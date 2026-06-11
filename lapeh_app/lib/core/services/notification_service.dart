import '../api_client.dart';
import '../models/notification_model.dart';

class NotificationService {
  final _api = ApiClient();

  Future<List<NotificationModel>> list() async {
    final res = await _api.dio.get('/notifications');
    final data = res.data['notifications'];
    final items = data is Map ? (data['data'] as List? ?? []) : (data as List? ?? []);
    return items.map((e) => NotificationModel.fromJson(Map<String, dynamic>.from(e))).toList();
  }

  Future<int> unreadCount() async {
    final res = await _api.dio.get('/notifications/unread-count');
    return (res.data['count'] as num?)?.toInt() ?? 0;
  }

  Future<void> markRead(int id) async {
    await _api.dio.patch('/notifications/$id/read');
  }

  Future<void> markAllRead() async {
    await _api.dio.post('/notifications/read-all');
  }
}
