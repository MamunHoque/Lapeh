import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import '../../core/theme.dart';
import '../../core/i18n.dart';
import '../../core/providers/sender_provider.dart';
import '../../shared/widgets.dart';
import 'tracking_screen.dart';

class DeliveriesScreen extends ConsumerWidget {
  const DeliveriesScreen({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    return DefaultTabController(
      length: 2,
      child: Column(
        children: [
          Padding(
            padding: const EdgeInsets.fromLTRB(16, 12, 16, 0),
            child: Align(alignment: Alignment.centerLeft, child: Text(tr('deliveries'), style: T.h1)),
          ),
          TabBar(
            labelColor: AppColors.pink,
            unselectedLabelColor: AppColors.slate,
            indicatorColor: AppColors.pink,
            labelStyle: const TextStyle(fontWeight: FontWeight.w700, fontSize: 13.5),
            tabs: [Tab(text: tr('active')), Tab(text: tr('history'))],
          ),
          Expanded(
            child: TabBarView(children: const [
              _OrderList(status: null, activeOnly: true),
              _OrderList(status: 'delivered', activeOnly: false),
            ]),
          ),
        ],
      ),
    );
  }
}

class _OrderList extends ConsumerWidget {
  final String? status;
  final bool activeOnly;
  const _OrderList({required this.status, required this.activeOnly});

  void _refresh(WidgetRef ref) {
    ref.invalidate(activeOnly ? ordersProvider(null) : historyProvider);
  }

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final ordersAsync = activeOnly
        ? ref.watch(ordersProvider(null))
        : ref.watch(historyProvider);

    return RefreshIndicator(
      color: AppColors.pink,
      onRefresh: () async => _refresh(ref),
      child: ordersAsync.when(
      loading: () => ListView(children: const [
        SizedBox(height: 220),
        Center(child: CircularProgressIndicator(color: AppColors.pink)),
      ]),
      error: (e, _) => ListView(children: [
        const SizedBox(height: 160),
        ErrorRetry(error: e, onRetry: () => _refresh(ref)),
      ]),
      data: (orders) {
        final filtered = activeOnly
            ? orders.where((o) => !o.isTerminal).toList()
            : orders;
        if (filtered.isEmpty) {
          return ListView(children: [
            const SizedBox(height: 60),
            EmptyState(message: tr('no_orders')),
          ]);
        }
        return ListView.separated(
          padding: const EdgeInsets.all(16),
          itemCount: filtered.length,
          separatorBuilder: (_, __) => const SizedBox(height: 10),
          itemBuilder: (_, i) {
            final o = filtered[i];
            return GestureDetector(
              onTap: () => Navigator.push(context, MaterialPageRoute(builder: (_) => TrackingScreen(order: o))),
              child: AppCard(
                child: Row(children: [
                  Expanded(
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Text('${o.orderNo} · ${o.customerName}', style: const TextStyle(fontSize: 13.5, fontWeight: FontWeight.w700)),
                        const SizedBox(height: 2),
                        Text(o.driver != null ? '${tr('driver')} ${o.driver!.name}' : tr('no_driver_yet'), style: T.mutedSm),
                      ],
                    ),
                  ),
                  if (o.customerLink != null) CopyLinkIcon(link: o.customerLink!),
                  Column(
                    crossAxisAlignment: CrossAxisAlignment.end,
                    children: [
                      StatusBadge(status: o.status),
                      const SizedBox(height: 5),
                      if (o.deliveryFee != null && o.deliveryFee! > 0)
                        Text('AED ${o.deliveryFee!.toStringAsFixed(2)}',
                            style: const TextStyle(fontSize: 12, fontWeight: FontWeight.w700)),
                    ],
                  ),
                ]),
              ),
            );
          },
        );
      },
      ),
    );
  }
}
