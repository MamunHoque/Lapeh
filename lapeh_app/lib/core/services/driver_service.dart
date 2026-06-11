import 'package:dio/dio.dart';
import '../api_client.dart';
import '../models/order_model.dart';
import '../models/driver_earnings_model.dart';

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
    List<int>? photoBytes,
  }) async {
    final map = <String, dynamic>{'otp': otp};
    if (photoBytes != null) {
      // Bytes work on every platform (web has no dart:io File). Set the
      // content type explicitly so server-side mimes:jpg validation passes.
      map['photo'] = MultipartFile.fromBytes(
        photoBytes,
        filename: 'proof.jpg',
        contentType: DioMediaType('image', 'jpeg'),
      );
    }
    await _api.dio.post(
      '/driver/orders/$orderId/deliver',
      data: FormData.fromMap(map),
      options: Options(contentType: 'multipart/form-data'),
    );
  }

  Future<DriverEarningsData> earnings() async {
    final res = await _api.dio.get('/driver/earnings');
    return DriverEarningsData.fromJson(Map<String, dynamic>.from(res.data as Map));
  }
}
