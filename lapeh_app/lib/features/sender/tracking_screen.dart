import 'dart:async';
import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:google_maps_flutter/google_maps_flutter.dart';
import '../../core/theme.dart';
import '../../core/i18n.dart';
import '../../core/api_client.dart';
import '../../core/models/order_model.dart';
import '../../core/providers/sender_provider.dart';
import '../../shared/widgets.dart';
import '../../shared/map_placeholder.dart';

class TrackingScreen extends ConsumerStatefulWidget {
  final OrderModel order;
  const TrackingScreen({super.key, required this.order});
  @override
  ConsumerState<TrackingScreen> createState() => _TrackingScreenState();
}

class _TrackingScreenState extends ConsumerState<TrackingScreen> {
  Timer? _poll;
  OrderModel? _current;
  GoogleMapController? _mapCtrl;
  bool _resending = false;

  Future<void> _resend(int orderId) async {
    setState(() => _resending = true);
    try {
      await ref.read(senderServiceProvider).resendLink(orderId);
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text(tr('link_resent'))));
      }
    } catch (e) {
      if (mounted) ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text(apiErrorMessage(e))));
    } finally {
      if (mounted) setState(() => _resending = false);
    }
  }

  @override
  void initState() {
    super.initState();
    _current = widget.order;
    _refresh(); // list payloads lack coords; detail payload has them
    if (!widget.order.isTerminal) {
      _poll = Timer.periodic(const Duration(seconds: 5), (_) => _refresh());
    }
  }

  Future<void> _refresh() async {
    try {
      final updated = await ref.read(senderServiceProvider).getOrder(widget.order.id);
      if (!mounted) return;
      setState(() => _current = updated);
      _fitMap(updated);
      if (updated.isTerminal) {
        _poll?.cancel();
        // Lists are stale once an order finishes
        ref.invalidate(dashboardProvider);
        ref.invalidate(ordersProvider(null));
        ref.invalidate(historyProvider);
      }
    } catch (_) {}
  }

  @override
  void dispose() {
    _poll?.cancel();
    _mapCtrl?.dispose();
    super.dispose();
  }

  List<LatLng> _points(OrderModel o) => [
        if (o.hasPickupCoords) LatLng(o.pickupLat!, o.pickupLng!),
        if (o.hasCustomerCoords) LatLng(o.customerLat!, o.customerLng!),
        if (o.driver?.lat != null && o.driver?.lng != null) LatLng(o.driver!.lat!, o.driver!.lng!),
      ];

  void _fitMap(OrderModel o) {
    final pts = _points(o);
    if (_mapCtrl == null || pts.isEmpty) return;
    if (pts.length == 1) {
      _mapCtrl!.animateCamera(CameraUpdate.newLatLngZoom(pts.first, 14));
      return;
    }
    double minLat = pts.first.latitude, maxLat = pts.first.latitude;
    double minLng = pts.first.longitude, maxLng = pts.first.longitude;
    for (final p in pts) {
      if (p.latitude < minLat) minLat = p.latitude;
      if (p.latitude > maxLat) maxLat = p.latitude;
      if (p.longitude < minLng) minLng = p.longitude;
      if (p.longitude > maxLng) maxLng = p.longitude;
    }
    _mapCtrl!.animateCamera(CameraUpdate.newLatLngBounds(
      LatLngBounds(southwest: LatLng(minLat, minLng), northeast: LatLng(maxLat, maxLng)),
      60,
    ));
  }

  Set<Marker> _markers(OrderModel o) => {
        if (o.hasPickupCoords)
          Marker(
            markerId: const MarkerId('pickup'),
            position: LatLng(o.pickupLat!, o.pickupLng!),
            icon: BitmapDescriptor.defaultMarkerWithHue(BitmapDescriptor.hueViolet),
            infoWindow: InfoWindow(title: o.pickupName ?? tr('pickup_label')),
          ),
        if (o.hasCustomerCoords)
          Marker(
            markerId: const MarkerId('customer'),
            position: LatLng(o.customerLat!, o.customerLng!),
            icon: BitmapDescriptor.defaultMarkerWithHue(BitmapDescriptor.hueRose),
            infoWindow: InfoWindow(title: o.customerName),
          ),
        if (o.driver?.lat != null && o.driver?.lng != null)
          Marker(
            markerId: const MarkerId('driver'),
            position: LatLng(o.driver!.lat!, o.driver!.lng!),
            icon: BitmapDescriptor.defaultMarkerWithHue(BitmapDescriptor.hueAzure),
            infoWindow: InfoWindow(title: o.driver!.name),
          ),
      };

  Widget _map(OrderModel o) {
    final pts = _points(o);
    if (pts.isEmpty) {
      // No coordinates yet (customer hasn't confirmed) — stylized placeholder
      return MapPlaceholder(
        height: 300,
        showRoute: false,
        pickupLabel: tr('pickup_label'),
        dropLabel: o.customerName,
        radius: BorderRadius.zero,
      );
    }
    return SizedBox(
      height: 300,
      child: GoogleMap(
        initialCameraPosition: CameraPosition(target: pts.first, zoom: 13),
        markers: _markers(o),
        zoomControlsEnabled: false,
        myLocationButtonEnabled: false,
        onMapCreated: (c) {
          _mapCtrl = c;
          _fitMap(o);
        },
      ),
    );
  }

  // Backend status names. Pre-dispatch statuses collapse onto the first step.
  List<StatusStep> _buildTimeline(OrderModel o) {
    final steps = [
      ('created', tr('order_created')),
      ('searching_driver', tr('st_searching_driver')),
      ('driver_assigned', tr('st_driver_assigned')),
      ('arrived_at_pickup', tr('st_arrived_at_pickup')),
      ('picked_up', tr('st_picked_up')),
      ('on_the_way', tr('st_on_the_way')),
      ('delivered', tr('st_delivered')),
    ];
    final statusOrder = steps.map((s) => s.$1).toList();
    var currentIdx = statusOrder.indexOf(o.status);
    if (currentIdx == -1) currentIdx = 0; // waiting_for_location/paid/etc → "created" stage

    return [
      for (var i = 0; i < steps.length; i++)
        StatusStep(steps[i].$2, i < currentIdx ? 'done' : (i == currentIdx ? 'active' : 'todo')),
    ];
  }

  @override
  Widget build(BuildContext context) {
    final o = _current ?? widget.order;
    return Scaffold(
      appBar: AppBar(title: Text('${o.orderNo} · ${o.customerName}', overflow: TextOverflow.ellipsis)),
      body: Column(
        children: [
          _map(o),
          Expanded(
            child: Transform.translate(
              offset: const Offset(0, -22),
              child: Container(
                decoration: const BoxDecoration(
                  color: Colors.white,
                  borderRadius: BorderRadius.vertical(top: Radius.circular(24)),
                  boxShadow: [BoxShadow(color: Color(0x22000000), blurRadius: 24, offset: Offset(0, -8))],
                ),
                child: ListView(
                  padding: const EdgeInsets.fromLTRB(16, 10, 16, 24),
                  children: [
                    Center(child: Container(width: 40, height: 4, decoration: BoxDecoration(color: AppColors.line, borderRadius: BorderRadius.circular(9)))),
                    const SizedBox(height: 12),
                    if (o.hasDriver) ...[
                      Row(children: [
                        const CircleAvatar(radius: 22, backgroundColor: AppColors.ink, child: Icon(Icons.person, color: Colors.white)),
                        const SizedBox(width: 11),
                        Expanded(
                          child: Column(
                            crossAxisAlignment: CrossAxisAlignment.start,
                            children: [
                              Text(o.driver!.name, style: const TextStyle(fontSize: 14, fontWeight: FontWeight.w700)),
                              Row(children: [
                                const Icon(Icons.pedal_bike, size: 13, color: AppColors.slate),
                                const SizedBox(width: 3),
                                Expanded(
                                  child: Text('${o.driver!.vehicleType} · ${o.driver!.phone}',
                                      style: const TextStyle(fontSize: 11, color: AppColors.slate),
                                      overflow: TextOverflow.ellipsis),
                                ),
                              ]),
                            ],
                          ),
                        ),
                        Container(
                          width: 40, height: 40,
                          decoration: BoxDecoration(color: AppColors.greenSoft, borderRadius: BorderRadius.circular(12)),
                          child: const Icon(Icons.phone, color: AppColors.green, size: 18),
                        ),
                      ]),
                      const SizedBox(height: 12),
                    ],
                    Container(
                      padding: const EdgeInsets.all(11),
                      decoration: BoxDecoration(color: AppColors.pinkSoft, borderRadius: BorderRadius.circular(12)),
                      child: Row(children: [
                        const Icon(Icons.pedal_bike, size: 16, color: AppColors.pinkDeep),
                        const SizedBox(width: 7),
                        Expanded(child: Text(
                          _statusLabel(o.status),
                          style: const TextStyle(fontSize: 12, fontWeight: FontWeight.w700, color: AppColors.pinkDeep),
                        )),
                        StatusBadge(status: o.status),
                      ]),
                    ),
                    if (o.deliveryFee != null) ...[
                      const SizedBox(height: 10),
                      Row(mainAxisAlignment: MainAxisAlignment.spaceBetween, children: [
                        Text(tr('delivery_fee'), style: const TextStyle(fontSize: 12.5, color: AppColors.slate)),
                        Text('AED ${o.deliveryFee!.toStringAsFixed(2)}', style: const TextStyle(fontSize: 13, fontWeight: FontWeight.w700)),
                      ]),
                    ],
                    if (o.customerLink != null && !o.isTerminal) ...[
                      const SizedBox(height: 14),
                      Text(tr('customer_link'),
                          style: const TextStyle(fontSize: 11.5, fontWeight: FontWeight.w700, color: AppColors.slate)),
                      const SizedBox(height: 6),
                      InkWell(
                        onTap: () => copyLink(context, o.customerLink!),
                        borderRadius: BorderRadius.circular(10),
                        child: Container(
                          padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 11),
                          decoration: BoxDecoration(
                            color: const Color(0xFFF4F5F8),
                            borderRadius: BorderRadius.circular(10),
                            border: Border.all(color: AppColors.line),
                          ),
                          child: Row(children: [
                            Expanded(
                              child: Text(o.customerLink!,
                                  style: const TextStyle(fontSize: 12, color: AppColors.ink),
                                  overflow: TextOverflow.ellipsis),
                            ),
                            const SizedBox(width: 8),
                            const Icon(Icons.copy_rounded, size: 17, color: AppColors.pink),
                          ]),
                        ),
                      ),
                      const SizedBox(height: 10),
                      Row(children: [
                        Expanded(child: LapehButton(label: tr('copy_link'), icon: Icons.copy_rounded, onPressed: () => copyLink(context, o.customerLink!))),
                        const SizedBox(width: 10),
                        Expanded(
                          child: _resending
                              ? const Center(child: Padding(padding: EdgeInsets.symmetric(vertical: 14), child: SizedBox(width: 22, height: 22, child: CircularProgressIndicator(strokeWidth: 2.5, color: AppColors.pink))))
                              : LapehButton(label: tr('resend_link'), icon: Icons.send_outlined, ghost: true, onPressed: () => _resend(o.id)),
                        ),
                      ]),
                    ],
                    if (o.items.isNotEmpty) ...[
                      const SizedBox(height: 14),
                      Text(tr('package_items'),
                          style: const TextStyle(fontSize: 11.5, fontWeight: FontWeight.w700, color: AppColors.slate)),
                      const SizedBox(height: 6),
                      ...o.items.map((i) => Padding(
                            padding: const EdgeInsets.symmetric(vertical: 4),
                            child: Row(mainAxisAlignment: MainAxisAlignment.spaceBetween, children: [
                              Expanded(child: Text('${i.name} ×${i.quantity}', style: const TextStyle(fontSize: 13))),
                              Text('AED ${i.totalPrice.toStringAsFixed(2)}', style: const TextStyle(fontSize: 12.5, fontWeight: FontWeight.w600)),
                            ]),
                          )),
                    ],
                    const SizedBox(height: 14),
                    StatusTimeline(steps: _buildTimeline(o)),
                  ],
                ),
              ),
            ),
          ),
        ],
      ),
    );
  }

  String _statusLabel(String s) {
    switch (s) {
      case 'searching_driver': return tr('track_searching');
      case 'driver_assigned': return tr('track_assigned');
      case 'arrived_at_pickup': return tr('st_arrived_at_pickup');
      case 'picked_up': return tr('track_picked_up');
      case 'on_the_way': return tr('track_on_the_way');
      case 'delivered': return tr('track_delivered');
      case 'cancelled': return tr('track_cancelled');
      default: return trOr('st_$s', s.replaceAll('_', ' '));
    }
  }
}
