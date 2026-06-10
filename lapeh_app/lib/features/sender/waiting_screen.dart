import 'dart:async';
import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import '../../core/theme.dart';
import '../../core/i18n.dart';
import '../../core/models/order_model.dart';
import '../../core/providers/sender_provider.dart';
import '../../core/status_meta.dart';
import '../../shared/widgets.dart';
import 'tracking_screen.dart';

class WaitingScreen extends ConsumerStatefulWidget {
  final OrderModel order;
  const WaitingScreen({super.key, required this.order});
  @override
  ConsumerState<WaitingScreen> createState() => _WaitingScreenState();
}

class _WaitingScreenState extends ConsumerState<WaitingScreen> {
  late OrderModel _order;
  Timer? _poll;
  bool _resending = false;

  @override
  void initState() {
    super.initState();
    _order = widget.order;
    _poll = Timer.periodic(const Duration(seconds: 4), (_) => _refresh());
  }

  Future<void> _refresh() async {
    try {
      final updated = await ref.read(senderServiceProvider).getOrder(_order.id);
      if (!mounted) return;
      setState(() => _order = updated);
      // Auto-advance when customer confirms location + pays → go to tracking
      if (updated.status == 'paid' ||
          updated.status == 'searching_driver' ||
          driverActiveStatuses.contains(updated.status)) {
        _poll?.cancel();
        if (mounted) {
          Navigator.pushReplacement(context, MaterialPageRoute(builder: (_) => TrackingScreen(order: updated)));
        }
      }
    } catch (_) {}
  }

  Future<void> _copyLink() async {
    final link = _order.customerLink;
    if (link == null) return;
    await Clipboard.setData(ClipboardData(text: link));
    if (mounted) {
      ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text(tr('link_copied'))));
    }
  }

  Future<void> _resend() async {
    setState(() => _resending = true);
    try {
      await ref.read(senderServiceProvider).resendLink(_order.id);
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text(tr('link_resent'))));
      }
    } catch (e) {
      if (mounted) ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text('${tr('failed_prefix')}: $e')));
    } finally {
      if (mounted) setState(() => _resending = false);
    }
  }

  @override
  void dispose() {
    _poll?.cancel();
    super.dispose();
  }

  List<StatusStep> _timelineSteps() {
    final o = _order;
    bool locDone = o.customerLat != null;
    bool payDone = o.paymentStatus == 'paid';
    bool dispatchReady = o.status == 'searching_driver' || driverActiveStatuses.contains(o.status);

    return [
      StatusStep(tr('link_delivered'), 'done'),
      StatusStep(tr('location_confirmed'), locDone ? 'done' : 'active'),
      StatusStep(tr('payment_completed'), payDone ? 'done' : (locDone ? 'active' : 'todo')),
      StatusStep(tr('ready_dispatch'), dispatchReady ? 'done' : 'todo'),
    ];
  }

  @override
  Widget build(BuildContext context) {
    final o = _order;
    return Scaffold(
      appBar: AppBar(title: Text(o.orderNo)),
      body: SafeArea(
        child: ListView(
          padding: const EdgeInsets.fromLTRB(16, 6, 16, 24),
          children: [
            Container(
              padding: const EdgeInsets.all(13),
              decoration: BoxDecoration(color: AppColors.ink, borderRadius: BorderRadius.circular(15)),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text(o.customerName, style: const TextStyle(color: Colors.white, fontSize: 15, fontWeight: FontWeight.w700)),
                  const SizedBox(height: 2),
                  Text('${o.customerPhone} · AED ${o.orderValue.toStringAsFixed(0)}',
                      style: const TextStyle(color: Color(0xFFB7BECC), fontSize: 11.5)),
                ],
              ),
            ),
            const SizedBox(height: 14),
            AppCard(
              child: Row(children: [
                Container(
                  width: 36, height: 36,
                  decoration: BoxDecoration(color: AppColors.greenSoft, borderRadius: BorderRadius.circular(10)),
                  child: const Icon(Icons.chat_bubble_outline, color: AppColors.green, size: 17),
                ),
                const SizedBox(width: 11),
                Expanded(
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Text(tr('link_sent'), style: const TextStyle(fontSize: 12.5, fontWeight: FontWeight.w700)),
                      Text(tr('customer_notified'), style: const TextStyle(fontSize: 11, color: AppColors.slate)),
                    ],
                  ),
                ),
                const CircleAvatar(radius: 13, backgroundColor: AppColors.green, child: Icon(Icons.check, size: 14, color: Colors.white)),
              ]),
            ),
            const SizedBox(height: 16),
            Text(tr('customer_progress'), style: T.title),
            const SizedBox(height: 10),
            StatusTimeline(steps: _timelineSteps()),
            const SizedBox(height: 6),
            Container(
              padding: const EdgeInsets.all(12),
              decoration: BoxDecoration(color: AppColors.amberSoft, borderRadius: BorderRadius.circular(12)),
              child: Row(children: [
                const Icon(Icons.schedule, size: 16, color: Color(0xFF9A6800)),
                const SizedBox(width: 8),
                Expanded(child: Text(tr('waiting_customer'),
                    style: const TextStyle(fontSize: 12, fontWeight: FontWeight.w600, color: Color(0xFF9A6800)))),
              ]),
            ),
            if (o.customerLink != null) ...[
              const SizedBox(height: 12),
              Text(tr('customer_link'),
                  style: const TextStyle(fontSize: 11.5, fontWeight: FontWeight.w700, color: AppColors.slate)),
              const SizedBox(height: 6),
              InkWell(
                onTap: _copyLink,
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
              const SizedBox(height: 12),
              LapehButton(label: tr('copy_link'), icon: Icons.copy_rounded, onPressed: _copyLink),
            ],
            const SizedBox(height: 10),
            _resending
                ? const Center(child: CircularProgressIndicator(color: AppColors.pink))
                : LapehButton(label: tr('resend_link'), ghost: true, icon: Icons.send_outlined, onPressed: _resend),
          ],
        ),
      ),
    );
  }
}
