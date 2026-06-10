import 'dart:async';
import 'dart:io';
import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:google_maps_flutter/google_maps_flutter.dart';
import 'package:image_picker/image_picker.dart';
import 'package:url_launcher/url_launcher.dart';
import '../../core/theme.dart';
import '../../core/api_client.dart';
import '../../core/i18n.dart';
import '../../core/models/order_model.dart';
import '../../core/providers/driver_provider.dart';
import '../../core/services/driver_service.dart';
import '../../core/services/location_service.dart';
import '../../shared/widgets.dart';

class DeliveryFlowScreen extends ConsumerStatefulWidget {
  final OrderModel order;
  const DeliveryFlowScreen({super.key, required this.order});
  @override
  ConsumerState<DeliveryFlowScreen> createState() => _DeliveryFlowScreenState();
}

class _DeliveryFlowScreenState extends ConsumerState<DeliveryFlowScreen> {
  // 0 to-restaurant · 1 pickup · 2 to-customer · 3 otp · 4 delivered
  int step = 0;
  bool _loading = false;

  @override
  void initState() {
    super.initState();
    // Resume mid-delivery after app restart — backend rejects out-of-order
    // transitions, so the step must match the order's actual status.
    step = switch (widget.order.status) {
      'arrived_at_restaurant' => 1,
      'picked_up' || 'on_the_way' => 2,
      'delivered' => 4,
      _ => 0,
    };
    if (widget.order.status == 'picked_up') {
      // Trip resumed: mark on_the_way so customer tracking moves forward.
      DriverService()
          .updateOrderStatus(widget.order.id, 'on_the_way')
          .catchError((_) {});
    }
  }

  Future<void> _advance(String status) async {
    setState(() => _loading = true);
    try {
      await DriverService().updateOrderStatus(widget.order.id, status);
      setState(() { step++; _loading = false; });
    } catch (e) {
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text(apiErrorMessage(e))));
        setState(() => _loading = false);
      }
    }
  }

  /// Pickup confirm: picked_up then on_the_way in one tap — the trip starts
  /// immediately, and customer tracking shows "on the way" right away.
  Future<void> _pickupAndGo() async {
    setState(() => _loading = true);
    try {
      final svc = DriverService();
      await svc.updateOrderStatus(widget.order.id, 'picked_up');
      await svc.updateOrderStatus(widget.order.id, 'on_the_way');
      setState(() { step = 2; _loading = false; });
    } catch (e) {
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text(apiErrorMessage(e))));
        setState(() => _loading = false);
      }
    }
  }

  @override
  Widget build(BuildContext context) {
    final o = widget.order;
    if (_loading) {
      return const Scaffold(body: Center(child: CircularProgressIndicator(color: AppColors.pink)));
    }
    switch (step) {
      case 0:
        return _NavScreen(
          title: tr('to_restaurant'),
          destination: o.hasRestaurantCoords ? LatLng(o.restaurantLat!, o.restaurantLng!) : null,
          destinationLabel: o.restaurantName ?? tr('restaurant_pickup'),
          banner: ('${tr('head_to')} ${o.restaurantName ?? tr('restaurant_pickup')}', ''),
          contactIcon: Icons.storefront,
          contactIsPickup: true,
          contactTitle: o.restaurantName ?? tr('restaurant_pickup'),
          contactSub: '${tr('order_prefix')} ${o.orderNo} · AED ${o.orderValue.toStringAsFixed(0)}',
          cta: tr('arrived_restaurant'),
          onCta: () => _advance('arrived_at_restaurant'),
        );
      case 1:
        return _PickupScreen(order: o, onNext: _pickupAndGo);
      case 2:
        return _NavScreen(
          title: tr('to_customer'),
          destination: o.hasCustomerCoords ? LatLng(o.customerLat!, o.customerLng!) : null,
          destinationLabel: o.customerName,
          banner: (o.customerAddress ?? o.customerName, o.distanceKm != null ? '${o.distanceKm!.toStringAsFixed(1)} km' : ''),
          contactIcon: Icons.person,
          contactIsPickup: false,
          contactTitle: o.customerName,
          contactSub: o.customerPhone,
          cta: tr('ive_arrived'),
          onCta: () => setState(() => step++),
        );
      case 3:
        return _OtpScreen(order: o, onDone: () => setState(() => step++));
      default:
        return _DeliveredScreen(order: o);
    }
  }
}

// ─── Navigation screen with real Google Map ───────────────────────────────────

