import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import '../../core/theme.dart';
import '../../core/i18n.dart';
import '../../core/models/driver_earnings_model.dart';
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
        data: (d) => _EarningsBody(d: d),
      ),
    );
  }
}

class _EarningsBody extends StatelessWidget {
  final DriverEarningsData d;
  const _EarningsBody({required this.d});

  @override
  Widget build(BuildContext context) {
    final delta = d.todayDeltaPct;
    final best = d.bestDay;
    return ListView(
      padding: const EdgeInsets.fromLTRB(16, 12, 16, 24),
      children: [
        Text(tr('earnings'), style: T.h1),
        const SizedBox(height: 14),

        // Hero — today's earnings
        Container(
          padding: const EdgeInsets.all(18),
          decoration: BoxDecoration(
            gradient: const LinearGradient(colors: [Color(0xFF2BD4A0), AppColors.green]),
            borderRadius: BorderRadius.circular(18),
          ),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Text(tr('today'),
                  style: const TextStyle(color: Colors.white70, fontSize: 12, fontWeight: FontWeight.w600)),
              const SizedBox(height: 4),
              Row(crossAxisAlignment: CrossAxisAlignment.baseline, textBaseline: TextBaseline.alphabetic, children: [
                Text(d.today.earnings.toStringAsFixed(2),
                    style: const TextStyle(color: Colors.white, fontSize: 32, fontWeight: FontWeight.w800)),
                const SizedBox(width: 6),
                const Text('AED', style: TextStyle(color: Colors.white70, fontSize: 14, fontWeight: FontWeight.w700)),
              ]),
              const SizedBox(height: 4),
              Text('${d.today.trips} ${tr('trips_today')}',
                  style: const TextStyle(color: Colors.white70, fontSize: 12)),
              if (delta != null) ...[
                const SizedBox(height: 8),
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

        // Period summaries
        Row(children: [
          Expanded(child: _StatCard(label: tr('this_week'), value: 'AED ${d.week.earnings.toStringAsFixed(0)}', sub: '${d.week.trips} ${tr('trips_unit')}')),
          const SizedBox(width: 8),
          Expanded(child: _StatCard(label: tr('this_month'), value: 'AED ${d.month.earnings.toStringAsFixed(0)}', sub: '${d.month.trips} ${tr('trips_unit')}')),
          const SizedBox(width: 8),
          Expanded(child: _StatCard(label: tr('all_time'), value: 'AED ${d.allTime.earnings.toStringAsFixed(0)}', sub: '${d.allTime.trips} ${tr('trips_unit')}')),
        ]),
        const SizedBox(height: 8),

        // Platform commission deducted (only when the admin charges drivers).
        if (d.allTime.commission > 0)
          Container(
            width: double.infinity,
            padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 12),
            decoration: BoxDecoration(color: AppColors.bg, borderRadius: BorderRadius.circular(12)),
            child: Row(
              mainAxisAlignment: MainAxisAlignment.spaceBetween,
              children: [
                Text(tr('platform_commission'), style: T.mutedSm),
                Text('− AED ${d.allTime.commission.toStringAsFixed(2)}',
                    style: T.mutedSm.copyWith(color: AppColors.red, fontWeight: FontWeight.w700)),
              ],
            ),
          ),
        if (d.allTime.commission > 0) const SizedBox(height: 8),

        // Secondary stats
        Row(children: [
          Expanded(child: _StatCard(
            label: tr('avg_per_trip'),
            value: 'AED ${d.week.avgEarning.toStringAsFixed(1)}',
            sub: tr('this_week'),
          )),
          const SizedBox(width: 8),
          Expanded(child: _StatCard(
            label: tr('total_distance'),
            value: '${d.week.distanceKm.toStringAsFixed(1)} ${tr('km_unit')}',
            sub: tr('this_week'),
          )),
          const SizedBox(width: 8),
          Expanded(child: _StatCard(
            label: tr('best_day'),
            value: best != null ? 'AED ${best.earnings.toStringAsFixed(0)}' : '–',
            sub: best != null ? _weekdayLabel(best.dateTime) : '—',
          )),
        ]),
        const SizedBox(height: 18),

        // Weekly chart
        SectionHeader(tr('weekly_overview')),
        const SizedBox(height: 12),
        AppCard(
          padding: const EdgeInsets.fromLTRB(8, 14, 8, 8),
          child: _WeeklyChart(d.dailyBreakdown),
        ),
        const SizedBox(height: 18),

        // Trip history
        SectionHeader(tr('recent_trips')),
        const SizedBox(height: 10),
        if (d.history.isEmpty)
          Padding(
            padding: const EdgeInsets.symmetric(vertical: 28),
            child: EmptyState(message: tr('no_earnings_yet'), icon: Icons.pedal_bike),
          )
        else
          ...d.history.map((t) => _TripRow(t)),
      ],
    );
  }
}

class _StatCard extends StatelessWidget {
  final String label, value, sub;
  const _StatCard({required this.label, required this.value, required this.sub});
  @override
  Widget build(BuildContext context) {
    return AppCard(
      padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 11),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Text(label, style: const TextStyle(fontSize: 10.5, color: AppColors.slate, fontWeight: FontWeight.w600)),
          const SizedBox(height: 4),
          Text(value, style: const TextStyle(fontSize: 15.5, fontWeight: FontWeight.w800), overflow: TextOverflow.ellipsis),
          const SizedBox(height: 1),
          Text(sub, style: const TextStyle(fontSize: 10, color: AppColors.slate2, fontWeight: FontWeight.w600), overflow: TextOverflow.ellipsis),
        ],
      ),
    );
  }
}

