import 'dart:async';
import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import '../../core/theme.dart';
import '../../core/i18n.dart';
import '../../core/models/order_model.dart';
import '../../core/providers/restaurant_provider.dart';
import '../../shared/widgets.dart';
import '../../shared/map_placeholder.dart';

class TrackingScreen extends ConsumerStatefulWidget {
  final OrderModel order;
  const TrackingScreen({super.key, required this.order});
  @override
  ConsumerState<TrackingScreen> createState() => _TrackingScreenState();
}

class _TrackingScreenState extends ConsumerState<TrackingScreen> with SingleTickerProviderStateMixin {
  late final AnimationController _c;
  Timer? _poll;
  OrderModel? _current;

  @override
  void initState() {
    super.initState();
    _current = widget.order;
    _c = AnimationController(vsync: this, duration: const Duration(seconds: 12))..repeat();
    if (!widget.order.isTerminal) {
      _poll = Timer.periodic(const Duration(seconds: 5), (_) => _refresh());
    }
  }

  Future<void> _refresh() async {
    try {
      final updated = await ref.read(restaurantServiceProvider).getOrder(widget.order.id);
      if (mounted) setState(() => _current = updated);
      if (updated.isTerminal) _poll?.cancel();
    } catch (_) {}
  }

  @override
  void dispose() {
    _c.dispose();
    _poll?.cancel();
    super.dispose();
  }

  List<StatusStep> _buildTimeline(OrderModel o) {
    final steps = [
      ('created', tr('order_created')),
      ('searching_driver', tr('status_searching')),
      ('assigned', tr('status_assigned')),
      ('picked_up', tr('status_picked_up')),
      ('on_the_way', tr('status_on_the_way')),
      ('delivered', tr('status_delivered')),
    ];
    final statusOrder = steps.map((s) => s.$1).toList();
    final currentIdx = statusOrder.indexOf(o.status);

    return steps.map((s) {
      final idx = statusOrder.indexOf(s.$1);
      String state;
      if (idx < currentIdx) {
        state = 'done';
      } else if (idx == currentIdx) {
        state = 'active';
      } else {
        state = 'todo';
      }
      return StatusStep(s.$2, state);
    }).toList();
  }

  @override
  Widget build(BuildContext context) {
    final o = _current ?? widget.order;
    return Scaffold(
      appBar: AppBar(title: Text('${o.orderNo} · ${o.customerName}')),
      body: Column(
        children: [
          AnimatedBuilder(
            animation: _c,
            builder: (_, __) => MapPlaceholder(
              height: 300,
              movingDotT: o.hasDriver ? (0.15 + _c.value * 0.7) : null,
              pickupLabel: tr('restaurant_pickup'),
              dropLabel: o.customerName,
              radius: BorderRadius.zero,
            ),
          ),
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
                                Text('${o.driver!.vehicleType} · ${o.driver!.phone}', style: const TextStyle(fontSize: 11, color: AppColors.slate)),
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
      case 'assigned': return tr('track_assigned');
      case 'picked_up': return tr('track_picked_up');
      case 'on_the_way': return tr('track_on_the_way');
      case 'delivered': return tr('track_delivered');
      case 'cancelled': return tr('track_cancelled');
      default: return s.replaceAll('_', ' ');
    }
  }
}
