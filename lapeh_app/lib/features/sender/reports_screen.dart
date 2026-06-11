import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import '../../core/theme.dart';
import '../../core/i18n.dart';
import '../../core/models/order_model.dart';
import '../../core/providers/sender_provider.dart';
import '../../shared/widgets.dart';
import 'tracking_screen.dart';

class ReportsScreen extends ConsumerWidget {
  const ReportsScreen({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final reportsAsync = ref.watch(reportsProvider);

    return RefreshIndicator(
      color: AppColors.pink,
      onRefresh: () async => ref.invalidate(reportsProvider),
      child: reportsAsync.when(
        loading: () => ListView(children: const [
          SizedBox(height: 240),
          Center(child: CircularProgressIndicator(color: AppColors.pink)),
        ]),
        error: (e, _) => ListView(children: [
          const SizedBox(height: 180),
          ErrorRetry(error: e, onRetry: () => ref.invalidate(reportsProvider)),
        ]),
        data: (r) => _ReportsBody(r: r),
      ),
    );
  }
}

class _ReportsBody extends StatelessWidget {
  final ReportData r;
  const _ReportsBody({required this.r});

  @override
  Widget build(BuildContext context) {
    final delta = r.revenueDeltaPct;
    return ListView(
      padding: const EdgeInsets.fromLTRB(16, 12, 16, 24),
      children: [
        Text(tr('reports'), style: T.h1),
        const SizedBox(height: 14),
        Container(
          padding: const EdgeInsets.all(16),
          decoration: BoxDecoration(
            gradient: const LinearGradient(colors: [Color(0xFFFF3A8D), AppColors.pinkDeep]),
            borderRadius: BorderRadius.circular(18),
          ),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Text(tr('revenue_today'),
                  style: const TextStyle(color: Colors.white70, fontSize: 11.5, fontWeight: FontWeight.w600)),
              const SizedBox(height: 4),
              Row(crossAxisAlignment: CrossAxisAlignment.baseline, textBaseline: TextBaseline.alphabetic, children: [
                Text(r.revenue.toStringAsFixed(2),
                    style: const TextStyle(color: Colors.white, fontSize: 30, fontWeight: FontWeight.w800)),
                const SizedBox(width: 6),
                const Text('AED', style: TextStyle(color: Colors.white70, fontSize: 14, fontWeight: FontWeight.w700)),
              ]),
              if (delta != null) ...[
                const SizedBox(height: 6),
                Container(
                  padding: const EdgeInsets.symmetric(horizontal: 9, vertical: 3),
                  decoration: BoxDecoration(color: Colors.white24, borderRadius: BorderRadius.circular(999)),
                  child: Text(
                    '${delta >= 0 ? "▲" : "▼"} ${delta.abs().toStringAsFixed(0)}% ${tr('vs_yesterday')}',
                    style: const TextStyle(color: Colors.white, fontSize: 11, fontWeight: FontWeight.w700),
                  ),
                ),
              ],
            ],
          ),
        ),
        const SizedBox(height: 14),
        Row(children: [
          Expanded(child: _Mini(tr('orders_label'), '${r.orders}')),
          const SizedBox(width: 8),
          Expanded(child: _Mini(tr('completed'), '${r.delivered}')),
          const SizedBox(width: 8),
          Expanded(child: _Mini(tr('avg_fee'), '${r.avgFee.toStringAsFixed(0)} AED')),
        ]),
        if (r.commission > 0) ...[
          const SizedBox(height: 8),
          _Mini(tr('commission_charged'), '${r.commission.toStringAsFixed(2)} AED'),
        ],
        const SizedBox(height: 18),
        SectionHeader(tr('recent')),
        const SizedBox(height: 10),
        if (r.recent.isEmpty)
          EmptyState(message: tr('no_recent'))
        else
          ...r.recent.map((row) => Padding(
                padding: const EdgeInsets.only(bottom: 10),
                child: GestureDetector(
                  onTap: () => Navigator.push(context, MaterialPageRoute(
                    builder: (_) => TrackingScreen(order: _stubOrder(row)),
                  )),
                  child: AppCard(
                    child: Row(children: [
                      Expanded(
                        child: Column(
                          crossAxisAlignment: CrossAxisAlignment.start,
                          children: [
                            Text('${row.orderNo} · ${row.customerName}',
                                style: const TextStyle(fontSize: 13, fontWeight: FontWeight.w700),
                                overflow: TextOverflow.ellipsis),
                            if (row.deliveryFee > 0)
                              Text('AED ${row.deliveryFee.toStringAsFixed(2)}', style: T.mutedSm),
                          ],
                        ),
                      ),
                      StatusBadge(status: row.status),
                    ]),
                  ),
                ),
              )),
      ],
    );
  }

  // Minimal order so tapping a report row opens tracking (it refetches by id).
  OrderModel _stubOrder(ReportRow row) => OrderModel(
        id: row.id,
        orderNo: row.orderNo,
        customerName: row.customerName,
        customerPhone: '',
        status: row.status,
        paymentStatus: 'paid',
        orderValue: 0,
        deliveryFee: row.deliveryFee,
        createdAt: DateTime.now(),
      );
}

class _Mini extends StatelessWidget {
  final String label, value;
  const _Mini(this.label, this.value);
  @override
  Widget build(BuildContext context) {
    return AppCard(
      padding: const EdgeInsets.all(11),
      child: Column(children: [
        Text(label, style: const TextStyle(fontSize: 10.5, color: AppColors.slate, fontWeight: FontWeight.w600)),
        const SizedBox(height: 2),
        Text(value, style: const TextStyle(fontSize: 18, fontWeight: FontWeight.w800)),
      ]),
    );
  }
}