/// Proportional bar chart for the last 7 days. Today is highlighted.
class _WeeklyChart extends StatelessWidget {
  final List<DailyEarning> days;
  const _WeeklyChart(this.days);

  @override
  Widget build(BuildContext context) {
    final maxEarn = days.fold<double>(0, (m, d) => d.earnings > m ? d.earnings : m);
    const barAreaH = 96.0;
    return SizedBox(
      height: barAreaH + 38,
      child: Row(
        crossAxisAlignment: CrossAxisAlignment.end,
        children: days.asMap().entries.map((entry) {
          final i = entry.key;
          final day = entry.value;
          final isToday = i == days.length - 1;
          final ratio = maxEarn > 0 ? day.earnings / maxEarn : 0.0;
          final barH = day.earnings > 0 ? (barAreaH * ratio).clamp(8.0, barAreaH) : 3.0;
          return Expanded(
            child: Column(
              mainAxisAlignment: MainAxisAlignment.end,
              children: [
                SizedBox(
                  height: 14,
                  child: day.earnings > 0
                      ? FittedBox(child: Text(day.earnings.toStringAsFixed(0),
                          style: const TextStyle(fontSize: 9.5, color: AppColors.slate, fontWeight: FontWeight.w700)))
                      : null,
                ),
                const SizedBox(height: 3),
                Container(
                  height: barH,
                  margin: const EdgeInsets.symmetric(horizontal: 4),
                  decoration: BoxDecoration(
                    color: isToday ? AppColors.green : AppColors.greenSoft,
                    borderRadius: const BorderRadius.vertical(top: Radius.circular(5), bottom: Radius.circular(2)),
                  ),
                ),
                const SizedBox(height: 6),
                Text(
                  _weekdayLabel(day.dateTime),
                  style: TextStyle(
                    fontSize: 10,
                    color: isToday ? AppColors.green : AppColors.slate2,
                    fontWeight: isToday ? FontWeight.w800 : FontWeight.w600,
                  ),
                ),
              ],
            ),
          );
        }).toList(),
      ),
    );
  }
}

class _TripRow extends StatelessWidget {
  final TripEarning t;
  const _TripRow(this.t);