class _NavScreen extends StatefulWidget {
  final String title;
  final LatLng? destination;
  final String destinationLabel;
  final (String, String) banner;
  final IconData contactIcon;
  final bool contactIsPickup;
  final String contactTitle, contactSub;
  final String cta;
  final VoidCallback onCta;

  const _NavScreen({
    required this.title,
    required this.destination,
    required this.destinationLabel,
    required this.banner,
    required this.contactIcon,
    required this.contactIsPickup,
    required this.contactTitle,
    required this.contactSub,
    required this.cta,
    required this.onCta,
  });

  @override
  State<_NavScreen> createState() => _NavScreenState();
}

class _NavScreenState extends State<_NavScreen> {
  GoogleMapController? _mapCtrl;
  LatLng? _currentPos;
  Timer? _locTimer;
  Set<Marker> _markers = {};

  @override
  void initState() {
    super.initState();
    _updateLocation();
    _locTimer = Timer.periodic(const Duration(seconds: 5), (_) => _updateLocation());
    _buildMarkers();
  }

  Future<void> _updateLocation() async {
    final pos = await LocationService().getCurrentPosition();
    if (pos != null && mounted) {
      setState(() => _currentPos = LatLng(pos.latitude, pos.longitude));
      _buildMarkers();
    }
  }

  void _buildMarkers() {
    final markers = <Marker>{};
    if (_currentPos != null) {
      markers.add(Marker(
        markerId: const MarkerId('you'),
        position: _currentPos!,
        icon: BitmapDescriptor.defaultMarkerWithHue(BitmapDescriptor.hueAzure),
        infoWindow: InfoWindow(title: tr('you_marker')),
      ));
    }
    if (widget.destination != null) {
      markers.add(Marker(
        markerId: const MarkerId('dest'),
        position: widget.destination!,
        infoWindow: InfoWindow(title: widget.destinationLabel),
      ));
    }
    if (mounted) setState(() => _markers = markers);
  }

  Future<void> _launchNavigation() async {
    if (widget.destination == null) return;
    final lat = widget.destination!.latitude;
    final lng = widget.destination!.longitude;
    final uri = Uri.parse('google.navigation:q=$lat,$lng&mode=d');
    final fallback = Uri.parse('https://www.google.com/maps/dir/?api=1&destination=$lat,$lng&travelmode=driving');
    if (await canLaunchUrl(uri)) {
      await launchUrl(uri);
    } else {
      await launchUrl(fallback, mode: LaunchMode.externalApplication);
    }
  }

