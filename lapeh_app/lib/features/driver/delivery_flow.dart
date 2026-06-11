import 'dart:async';
import 'dart:math' as math;
import 'dart:typed_data';
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
  // 0 to-pickup · 1 collect · 2 to-customer · 3 otp · 4 delivered
  int step = 0;
  bool _loading = false;

  @override
  void initState() {
    super.initState();
    // Resume mid-delivery after app restart — backend rejects out-of-order
    // transitions, so the step must match the order's actual status.
    step = switch (widget.order.status) {
      'arrived_at_pickup' => 1,
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
          order: o,
          toPickup: true,
          title: tr('to_pickup'),
          destination: o.hasPickupCoords ? LatLng(o.pickupLat!, o.pickupLng!) : null,
          destinationLabel: o.pickupName ?? tr('pickup_label'),
          cta: tr('arrived_pickup'),
          onCta: () => _advance('arrived_at_pickup'),
        );
      case 1:
        return _PickupScreen(order: o, onNext: _pickupAndGo);
      case 2:
        return _NavScreen(
          order: o,
          toPickup: false,
          title: tr('to_customer'),
          destination: o.hasCustomerCoords ? LatLng(o.customerLat!, o.customerLng!) : null,
          destinationLabel: o.customerName,
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
  final OrderModel order;
  final bool toPickup;
  final String title;
  final LatLng? destination;
  final String destinationLabel;
  final String cta;
  final VoidCallback onCta;

  const _NavScreen({
    required this.order,
    required this.toPickup,
    required this.title,
    required this.destination,
    required this.destinationLabel,
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
  double? _liveKm; // live distance from the driver to the destination

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
      final here = LatLng(pos.latitude, pos.longitude);
      final dest = widget.destination;
      setState(() {
        _currentPos = here;
        _liveKm = dest != null ? _haversineKm(here, dest) : null;
      });
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
          height: 270,
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
              decoration: BoxDecoration(
                color: Colors.white,
                borderRadius: const BorderRadius.vertical(top: Radius.circular(24)),
                boxShadow: [BoxShadow(color: Colors.black.withValues(alpha: .05), blurRadius: 20, offset: const Offset(0, -4))],
              ),
              padding: const EdgeInsets.fromLTRB(16, 10, 16, 16),
              child: Column(children: [
                Container(width: 40, height: 4, decoration: BoxDecoration(color: AppColors.line, borderRadius: BorderRadius.circular(9))),
                const SizedBox(height: 14),
                Row(children: [
                  _phasePill(),
                  const Spacer(),
                  if (_liveKm != null) ...[
                    _metric(Icons.route, '${_liveKm!.toStringAsFixed(1)} ${tr('km_unit')}'),
                    const SizedBox(width: 8),
                    _metric(Icons.schedule, '~${_etaMin()} ${tr('min_unit')}'),
                  ],
                ]),
                const SizedBox(height: 12),
                _navBanner(),
                const SizedBox(height: 14),
                Expanded(
                  child: SingleChildScrollView(
                    child: Column(children: [
                      _routeCard(),
                      const SizedBox(height: 12),
                      _contactCard(),
                      if (widget.order.items.isNotEmpty || widget.order.orderValue > 0) ...[
                        const SizedBox(height: 12),
                        _orderCard(),
                      ],
                    ]),
                  ),
                ),
                const SizedBox(height: 10),
                LapehButton(label: widget.cta, icon: Icons.check, onPressed: widget.onCta),
              ]),
            ),
          ),
        ),
      ]),
    );
  }

  int _etaMin() {
    if (_liveKm == null) return 0;
    // Rough city speed ~22 km/h.
    return (_liveKm! / 22 * 60).clamp(1, 999).round();
  }

  Widget _phasePill() {
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 11, vertical: 6),
      decoration: BoxDecoration(color: AppColors.pinkSoft, borderRadius: BorderRadius.circular(999)),
      child: Row(mainAxisSize: MainAxisSize.min, children: [
        Icon(widget.toPickup ? Icons.storefront : Icons.flag, size: 13, color: AppColors.pink),
        const SizedBox(width: 6),
        Text(widget.toPickup ? tr('to_pickup') : tr('to_customer'),
            style: const TextStyle(color: AppColors.pink, fontWeight: FontWeight.w700, fontSize: 12)),
      ]),
    );
  }

  Widget _metric(IconData icon, String text) {
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 6),
      decoration: BoxDecoration(color: AppColors.bg, borderRadius: BorderRadius.circular(999)),
      child: Row(mainAxisSize: MainAxisSize.min, children: [
        Icon(icon, size: 13, color: AppColors.slate),
        const SizedBox(width: 5),
        Text(text, style: const TextStyle(fontSize: 12, fontWeight: FontWeight.w700, color: AppColors.ink)),
      ]),
    );
  }

  Widget _navBanner() {
    final sub = _liveKm != null
        ? '${_liveKm!.toStringAsFixed(1)} ${tr('km_unit')} · ~${_etaMin()} ${tr('min_unit')}'
        : widget.destinationLabel;
    return GestureDetector(
      onTap: _launchNavigation,
      child: Container(
        padding: const EdgeInsets.all(13),
        decoration: BoxDecoration(color: AppColors.ink, borderRadius: BorderRadius.circular(14)),
        child: Row(children: [
          Container(
            width: 34, height: 34,
            decoration: BoxDecoration(color: Colors.white.withValues(alpha: .12), borderRadius: BorderRadius.circular(10)),
            child: const Icon(Icons.navigation, color: Color(0xFF7CF0B4), size: 18),
          ),
          const SizedBox(width: 11),
          Expanded(
            child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
              Text(tr('navigate'), style: const TextStyle(color: Colors.white, fontSize: 13.5, fontWeight: FontWeight.w700)),
              Text(sub, maxLines: 1, overflow: TextOverflow.ellipsis, style: const TextStyle(color: Color(0xFFB7BECC), fontSize: 11)),
            ]),
          ),
          const Icon(Icons.open_in_new, color: Color(0xFF7CF0B4), size: 16),
        ]),
      ),
    );
  }

  Widget _routeCard() {
    final o = widget.order;
    return Container(
      padding: const EdgeInsets.all(14),
      decoration: BoxDecoration(color: Colors.white, borderRadius: BorderRadius.circular(16), border: Border.all(color: AppColors.line)),
      child: Row(crossAxisAlignment: CrossAxisAlignment.start, children: [
        Column(children: [
          _dot(AppColors.green, filled: widget.toPickup),
          Container(width: 2, height: 34, color: AppColors.line),
          _dot(AppColors.pink, filled: !widget.toPickup),
        ]),
        const SizedBox(width: 12),
        Expanded(
          child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
            _routeBlock(tr('pickup'), o.pickupName ?? tr('pickup_label'), o.pickupAddress, active: widget.toPickup),
            const SizedBox(height: 16),
            _routeBlock(tr('dropoff_label'), o.customerName, o.customerAddress, active: !widget.toPickup),
          ]),
        ),
      ]),
    );
  }

  Widget _dot(Color color, {required bool filled}) {
    return Container(
      width: 14, height: 14,
      decoration: BoxDecoration(color: filled ? color : Colors.white, shape: BoxShape.circle, border: Border.all(color: color, width: 2)),
    );
  }

  Widget _routeBlock(String label, String title, String? sub, {required bool active}) {
    return Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
      Text(label.toUpperCase(),
          style: TextStyle(fontSize: 10, fontWeight: FontWeight.w800, letterSpacing: .5, color: active ? AppColors.pink : AppColors.slate2)),
      const SizedBox(height: 2),
      Text(title, style: const TextStyle(fontSize: 13.5, fontWeight: FontWeight.w700, color: AppColors.ink)),
      if (sub != null && sub.isNotEmpty) ...[
        const SizedBox(height: 1),
        Text(sub, maxLines: 2, overflow: TextOverflow.ellipsis, style: T.mutedSm),
      ],
    ]);
  }

  Widget _contactCard() {
    final o = widget.order;
    final name = widget.toPickup ? (o.pickupName ?? tr('pickup_label')) : o.customerName;
    final sub = widget.toPickup ? '${tr('order_prefix')} ${o.orderNo}' : o.customerPhone;
    final phone = widget.toPickup ? o.pickupPhone : o.customerPhone;
    return Container(
      padding: const EdgeInsets.all(12),
      decoration: BoxDecoration(color: AppColors.bg, borderRadius: BorderRadius.circular(16)),
      child: Row(children: [
        Container(
          width: 40, height: 40,
          decoration: BoxDecoration(color: widget.toPickup ? AppColors.ink : AppColors.pinkSoft, borderRadius: BorderRadius.circular(12)),
          child: Icon(widget.toPickup ? Icons.storefront : Icons.person, color: widget.toPickup ? Colors.white : AppColors.pink, size: 19),
        ),
        const SizedBox(width: 11),
        Expanded(
          child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
            Text(name, style: const TextStyle(fontSize: 13.5, fontWeight: FontWeight.w700)),
            Text(sub, style: T.mutedSm),
          ]),
        ),
        if (phone != null && phone.isNotEmpty)
          GestureDetector(
            onTap: () => launchUrl(Uri.parse('tel:$phone')),
            child: Container(
              width: 42, height: 42,
              decoration: BoxDecoration(color: AppColors.greenSoft, borderRadius: BorderRadius.circular(12)),
              child: const Icon(Icons.phone, color: AppColors.green, size: 18),
            ),
          ),
      ]),
    );
  }

  Widget _orderCard() {
    final o = widget.order;
    return Container(
      padding: const EdgeInsets.all(14),
      decoration: BoxDecoration(color: Colors.white, borderRadius: BorderRadius.circular(16), border: Border.all(color: AppColors.line)),
      child: Column(children: [
        _kv(tr('package_items'), '${o.items.length}'),
        const SizedBox(height: 8),
        _kv(tr('order_value_label'), 'AED ${o.orderValue.toStringAsFixed(2)}'),
        if (o.deliveryFee != null) ...[
          const SizedBox(height: 8),
          _kv(tr('delivery_earnings'), 'AED ${o.deliveryFee!.toStringAsFixed(2)}', highlight: true),
        ],
      ]),
    );
  }

  Widget _kv(String k, String v, {bool highlight = false}) {
    return Row(mainAxisAlignment: MainAxisAlignment.spaceBetween, children: [
      Text(k, style: T.mutedSm),
      Text(v, style: TextStyle(fontSize: 13, fontWeight: FontWeight.w700, color: highlight ? AppColors.pink : AppColors.ink)),
    ]);
  }

  LatLngBounds _latLngBounds(LatLng a, LatLng b) => LatLngBounds(
        southwest: LatLng(a.latitude < b.latitude ? a.latitude : b.latitude,
            a.longitude < b.longitude ? a.longitude : b.longitude),
        northeast: LatLng(a.latitude > b.latitude ? a.latitude : b.latitude,
            a.longitude > b.longitude ? a.longitude : b.longitude),
      );
}

