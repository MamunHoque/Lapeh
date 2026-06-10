import 'package:flutter_riverpod/flutter_riverpod.dart';
import '../models/order_model.dart';
import '../services/driver_service.dart';

final driverServiceProvider = Provider((_) => DriverService());

// Driver online status
class DriverStatusNotifier extends StateNotifier<String> {
  DriverStatusNotifier() : super('offline');

  final _service = DriverService();

  Future<void> goOnline() async {
    await _service.updateStatus('online');
    state = 'online';
  }

  Future<void> goOffline() async {
    await _service.updateStatus('offline');
    state = 'offline';
  }

  void setOnDelivery() => state = 'on_delivery';

  /// Adopt backend-reported status (from /auth/me) without an API call.
  void sync(String status) => state = status;
}

final driverStatusProvider =
    StateNotifierProvider<DriverStatusNotifier, String>((_) => DriverStatusNotifier());

// Current pending offer
final currentOfferProvider = FutureProvider<DeliveryOffer?>((ref) async {
  return ref.read(driverServiceProvider).currentOffer();
});

// Current active order
final currentOrderProvider = FutureProvider<OrderModel?>((ref) async {
  return ref.read(driverServiceProvider).currentOrder();
});

// Earnings
class EarningsNotifier extends AsyncNotifier<({double today, List<dynamic> history})> {
  @override
  Future<({double today, List<dynamic> history})> build() =>
      ref.read(driverServiceProvider).earnings();

  Future<void> refresh() async {
    state = const AsyncLoading();
    state = await AsyncValue.guard(() => ref.read(driverServiceProvider).earnings());
  }
}

final earningsProvider =
    AsyncNotifierProvider<EarningsNotifier, ({double today, List<dynamic> history})>(
        EarningsNotifier.new);
