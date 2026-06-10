import '../api_client.dart';
import '../models/order_model.dart';

class RestaurantService {
  final _api = ApiClient();

  Future<({DashboardStats stats, List<OrderModel> activeDeliveries})> dashboard() async {
    final res = await _api.dio.get('/restaurant/dashboard');
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
    required double orderValue,
    int? prepTimeMin,
    String? notes,
  }) async {
    final res = await _api.dio.post('/restaurant/orders', data: {
      'customer_name': customerName,
      'customer_phone': customerPhone,
      'order_value': orderValue,
      if (prepTimeMin != null) 'prep_time_min': prepTimeMin,
      if (notes != null) 'notes': notes,
    });
    return (
      order: OrderModel.fromJson(res.data['order']),
      customerLink: res.data['customer_link'] as String,
    );
  }

  Future<List<OrderModel>> listOrders({String? status}) async {
    final res = await _api.dio.get('/restaurant/orders', queryParameters: {
      if (status != null) 'status': status,
    });
    final data = res.data['orders'];
    final items = data is Map ? data['data'] as List : data as List;
    return items.map((e) => OrderModel.fromJson(e)).toList();
  }

  Future<OrderModel> getOrder(int id) async {
    final res = await _api.dio.get('/restaurant/orders/$id');
    return OrderModel.fromJson(res.data['order']);
  }

  Future<void> resendLink(int orderId) async {
    await _api.dio.post('/restaurant/orders/$orderId/resend-link');
  }

  Future<void> cancelOrder(int orderId, {String? reason}) async {
    await _api.dio.post('/restaurant/orders/$orderId/cancel', data: {
      if (reason != null) 'reason': reason,
    });
  }

  Future<void> rateDriver(int orderId, {
    required int rating,
    List<String>? tags,
    String? comment,
  }) async {
    await _api.dio.post('/restaurant/orders/$orderId/rate-driver', data: {
      'rating': rating,
      if (tags != null) 'tags': tags,
      if (comment != null) 'comment': comment,
    });
  }

  Future<List<OrderModel>> history() async {
    final res = await _api.dio.get('/restaurant/history');
    final data = res.data['orders'];
    final items = data is Map ? data['data'] as List : data as List;
    return items.map((e) => OrderModel.fromJson(e)).toList();
  }

  Future<ReportData> reports() async {
    final res = await _api.dio.get('/restaurant/reports');
    return ReportData.fromJson(Map<String, dynamic>.from(res.data));
  }
}