double _haversineKm(LatLng a, LatLng b) {
  const r = 6371.0;
  final dLat = (b.latitude - a.latitude) * math.pi / 180;
  final dLng = (b.longitude - a.longitude) * math.pi / 180;
  final la1 = a.latitude * math.pi / 180;
  final la2 = b.latitude * math.pi / 180;
  final h = math.sin(dLat / 2) * math.sin(dLat / 2) +
      math.cos(la1) * math.cos(la2) * math.sin(dLng / 2) * math.sin(dLng / 2);
  return 2 * r * math.asin(math.sqrt(h));
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
        if (order.hasPickupCoords)
          SizedBox(
            height: 240,
            child: GoogleMap(
              initialCameraPosition: CameraPosition(target: LatLng(order.pickupLat!, order.pickupLng!), zoom: 16),
              markers: {
                Marker(markerId: const MarkerId('rest'), position: LatLng(order.pickupLat!, order.pickupLng!),
                    infoWindow: InfoWindow(title: order.pickupName ?? tr('pickup_label'))),
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
                  StatusStep(tr('arrived_pickup'), 'done'),
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
  Uint8List? _photoBytes;
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
    if (picked == null) return;
    // Read bytes so the preview/upload work on web too (no dart:io File).
    final bytes = await picked.readAsBytes();
    if (mounted) setState(() => _photoBytes = bytes);
  }

  Future<void> _confirm() async {
    if (code.length < 4) return;
    setState(() { _loading = true; _error = null; });
    try {
      await DriverService().deliver(widget.order.id, otp: code, photoBytes: _photoBytes);
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
            if (_photoBytes != null)
              ClipRRect(
                borderRadius: BorderRadius.circular(12),
                child: Image.memory(_photoBytes!, height: 90, width: double.infinity, fit: BoxFit.cover),
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
