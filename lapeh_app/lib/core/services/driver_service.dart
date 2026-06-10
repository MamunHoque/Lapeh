import 'package:dio/dio.dart';
import '../api_client.dart';
import '../models/order_model.dart';

class DriverService {
  final _api = ApiClient();

  Future<void> updateStatus(String status) async {
    await _api.dio.patch('/driver/status', data: {'status': status});
  }

  Future<void> pushLocation(double lat, double lng) async {
    await _api.dio.post('/driver/location', data: {'lat': lat, 'lng': lng});
  }

  Future<DeliveryOffer?> currentOffer() async {
    final res = await _api.dio.get('/driver/offers/current');
    final data = res.data['offer'];
    return data != null ? DeliveryOffer.fromJson(data) : null;
  }

  Future<OrderModel> acceptOffer(int offerId) async {
    final res = await _api.dio.post('/driver/offers/$offerId/accept');
    return OrderModel.fromJson(res.data['order']);
  }

  Future<void> rejectOffer(int offerId) async {
    await _api.dio.post('/driver/offers/$offerId/reject');
  }

  Future<OrderModel?> currentOrder() async {
    final res = await _api.dio.get('/driver/orders/current');
    final data = res.data['order'];
    return data != null ? OrderModel.fromJson(data) : null;
  }

  Future<void> updateOrderStatus(int orderId, String status) async {
    await _api.dio.post('/driver/orders/$orderId/status', data: {'status': status});
  }

  Future<void> deliver(int orderId, {
    required String otp,
    String? photoPath,
  }) async {
    final map = <String, dynamic>{'otp': otp};
    if (photoPath != null) {
      map['photo'] = await MultipartFile.fromFile(photoPath, filename: 'proof.jpg');
    }
    await _api.dio.post(
      '/driver/orders/$orderId/deliver',
      data: FormData.fromMap(map),
      options: Options(contentType: 'multipart/form-data'),
    );
  }

  Future<({double today, List<dynamic> history})> earnings() async {
    final res = await _api.dio.get('/driver/earnings');
    final histData = res.data['history'];
    final List<dynamic> trips = histData is Map ? (histData['data'] as List? ?? []) : (histData as List? ?? []);
    return (
      today: asDouble(res.data['today']) ?? 0,
      history: trips,
    );
  }
}
