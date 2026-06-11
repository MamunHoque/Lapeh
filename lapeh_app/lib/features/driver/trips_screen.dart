import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import '../../core/theme.dart';
import '../../core/i18n.dart';
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
      loading: () => const CustomScrollView(
        physics: AlwaysScrollableScrollPhysics(),
        slivers: [
          SliverFillRemaining(
            hasScrollBody: false,
            child: Center(child: CircularProgressIndicator(color: AppColors.pink)),
          ),
        ],
      ),
      error: (e, _) => CustomScrollView(
        physics: const AlwaysScrollableScrollPhysics(),
        slivers: [
          SliverFillRemaining(
            hasScrollBody: false,
            child: ErrorRetry(error: e, onRetry: () => ref.read(earningsProvider.notifier).refresh()),
          ),
        ],
      ),
      data: (e) {
        final trips = e.history;
        final header = Padding(
          padding: const EdgeInsets.fromLTRB(16, 12, 16, 14),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Text(tr('trips'), style: T.h1),
              const SizedBox(height: 4),
              Text('${tr('today')} · ${e.today.trips} ${tr('completed_label')}', style: T.muted),
            ],
          ),
        );
        if (trips.isEmpty) {
          return Column(
            children: [
              header,
              Expanded(
                child: CustomScrollView(
                  physics: const AlwaysScrollableScrollPhysics(),
                  slivers: [
                    SliverFillRemaining(
                      hasScrollBody: false,
                      child: EmptyState(message: tr('no_trips'), icon: Icons.pedal_bike),
                    ),
                  ],
                ),
              ),
            ],
          );
        }
        return ListView(
          padding: const EdgeInsets.fromLTRB(16, 12, 16, 24),
          children: [
            Text(tr('trips'), style: T.h1),
            const SizedBox(height: 4),
            Text('${tr('today')} · ${trips.length} ${tr('completed_label')}', style: T.muted),
            const SizedBox(height: 14),
            ...trips.map((trip) {
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
                            Text(trip.orderNo, style: const TextStyle(fontSize: 13, fontWeight: FontWeight.w700)),
                            Text(trip.area.isNotEmpty ? trip.area : trip.sender, style: T.mutedSm),
                          ],
                        ),
                      ),
                      Text('+${trip.earning.toStringAsFixed(2)}',
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