  @override
  void dispose() {
    _locTimer?.cancel();
    _mapCtrl?.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    final dest = widget.destination;
    final initialCamera = dest != null
        ? CameraPosition(target: dest, zoom: 14)
        : const CameraPosition(target: LatLng(25.2048, 55.2708), zoom: 12);

    return Scaffold(
      appBar: AppBar(title: Text(widget.title)),
      body: Column(children: [
        SizedBox(
          height: 300,
          child: dest != null
              ? GoogleMap(
                  initialCameraPosition: initialCamera,
                  markers: _markers,
                  myLocationEnabled: true,
                  myLocationButtonEnabled: false,
                  zoomControlsEnabled: false,
                  onMapCreated: (c) {
                    _mapCtrl = c;
                    if (_currentPos != null) {
                      c.animateCamera(CameraUpdate.newLatLngBounds(
                        _latLngBounds(_currentPos!, dest),
                        80,
                      ));
                    }
                  },
                )
              : Container(color: AppColors.line, child: Center(child: Text(tr('no_coordinates'), style: const TextStyle(color: AppColors.slate)))),
        ),
        Expanded(
          child: Transform.translate(
            offset: const Offset(0, -22),
            child: Container(
              width: double.infinity,
              decoration: const BoxDecoration(
                color: Colors.white,
                borderRadius: BorderRadius.vertical(top: Radius.circular(24)),
              ),
              padding: const EdgeInsets.fromLTRB(16, 10, 16, 20),
              child: Column(children: [
                Container(width: 40, height: 4, decoration: BoxDecoration(color: AppColors.line, borderRadius: BorderRadius.circular(9))),
                const SizedBox(height: 14),
                // Navigation banner
                GestureDetector(
                  onTap: _launchNavigation,
                  child: Container(
                    padding: const EdgeInsets.all(13),
                    decoration: BoxDecoration(color: AppColors.ink, borderRadius: BorderRadius.circular(14)),
                    child: Row(children: [
                      const Icon(Icons.navigation, color: Color(0xFF7CF0B4), size: 20),
                      const SizedBox(width: 11),
                      Expanded(
                        child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
                          Text(widget.banner.$1, style: const TextStyle(color: Colors.white, fontSize: 13.5, fontWeight: FontWeight.w700)),
                          if (widget.banner.$2.isNotEmpty)
                            Text(widget.banner.$2, style: const TextStyle(color: Color(0xFFB7BECC), fontSize: 11)),
                        ]),
                      ),
                      const Icon(Icons.open_in_new, color: Color(0xFF7CF0B4), size: 16),
                    ]),
                  ),
                ),
                const SizedBox(height: 12),
                Row(children: [
                  Container(
                    width: 38, height: 38,
                    decoration: BoxDecoration(
                      color: widget.contactIsPickup ? AppColors.ink : AppColors.pinkSoft,
                      borderRadius: BorderRadius.circular(11),
                    ),
                    child: Icon(widget.contactIcon, color: widget.contactIsPickup ? Colors.white : AppColors.pink, size: 18),
                  ),
                  const SizedBox(width: 11),
                  Expanded(
                    child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
                      Text(widget.contactTitle, style: const TextStyle(fontSize: 13.5, fontWeight: FontWeight.w700)),
                      Text(widget.contactSub, style: T.mutedSm),
                    ]),
                  ),
                  GestureDetector(
                    onTap: () => launchUrl(Uri.parse('tel:${widget.contactSub}')),
                    child: Container(
                      width: 40, height: 40,
                      decoration: BoxDecoration(color: AppColors.greenSoft, borderRadius: BorderRadius.circular(12)),
                      child: const Icon(Icons.phone, color: AppColors.green, size: 18),
                    ),
                  ),
                ]),
                const Spacer(),
                LapehButton(label: widget.cta, icon: Icons.check, onPressed: widget.onCta),
              ]),
            ),
          ),
        ),
      ]),
    );
  }

  LatLngBounds _latLngBounds(LatLng a, LatLng b) => LatLngBounds(
        southwest: LatLng(a.latitude < b.latitude ? a.latitude : b.latitude,
            a.longitude < b.longitude ? a.longitude : b.longitude),
        northeast: LatLng(a.latitude > b.latitude ? a.latitude : b.latitude,
            a.longitude > b.longitude ? a.longitude : b.longitude),
      );
}

// ─── Pickup confirmation screen ───────────────────────────────────────────────

class _PickupScreen extends StatelessWidget {
  final OrderModel order;
  final VoidCallback onNext;
  const _PickupScreen({required this.order, required this.onNext});

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(title: Text(tr('pickup'))),
      body: Column(children: [
        if (order.hasRestaurantCoords)
          SizedBox(
            height: 240,
            child: GoogleMap(
              initialCameraPosition: CameraPosition(target: LatLng(order.restaurantLat!, order.restaurantLng!), zoom: 16),
              markers: {
                Marker(markerId: const MarkerId('rest'), position: LatLng(order.restaurantLat!, order.restaurantLng!),
                    infoWindow: InfoWindow(title: order.restaurantName ?? tr('restaurant_pickup'))),
              },
              zoomControlsEnabled: false,
              myLocationEnabled: true,
              myLocationButtonEnabled: false,
            ),
          )
        else
          Container(height: 240, color: AppColors.line),
        Expanded(
          child: Transform.translate(
            offset: const Offset(0, -22),
            child: Container(
              width: double.infinity,
              decoration: const BoxDecoration(color: Colors.white, borderRadius: BorderRadius.vertical(top: Radius.circular(24))),
              padding: const EdgeInsets.fromLTRB(16, 10, 16, 20),
              child: Column(children: [
                Container(width: 40, height: 4, decoration: BoxDecoration(color: AppColors.line, borderRadius: BorderRadius.circular(9))),
                const SizedBox(height: 14),
                Container(
                  padding: const EdgeInsets.all(12),
                  decoration: BoxDecoration(color: AppColors.pinkSoft, borderRadius: BorderRadius.circular(14)),
                  child: Row(children: [
                    const Icon(Icons.storefront, color: AppColors.pink, size: 20),
                    const SizedBox(width: 11),
                    Expanded(child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
                      Text('${tr('collect_order')} ${order.orderNo}', style: const TextStyle(fontSize: 13.5, fontWeight: FontWeight.w700)),
                      Text('${order.customerName} · AED ${order.orderValue.toStringAsFixed(0)}', style: T.mutedSm),
                    ])),
                  ]),
                ),
                const SizedBox(height: 14),
                StatusTimeline(steps: [
                  StatusStep(tr('arrived_restaurant'), 'done'),
                  StatusStep(tr('track_picked_up'), 'active'),
                  StatusStep(tr('track_on_the_way'), 'todo'),
                ]),
                const Spacer(),
                LapehButton(label: tr('picked_up_start'), icon: Icons.pedal_bike, onPressed: onNext),
              ]),
            ),
          ),
        ),
      ]),
    );
  }
}

