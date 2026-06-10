import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import '../../core/theme.dart';
import '../../core/i18n.dart';
import '../../core/models/order_model.dart';
import '../../core/providers/driver_provider.dart';
import '../../core/services/driver_service.dart';
import '../../shared/widgets.dart';
import 'delivery_flow.dart';

void showIncomingRequest(BuildContext context, DeliveryOffer offer, {VoidCallback? onDone}) {
  showModalBottomSheet(
    context: context,
    isScrollControlled: true,
    backgroundColor: Colors.transparent,
    builder: (_) => _IncomingRequestSheet(offer: offer, onDone: onDone),
  );
}

class _IncomingRequestSheet extends ConsumerStatefulWidget {
  final DeliveryOffer offer;
  final VoidCallback? onDone;
  const _IncomingRequestSheet({required this.offer, this.onDone});
  @override
  ConsumerState<_IncomingRequestSheet> createState() => _IncomingRequestSheetState();
}

class _IncomingRequestSheetState extends ConsumerState<_IncomingRequestSheet> with SingleTickerProviderStateMixin {
  late final AnimationController _c;
  bool _loading = false;

  @override
  void initState() {
    super.initState();
    final timeout = widget.offer.timeoutSec;
    _c = AnimationController(vsync: this, duration: Duration(seconds: timeout))..reverse(from: 1.0);
    _c.addStatusListener((s) {
      if (s == AnimationStatus.dismissed && mounted) {
        Navigator.of(context).maybePop();
        widget.onDone?.call();
      }
    });
  }

  @override
  void dispose() {
    _c.dispose();
    super.dispose();
  }

  Future<void> _accept() async {
    setState(() => _loading = true);
    _c.stop();
    try {
      await DriverService().acceptOffer(widget.offer.id);
      final order = await DriverService().currentOrder();
      ref.read(driverStatusProvider.notifier).setOnDelivery();
      if (!mounted) return;
      Navigator.of(context).pop();
      if (order != null) {
        Navigator.push(context, MaterialPageRoute(builder: (_) => DeliveryFlowScreen(order: order)));
      }
    } catch (e) {
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text('${tr('error_prefix')}: $e')));
        setState(() => _loading = false);
        Navigator.of(context).pop();
        widget.onDone?.call();
      }
    }
  }

  Future<void> _reject() async {
    _c.stop();
    try {
      await DriverService().rejectOffer(widget.offer.id);
    } catch (_) {}
    if (mounted) {
      Navigator.of(context).pop();
      widget.onDone?.call();
    }
  }

  @override
  Widget build(BuildContext context) {
    final offer = widget.offer;
    return Container(
      decoration: const BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.vertical(top: Radius.circular(26)),
      ),
      padding: const EdgeInsets.fromLTRB(16, 16, 16, 22),
      child: Column(
        mainAxisSize: MainAxisSize.min,
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(children: [
            const SizedBox(width: 4),
            AnimatedBuilder(
              animation: _c,
              builder: (_, __) => SizedBox(
                width: 16, height: 16,
                child: CircularProgressIndicator(value: _c.value, strokeWidth: 3, color: AppColors.pink, backgroundColor: AppColors.pinkSoft),
              ),
            ),
            const SizedBox(width: 8),
            AnimatedBuilder(
              animation: _c,
              builder: (_, __) => Text('${tr('new_request')} · ${(_c.value * widget.offer.timeoutSec).ceil()}${tr('sec_suffix')}',
                  style: const TextStyle(fontWeight: FontWeight.w700, fontSize: 13, color: AppColors.pinkDeep)),
            ),
          ]),
          const SizedBox(height: 12),
          Row(crossAxisAlignment: CrossAxisAlignment.center, children: [
            const Icon(Icons.payments_outlined, color: AppColors.pink, size: 22),
            const SizedBox(width: 8),
            Text(offer.deliveryFee != null ? offer.deliveryFee!.toStringAsFixed(2) : '–',
                style: const TextStyle(fontSize: 30, fontWeight: FontWeight.w800)),
            const SizedBox(width: 6),
            Padding(padding: const EdgeInsets.only(bottom: 4), child: Text(tr('aed_earnings'), style: const TextStyle(fontSize: 13, color: AppColors.slate))),
          ]),
          const SizedBox(height: 12),
          _leg(Icons.storefront, AppColors.ink, Colors.white, offer.restaurantName,
              '${tr('pickup_short')} · ${offer.distanceKm != null ? "${offer.distanceKm!.toStringAsFixed(1)} ${tr('km_away')}" : "–"}'),
          Container(margin: const EdgeInsets.only(left: 16), height: 18, width: 0,
              decoration: const BoxDecoration(border: Border(left: BorderSide(color: AppColors.line, width: 2)))),
          _leg(Icons.location_on, AppColors.pink, AppColors.pinkSoft, offer.orderNo, tr('dropoff')),
          const SizedBox(height: 16),
          _loading
              ? const Center(child: CircularProgressIndicator(color: AppColors.pink))
              : Row(children: [
                  Expanded(child: LapehButton(label: tr('reject'), ghost: true, onPressed: _reject)),
                  const SizedBox(width: 10),
                  Expanded(child: LapehButton(label: tr('accept'), onPressed: _accept)),
                ]),
        ],
      ),
    );
  }

  Widget _leg(IconData icon, Color fg, Color bg, String title, String sub) {
    return Row(children: [
      Container(
        width: 34, height: 34,
        decoration: BoxDecoration(color: bg, borderRadius: BorderRadius.circular(11)),
        child: Icon(icon, color: fg, size: 16),
      ),
      const SizedBox(width: 11),
      Expanded(
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Text(title, style: const TextStyle(fontSize: 13, fontWeight: FontWeight.w700)),
            Text(sub, style: T.mutedSm),
          ],
        ),
      ),
    ]);
  }
}