  @override
  Widget build(BuildContext context) {
    return Padding(
      padding: const EdgeInsets.only(bottom: 8),
      child: GestureDetector(
        onTap: () => _showDetail(context, t),
        child: AppCard(
          padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 12),
          child: Row(children: [
            Container(
              width: 38, height: 38,
              decoration: BoxDecoration(color: AppColors.greenSoft, borderRadius: BorderRadius.circular(11)),
              child: const Icon(Icons.check, color: AppColors.green, size: 18),
            ),
            const SizedBox(width: 11),
            Expanded(
              child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
                Text('${t.orderNo}${t.sender.isNotEmpty ? " · ${t.sender}" : ""}',
                    style: const TextStyle(fontSize: 13, fontWeight: FontWeight.w700), overflow: TextOverflow.ellipsis),
                Text(
                  [if (t.area.isNotEmpty) t.area, if (t.deliveredAt != null) _dateTimeLabel(t.deliveredAt!)].join(' · '),
                  style: T.mutedSm,
                  overflow: TextOverflow.ellipsis,
                ),
              ]),
            ),
            const SizedBox(width: 8),
            Text('+${t.earning.toStringAsFixed(2)}',
                style: const TextStyle(fontSize: 13.5, fontWeight: FontWeight.w800, color: AppColors.green)),
          ]),
        ),
      ),
    );
  }

  void _showDetail(BuildContext context, TripEarning t) {
    showModalBottomSheet(
      context: context,
      backgroundColor: Colors.white,
      shape: const RoundedRectangleBorder(borderRadius: BorderRadius.vertical(top: Radius.circular(24))),
      builder: (_) => SafeArea(
        child: Padding(
          padding: const EdgeInsets.fromLTRB(20, 14, 20, 22),
          child: Column(
            mainAxisSize: MainAxisSize.min,
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Center(
                child: Container(
                  width: 38, height: 4,
                  margin: const EdgeInsets.only(bottom: 16),
                  decoration: BoxDecoration(color: AppColors.line, borderRadius: BorderRadius.circular(2)),
                ),
              ),
              Text(tr('trip_detail'), style: T.h2),
              const SizedBox(height: 14),
              _DetailRow(tr('order_no'), t.orderNo),
              if (t.sender.isNotEmpty) _DetailRow(tr('sender'), t.sender),
              if (t.area.isNotEmpty) _DetailRow(tr('pickup_label'), t.area),
              if (t.distanceKm != null) _DetailRow(tr('total_distance'), '${t.distanceKm!.toStringAsFixed(1)} ${tr('km_unit')}'),
              if (t.deliveredAt != null) _DetailRow(tr('completed_label'), _dateTimeLabel(t.deliveredAt!)),
              const Divider(height: 26),
              Row(mainAxisAlignment: MainAxisAlignment.spaceBetween, children: [
                Text(tr('earnings'), style: const TextStyle(fontWeight: FontWeight.w700)),
                Text('AED ${t.earning.toStringAsFixed(2)}',
                    style: const TextStyle(fontSize: 17, fontWeight: FontWeight.w800, color: AppColors.green)),
              ]),
            ],
          ),
        ),
      ),
    );
  }
}

class _DetailRow extends StatelessWidget {
  final String label, value;
  const _DetailRow(this.label, this.value);
  @override
  Widget build(BuildContext context) {
    return Padding(
      padding: const EdgeInsets.symmetric(vertical: 5),
      child: Row(crossAxisAlignment: CrossAxisAlignment.start, children: [
        SizedBox(width: 110, child: Text(label, style: T.muted)),
        Expanded(child: Text(value, style: const TextStyle(fontSize: 13, fontWeight: FontWeight.w600))),
      ]),
    );
  }
}

const _weekdays = ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'];

String _weekdayLabel(DateTime? d) => d == null ? '' : _weekdays[d.weekday - 1];

String _dateTimeLabel(DateTime d) {
  final h = d.hour % 12 == 0 ? 12 : d.hour % 12;
  final m = d.minute.toString().padLeft(2, '0');
  final ap = d.hour < 12 ? 'AM' : 'PM';
  return '${d.day}/${d.month} $h:$m $ap';
}