// ─── OTP + photo screen ───────────────────────────────────────────────────────

class _OtpScreen extends ConsumerStatefulWidget {
  final OrderModel order;
  final VoidCallback onDone;
  const _OtpScreen({required this.order, required this.onDone});
  @override
  ConsumerState<_OtpScreen> createState() => _OtpScreenState();
}

class _OtpScreenState extends ConsumerState<_OtpScreen> {
  String code = '';
  File? _photo;
  bool _loading = false;
  String? _error;
  final _picker = ImagePicker();

  void _key(String d) {
    if (d == 'back') {
      if (code.isNotEmpty) setState(() => code = code.substring(0, code.length - 1));
    } else if (code.length < 4) {
      setState(() => code += d);
    }
  }

  Future<void> _pickPhoto() async {
    final picked = await _picker.pickImage(source: ImageSource.camera, imageQuality: 70);
    if (picked != null && mounted) setState(() => _photo = File(picked.path));
  }

  Future<void> _confirm() async {
    if (code.length < 4) return;
    setState(() { _loading = true; _error = null; });
    try {
      await DriverService().deliver(widget.order.id, otp: code, photoPath: _photo?.path);
      await ref.read(driverStatusProvider.notifier).goOnline();
      LocationService().stopBroadcasting();
      widget.onDone();
    } catch (e) {
      setState(() {
        _error = tr('wrong_code');
        _loading = false;
        code = '';
      });
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(title: Text(tr('confirm_delivery'))),
      body: SafeArea(
        child: Padding(
          padding: const EdgeInsets.fromLTRB(20, 8, 20, 18),
          child: Column(children: [
            Expanded(
              child: SingleChildScrollView(
                child: Column(children: [
            Container(
              width: 54, height: 54,
              decoration: BoxDecoration(color: AppColors.pinkSoft, borderRadius: BorderRadius.circular(16)),
              child: const Icon(Icons.verified_user_outlined, color: AppColors.pink, size: 26),
            ),
            const SizedBox(height: 12),
            Text(tr('enter_code'), style: T.h2),
            const SizedBox(height: 6),
            Text(tr('ask_code'), textAlign: TextAlign.center, style: T.muted),
            const SizedBox(height: 18),
            Row(
              mainAxisAlignment: MainAxisAlignment.center,
              children: List.generate(4, (i) {
                final filled = i < code.length;
                return Container(
                  margin: const EdgeInsets.symmetric(horizontal: 6),
                  width: 50, height: 58,
                  alignment: Alignment.center,
                  decoration: BoxDecoration(
                    color: filled ? AppColors.pinkSoft : Colors.white,
                    borderRadius: BorderRadius.circular(13),
                    border: Border.all(color: filled ? AppColors.pink : AppColors.line, width: 1.5),
                  ),
                  child: Text(filled ? code[i] : '', style: const TextStyle(fontSize: 22, fontWeight: FontWeight.w800)),
                );
              }),
            ),
            if (_error != null) ...[
              const SizedBox(height: 10),
              Text(_error!, style: const TextStyle(color: AppColors.red, fontWeight: FontWeight.w600)),
            ],
            const SizedBox(height: 18),
            _Keypad(onKey: _key),
            const SizedBox(height: 12),
            // Photo preview or add button
            if (_photo != null)
              ClipRRect(
                borderRadius: BorderRadius.circular(12),
                child: Image.file(_photo!, height: 90, width: double.infinity, fit: BoxFit.cover),
              )
            else
              GestureDetector(
                onTap: _pickPhoto,
                child: Container(
                  height: 52,
                  decoration: BoxDecoration(
                    color: Colors.white,
                    borderRadius: BorderRadius.circular(12),
                    border: Border.all(color: AppColors.line),
                  ),
                  child: Row(mainAxisAlignment: MainAxisAlignment.center, children: [
                    const Icon(Icons.photo_camera_outlined, size: 19, color: AppColors.slate),
                    const SizedBox(width: 8),
                    Text(tr('add_photo'), style: const TextStyle(color: AppColors.slate, fontWeight: FontWeight.w600, fontSize: 13)),
                  ]),
                ),
              ),
                ]),
              ),
            ),
            const SizedBox(height: 12),
            _loading
                ? const Center(child: CircularProgressIndicator(color: AppColors.pink))
                : LapehButton(
                    label: tr('confirm_btn'),
                    icon: Icons.check,
                    onPressed: code.length == 4 ? _confirm : null,
                  ),
          ]),
        ),
      ),
    );
  }
}

