import 'package:flutter_riverpod/flutter_riverpod.dart';
import '../models/notification_model.dart';
import '../services/notification_service.dart';
import '../services/meta_service.dart';

final notificationServiceProvider = Provider((_) => NotificationService());

/// Unread badge count for the dashboard bell / profile row.
class UnreadCountNotifier extends AsyncNotifier<int> {
  @override
  Future<int> build() => ref.read(notificationServiceProvider).unreadCount();

  Future<void> refresh() async {
    state = await AsyncValue.guard(() => ref.read(notificationServiceProvider).unreadCount());
  }
}

final unreadCountProvider = AsyncNotifierProvider<UnreadCountNotifier, int>(UnreadCountNotifier.new);

/// Full notification list with read mutations.
class NotificationsNotifier extends AsyncNotifier<List<NotificationModel>> {
  @override
  Future<List<NotificationModel>> build() => ref.read(notificationServiceProvider).list();

  Future<void> refresh() async {
    state = const AsyncLoading();
    state = await AsyncValue.guard(() => ref.read(notificationServiceProvider).list());
  }

  Future<void> markRead(int id) async {
    final current = state.valueOrNull;
    if (current == null) return;
    // Optimistic update; backend call follows.
    state = AsyncData([
      for (final n in current) n.id == id ? n.copyWith(read: true) : n,
    ]);
    try {
      await ref.read(notificationServiceProvider).markRead(id);
    } catch (_) {}
    ref.read(unreadCountProvider.notifier).refresh();
  }

  Future<void> markAllRead() async {
    final current = state.valueOrNull;
    if (current == null) return;
    state = AsyncData([for (final n in current) n.copyWith(read: true)]);
    try {
      await ref.read(notificationServiceProvider).markAllRead();
    } catch (_) {}
    ref.read(unreadCountProvider.notifier).refresh();
  }
}

final notificationsProvider =
    AsyncNotifierProvider<NotificationsNotifier, List<NotificationModel>>(NotificationsNotifier.new);

/// Support contact channels + FAQ (Help screen).
final supportProvider = FutureProvider<SupportInfo>((ref) async {
  return MetaService().support();
});
