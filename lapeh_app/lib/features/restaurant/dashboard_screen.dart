import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import '../../core/theme.dart';
import '../../core/i18n.dart';
import '../../core/models/order_model.dart';
import '../../core/status_meta.dart';
import '../../core/providers/auth_provider.dart';
import '../../core/providers/restaurant_provider.dart';
import '../../shared/widgets.dart';
import 'create_request_screen.dart';
import 'tracking_screen.dart';

class DashboardScreen extends ConsumerWidget {
  const DashboardScreen({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final auth = ref.watch(authProvider).valueOrNull;
    final dashAsync = ref.watch(dashboardProvider);

    return RefreshIndicator(
      color: AppColors.pink,
      onRefresh: () => ref.read(dashboardProvider.notifier).refresh(),
      child: dashAsync.when(
        loading: () => ListView(children: const [
          SizedBox(height: 240),
          Center(child: CircularProgressIndicator(color: AppColors.pink)),
        ]),
        error: (e, _) => ListView(children: [
          const SizedBox(height: 180),
          ErrorRetry(error: e, onRetry: () => ref.read(dashboardProvider.notifier).refresh()),
        ]),
        data: (data) => ListView(
          padding: const EdgeInsets.fromLTRB(16, 10, 16, 24),
          children: [
            Row(
              children: [
                Expanded(
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Text(tr('good_evening'), style: const TextStyle(fontSize: 12.5, color: AppColors.slate, fontWeight: FontWeight.w600)),
                      Text(auth?.restaurant?.name ?? auth?.name ?? tr('restaurant_pickup'), style: T.h2),
                    ],
                  ),
                ),
                _bell(),
              ],
            ),
            const SizedBox(height: 16),
            Row(children: [
              Expanded(child: _MiniStat(label: tr('today'), value: '${data.stats.total}', sub: tr('deliveries'))),
              const SizedBox(width: 8),
              Expanded(child: _MiniStat(label: tr('revenue'), value: data.stats.revenue.toStringAsFixed(0), sub: tr('aed_fees'))),
              const SizedBox(width: 8),
              Expanded(child: _MiniStat(label: tr('active'), value: '${data.activeDeliveries.length}', sub: tr('on_the_road'))),
            ]),
            const SizedBox(height: 14),
            LapehButton(
              label: tr('new_delivery'),
              icon: Icons.add,
              onPressed: () => Navigator.push(context, MaterialPageRoute(builder: (_) => const CreateRequestScreen())),
            ),
            const SizedBox(height: 18),
            SectionHeader(tr('active_deliveries'), action: tr('see_all')),
            const SizedBox(height: 10),
            if (data.activeDeliveries.isEmpty)
              Center(
                child: Padding(
                  padding: const EdgeInsets.symmetric(vertical: 24),
                  child: Text(tr('no_active'), style: const TextStyle(color: AppColors.slate)),
                ),
              )
            else
              ...data.activeDeliveries.map((o) => Padding(
                padding: const EdgeInsets.only(bottom: 10),
                child: _DeliveryRow(order: o),
              )),
          ],
        ),
      ),
    );
  }

  Widget _bell() => Container(
        width: 40, height: 40,
        decoration: BoxDecoration(color: Colors.white, borderRadius: BorderRadius.circular(12), border: Border.all(color: AppColors.line)),
        child: const Icon(Icons.notifications_none, color: AppColors.ink, size: 20),
      );
}

class _MiniStat extends StatelessWidget {
  final String label, value, sub;
  const _MiniStat({required this.label, required this.value, required this.sub});
  @override
  Widget build(BuildContext context) {
    return AppCard(
      padding: const EdgeInsets.fromLTRB(11, 11, 11, 12),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Text(label, style: const TextStyle(fontSize: 10.5, color: AppColors.slate, fontWeight: FontWeight.w600)),
          const SizedBox(height: 2),
          Text(value, style: const TextStyle(fontSize: 22, fontWeight: FontWeight.w800)),
          Text(sub, style: const TextStyle(fontSize: 10, color: AppColors.slate2)),
        ],
      ),
    );
  }
}

class _DeliveryRow extends StatelessWidget {
  final OrderModel order;
  const _DeliveryRow({required this.order});

  @override
  Widget build(BuildContext context) {
    final isOnWay = order.status == 'on_the_way' || order.status == 'picked_up';
    final isPending = customerPendingStatuses.contains(order.status);
    final icon = isOnWay
        ? Icons.pedal_bike
        : isPending
            ? Icons.schedule
            : Icons.location_on_outlined;
    final (bg, fg) = isOnWay
        ? (AppColors.blueSoft, AppColors.blue)
        : isPending
            ? (AppColors.amberSoft, AppColors.amber)
            : (AppColors.pinkSoft, AppColors.pink);

    return GestureDetector(
      onTap: () => Navigator.push(context, MaterialPageRoute(builder: (_) => TrackingScreen(order: order))),
      child: AppCard(
        padding: const EdgeInsets.all(12),
        child: Row(children: [
          Container(
            width: 40, height: 40,
            decoration: BoxDecoration(color: bg, borderRadius: BorderRadius.circular(11)),
            child: Icon(icon, color: fg, size: 19),
          ),
          const SizedBox(width: 11),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text('${order.orderNo} · ${order.customerName}', style: const TextStyle(fontSize: 13, fontWeight: FontWeight.w700)),
                Text(
                  order.driver != null
                      ? '${tr('driver')} ${order.driver!.name}${order.distanceKm != null ? " · ${order.distanceKm!.toStringAsFixed(1)} km" : ""}'
                      : (order.status == 'searching_driver' ? tr('track_searching') : tr('waiting_for_customer')),
                  style: T.mutedSm,
                ),
              ],
            ),
          ),
          if (order.customerLink != null) CopyLinkIcon(link: order.customerLink!),
          StatusBadge(status: order.status),
        ]),
      ),
    );
  }
}
