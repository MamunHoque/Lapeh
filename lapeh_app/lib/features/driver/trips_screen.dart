import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import '../../core/theme.dart';
import '../../core/i18n.dart';
import '../../core/models/order_model.dart' show asDouble;
import '../../core/providers/driver_provider.dart';
import '../../shared/widgets.dart';

class TripsScreen extends ConsumerWidget {
  const TripsScreen({super.key});

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
      data: (e) {
        final trips = e.history;
        return ListView(
          padding: const EdgeInsets.fromLTRB(16, 12, 16, 24),
          children: [
            Text(tr('trips'), style: T.h1),
            const SizedBox(height: 4),
            Text('${tr('today')} · ${trips.length} ${tr('completed_label')}', style: T.muted),
            const SizedBox(height: 14),
            if (trips.isEmpty)
              EmptyState(message: tr('no_trips'), icon: Icons.pedal_bike)
            else
              ...trips.map((t) {
                final trip = t as Map<String, dynamic>;
                final fee = asDouble(trip['delivery_fee']) ?? 0;
                return Padding(
                  padding: const EdgeInsets.only(bottom: 10),
                  child: AppCard(
                    child: Row(children: [
                      Container(
                        width: 40, height: 40,
                        decoration: BoxDecoration(color: AppColors.greenSoft, borderRadius: BorderRadius.circular(11)),
                        child: const Icon(Icons.check, color: AppColors.green, size: 19),
                      ),
                      const SizedBox(width: 11),
                      Expanded(
                        child: Column(
                          crossAxisAlignment: CrossAxisAlignment.start,
                          children: [
                            Text(trip['order_no'] ?? '–', style: const TextStyle(fontSize: 13, fontWeight: FontWeight.w700)),
                            Text(trip['customer_address'] ?? trip['customer_name'] ?? '', style: T.mutedSm),
                          ],
                        ),
                      ),
                      Text('+${fee.toStringAsFixed(2)}',
                          style: const TextStyle(fontSize: 13.5, fontWeight: FontWeight.w800, color: AppColors.green)),
                    ]),
                  ),
                );
              }),
          ],
        );
      },
      ),
    );
  }
}
