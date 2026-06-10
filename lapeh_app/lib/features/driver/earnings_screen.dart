import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import '../../core/theme.dart';
import '../../core/i18n.dart';
import '../../core/models/order_model.dart' show asDouble;
import '../../core/providers/driver_provider.dart';
import '../../shared/widgets.dart';

class EarningsScreen extends ConsumerWidget {
  const EarningsScreen({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final earningsAsync = ref.watch(earningsProvider);

    return RefreshIndicator(
      color: AppColors.pink,
      onRefresh: () => ref.read(earningsProvider.notifier).refresh(),
      child: earningsAsync.when(
      loading: () => ListView(children: const [
        SizedBox(height: 240),
        Center(child: CircularProgressIndicator(color: AppColors.pink)),
      ]),
      error: (e, _) => ListView(children: [
        const SizedBox(height: 180),
        ErrorRetry(error: e, onRetry: () => ref.read(earningsProvider.notifier).refresh()),
      ]),
      data: (e) => ListView(
        padding: const EdgeInsets.fromLTRB(16, 12, 16, 24),
        children: [
          Text(tr('earnings'), style: T.h1),
          const SizedBox(height: 14),
          Container(
            padding: const EdgeInsets.all(18),
            decoration: BoxDecoration(
              gradient: const LinearGradient(colors: [Color(0xFF2BD4A0), AppColors.green]),
              borderRadius: BorderRadius.circular(18),
            ),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(tr('today'), style: const TextStyle(color: Colors.white70, fontSize: 12, fontWeight: FontWeight.w600)),
                const SizedBox(height: 4),
                Text('AED ${e.today.toStringAsFixed(2)}',
                    style: const TextStyle(color: Colors.white, fontSize: 32, fontWeight: FontWeight.w800)),
                const SizedBox(height: 4),
                Text('${e.history.length} ${tr('trips_today')}',
                    style: const TextStyle(color: Colors.white70, fontSize: 12)),
              ],
            ),
          ),
          const SizedBox(height: 18),
          if (e.history.isNotEmpty) ...[
            SectionHeader(tr('recent_trips')),
            const SizedBox(height: 10),
            ...e.history.take(20).map((t) {
              final trip = t as Map<String, dynamic>;
              final fee = asDouble(trip['delivery_fee']) ?? 0;
              return Padding(
                padding: const EdgeInsets.only(bottom: 8),
                child: AppCard(
                  padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 12),
                  child: Row(children: [
                    Expanded(
                      child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
                        Text(trip['order_no'] ?? '–', style: const TextStyle(fontSize: 13, fontWeight: FontWeight.w700)),
                        Text(trip['customer_address'] ?? trip['status'] ?? '', style: T.mutedSm),
                      ]),
                    ),
                    Text('AED ${fee.toStringAsFixed(2)}',
                        style: const TextStyle(fontSize: 13, fontWeight: FontWeight.w700, color: AppColors.green)),
                  ]),
                ),
              );
            }),
          ],
        ],
      ),
      ),
    );
  }
}
