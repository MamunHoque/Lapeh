import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import '../../core/theme.dart';
import '../../core/i18n.dart';
import '../../core/models/notification_model.dart';
import '../../core/providers/notification_provider.dart';
import '../../shared/widgets.dart';

class NotificationsScreen extends ConsumerWidget {
  const NotificationsScreen({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final async = ref.watch(notificationsProvider);
    final hasUnread = (async.valueOrNull ?? const []).any((n) => !n.read);

    return Scaffold(
      appBar: AppBar(
        title: Text(tr('notifications')),
        actions: [
          if (hasUnread)
            TextButton(
              onPressed: () => ref.read(notificationsProvider.notifier).markAllRead(),
              child: Text(tr('mark_all_read'), style: const TextStyle(color: AppColors.pink, fontWeight: FontWeight.w700, fontSize: 12.5)),
            ),
        ],
      ),
      body: RefreshIndicator(
        color: AppColors.pink,
        onRefresh: () => ref.read(notificationsProvider.notifier).refresh(),
        child: async.when(
          loading: () => const CustomScrollView(
            physics: AlwaysScrollableScrollPhysics(),
            slivers: [SliverFillRemaining(hasScrollBody: false, child: Center(child: CircularProgressIndicator(color: AppColors.pink)))],
          ),
          error: (e, _) => CustomScrollView(
            physics: const AlwaysScrollableScrollPhysics(),
            slivers: [SliverFillRemaining(hasScrollBody: false, child: ErrorRetry(error: e, onRetry: () => ref.read(notificationsProvider.notifier).refresh()))],
          ),
          data: (items) {
            if (items.isEmpty) {
              return CustomScrollView(
                physics: const AlwaysScrollableScrollPhysics(),
                slivers: [
                  SliverFillRemaining(
                    hasScrollBody: false,
                    child: EmptyState(message: tr('no_notifications'), icon: Icons.notifications_none),
                  ),
                ],
              );
            }
            return ListView.separated(
              padding: const EdgeInsets.fromLTRB(16, 12, 16, 24),
              itemCount: items.length,
              separatorBuilder: (_, __) => const SizedBox(height: 8),
              itemBuilder: (_, i) => _NotificationRow(
                n: items[i],
                onTap: () {
                  if (!items[i].read) ref.read(notificationsProvider.notifier).markRead(items[i].id);
                },
              ),
            );
          },
        ),
      ),
    );
  }
}

class _NotificationRow extends StatelessWidget {
  final NotificationModel n;
  final VoidCallback onTap;
  const _NotificationRow({required this.n, required this.onTap});

  @override
  Widget build(BuildContext context) {
    return GestureDetector(
      onTap: onTap,
      child: AppCard(
        padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 13),
        child: Row(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Container(
              width: 36, height: 36,
              decoration: BoxDecoration(
                color: n.read ? AppColors.line : AppColors.pinkSoft,
                borderRadius: BorderRadius.circular(10),
              ),
              child: Icon(Icons.notifications, size: 18, color: n.read ? AppColors.slate2 : AppColors.pink),
            ),
            const SizedBox(width: 12),
            Expanded(
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text(n.title, style: TextStyle(fontSize: 13.5, fontWeight: n.read ? FontWeight.w600 : FontWeight.w800)),
                  const SizedBox(height: 2),
                  Text(n.body, style: T.mutedSm),
                  if (n.createdAt != null) ...[
                    const SizedBox(height: 4),
                    Text(_timeAgo(n.createdAt!), style: const TextStyle(fontSize: 10.5, color: AppColors.slate2, fontWeight: FontWeight.w600)),
                  ],
                ],
              ),
            ),
            if (!n.read)
              Container(
                margin: const EdgeInsets.only(top: 4, left: 6),
                width: 8, height: 8,
                decoration: const BoxDecoration(color: AppColors.pink, shape: BoxShape.circle),
              ),
          ],
        ),
      ),
    );
  }
}

String _timeAgo(DateTime dt) {
  final diff = DateTime.now().difference(dt);
  if (diff.inMinutes < 1) return tr('just_now');
  if (diff.inMinutes < 60) return '${diff.inMinutes}m';
  if (diff.inHours < 24) return '${diff.inHours}h';
  if (diff.inDays < 7) return '${diff.inDays}d';
  return '${dt.day}/${dt.month}/${dt.year}';
}
