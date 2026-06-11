import '../api_client.dart';
import '../models/order_model.dart';
import '../models/user_model.dart';

class SenderService {
  final _api = ApiClient();

  Future<({DashboardStats stats, List<OrderModel> activeDeliveries})> dashboard() async {
    final res = await _api.dio.get('/sender/dashboard');
    return (
      stats: DashboardStats.fromJson(res.data['stats']),
      activeDeliveries: (res.data['active_deliveries'] as List)
          .map((e) => OrderModel.fromJson(e))
          .toList(),
    );
  }

  Future<({OrderModel order, String customerLink})> createOrder({
    required String customerName,
    required String customerPhone,
    required List<OrderItem> items,
    String? pickupAddress,
    double? pickupLat,
    double? pickupLng,
    String? notes,
  }) async {
    final res = await _api.dio.post('/sender/orders', data: {
      'customer_name': customerName,
      'customer_phone': customerPhone,
      'items': items.map((i) => i.toJson()).toList(),
      if (pickupAddress != null) 'pickup_address': pickupAddress,
      if (pickupLat != null) 'pickup_lat': pickupLat,
      if (pickupLng != null) 'pickup_lng': pickupLng,
      if (notes != null) 'notes': notes,
    });
    return (
      order: OrderModel.fromJson(res.data['order']),
      customerLink: res.data['customer_link'] as String,
    );
  }

  Future<UserModel> updateProfile({
    String? name,
    String? defaultPickupAddress,
    double? defaultPickupLat,
    double? defaultPickupLng,
    String? businessName,
    String? businessCategory,
    String? contactPersonName,
  }) async {
    final res = await _api.dio.patch('/sender/profile', data: {
      if (name != null) 'name': name,
      if (defaultPickupAddress != null) 'default_pickup_address': defaultPickupAddress,
      if (defaultPickupLat != null) 'default_pickup_lat': defaultPickupLat,
      if (defaultPickupLng != null) 'default_pickup_lng': defaultPickupLng,
      if (businessName != null) 'business_name': businessName,
      if (businessCategory != null) 'business_category': businessCategory,
      if (contactPersonName != null) 'contact_person_name': contactPersonName,
    });
    return UserModel.fromJson(res.data['user']);
  }

  Future<List<OrderModel>> listOrders({String? status}) async {
    final res = await _api.dio.get('/sender/orders', queryParameters: {
      if (status != null) 'status': status,
    });
    final data = res.data['orders'];
    final items = data is Map ? data['data'] as List : data as List;
    return items.map((e) => OrderModel.fromJson(e)).toList();
  }

  Future<OrderModel> getOrder(int id) async {
    final res = await _api.dio.get('/sender/orders/$id');
    return OrderModel.fromJson(res.data['order']);
  }

  Future<void> resendLink(int orderId) async {
    await _api.dio.post('/sender/orders/$orderId/resend-link');
  }

  Future<void> cancelOrder(int orderId, {String? reason}) async {
    await _api.dio.post('/sender/orders/$orderId/cancel', data: {
      if (reason != null) 'reason': reason,
    });
  }

  Future<void> rateDriver(int orderId, {
    required int rating,
    List<String>? tags,
    String? comment,
  }) async {
    await _api.dio.post('/sender/orders/$orderId/rate-driver', data: {
      'rating': rating,
      if (tags != null) 'tags': tags,
      if (comment != null) 'comment': comment,
    });
  }

  Future<List<OrderModel>> history() async {
    final res = await _api.dio.get('/sender/history');
    final data = res.data['orders'];
    final items = data is Map ? data['data'] as List : data as List;
    return items.map((e) => OrderModel.fromJson(e)).toList();
  }

  Future<ReportData> reports() async {
    final res = await _api.dio.get('/sender/reports');
    return ReportData.fromJson(Map<String, dynamic>.from(res.data));
  }
}
