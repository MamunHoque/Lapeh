class NotificationModel {
  final int id;
  final String title;
  final String body;
  final Map<String, dynamic>? data;
  final bool read;
  final DateTime? createdAt;

  const NotificationModel({
    required this.id,
    required this.title,
    required this.body,
    this.data,
    this.read = false,
    this.createdAt,
  });

  factory NotificationModel.fromJson(Map<String, dynamic> j) => NotificationModel(
        id: j['id'],
        title: j['title'] ?? '',
        body: j['body'] ?? '',
        data: j['data'] is Map ? Map<String, dynamic>.from(j['data']) : null,
        read: j['read'] ?? false,
        createdAt: j['created_at'] != null ? DateTime.tryParse(j['created_at'].toString()) : null,
      );

  NotificationModel copyWith({bool? read}) => NotificationModel(
        id: id,
        title: title,
        body: body,
        data: data,
        read: read ?? this.read,
        createdAt: createdAt,
      );
}