class _Keypad extends StatelessWidget {
  final void Function(String) onKey;
  const _Keypad({required this.onKey});
  @override
  Widget build(BuildContext context) {
    const keys = ['1', '2', '3', '4', '5', '6', '7', '8', '9', '', '0', 'back'];
    return GridView.count(
      crossAxisCount: 3,
      shrinkWrap: true,
      physics: const NeverScrollableScrollPhysics(),
      childAspectRatio: 1.9,
      crossAxisSpacing: 8,
      mainAxisSpacing: 8,
      children: keys.map((k) {
        if (k.isEmpty) return const SizedBox();
        return Material(
          color: Colors.white,
          borderRadius: BorderRadius.circular(12),
          child: InkWell(
            borderRadius: BorderRadius.circular(12),
            onTap: () => onKey(k),
            child: Container(
              decoration: BoxDecoration(borderRadius: BorderRadius.circular(12), border: Border.all(color: AppColors.line)),
              alignment: Alignment.center,
              child: k == 'back'
                  ? const Icon(Icons.backspace_outlined, size: 18)
                  : Text(k, style: const TextStyle(fontSize: 18, fontWeight: FontWeight.w700)),
            ),
          ),
        );
      }).toList(),
    );
  }
}

// ─── Delivered success screen ─────────────────────────────────────────────────

class _DeliveredScreen extends ConsumerWidget {
  final OrderModel order;
  const _DeliveredScreen({required this.order});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    return Scaffold(
      body: SafeArea(
        child: Padding(
          padding: const EdgeInsets.fromLTRB(20, 30, 20, 20),
          child: Column(children: [
            Container(
              width: 84, height: 84,
              decoration: BoxDecoration(
                color: AppColors.green, shape: BoxShape.circle,
                boxShadow: const [BoxShadow(color: AppColors.greenSoft, spreadRadius: 12)],
              ),
              child: const Icon(Icons.check, color: Colors.white, size: 42),
            ),
            const SizedBox(height: 18),
            Text(tr('delivered_title'), style: T.h1),
            const SizedBox(height: 4),
            Text('${tr('order_prefix')} ${order.orderNo} ${tr('completed_label')}', style: T.muted),
            const SizedBox(height: 18),
            AppCard(
              child: Column(children: [
                _FeeLine(tr('delivery_earnings'), order.deliveryFee != null ? 'AED ${order.deliveryFee!.toStringAsFixed(2)}' : '–'),
                if (order.distanceKm != null) _FeeLine(tr('distance'), '${order.distanceKm!.toStringAsFixed(1)} km'),
                const Divider(height: 18, color: AppColors.line),
                _FeeLine(tr('order_value_label'), 'AED ${order.orderValue.toStringAsFixed(2)}', total: true),
              ]),
            ),
            const Spacer(),
            LapehButton(
              label: tr('back_online'),
              icon: Icons.power_settings_new,
              onPressed: () {
                ref.read(driverStatusProvider.notifier).goOnline();
                Navigator.of(context).popUntil((r) => r.isFirst);
              },
            ),
            const SizedBox(height: 10),
            LapehButton(label: tr('go_offline'), ghost: true, onPressed: () {
              ref.read(driverStatusProvider.notifier).goOffline();
              Navigator.of(context).popUntil((r) => r.isFirst);
            }),
          ]),
        ),
      ),
    );
  }
}

class _FeeLine extends StatelessWidget {
  final String label, value;
  final bool total;
  const _FeeLine(this.label, this.value, {this.total = false});
  @override
  Widget build(BuildContext context) {
    return Padding(
      padding: const EdgeInsets.symmetric(vertical: 4),
      child: Row(mainAxisAlignment: MainAxisAlignment.spaceBetween, children: [
        Text(label, style: TextStyle(fontSize: total ? 13.5 : 12.5, color: total ? AppColors.ink : AppColors.slate, fontWeight: total ? FontWeight.w700 : FontWeight.w500)),
        Text(value, style: TextStyle(fontSize: total ? 16 : 13, fontWeight: FontWeight.w700, color: total ? AppColors.pink : AppColors.ink)),
      ]),
    );
  }
}
