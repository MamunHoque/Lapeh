import 'package:flutter_riverpod/flutter_riverpod.dart';
import '../models/order_model.dart';
import '../services/restaurant_service.dart';

final restaurantServiceProvider = Provider((_) => RestaurantService());

// Dashboard state
class DashboardNotifier extends AsyncNotifier<({DashboardStats stats, List<OrderModel> activeDeliveries})> {
  @override
  Future<({DashboardStats stats, List<OrderModel> activeDeliveries})> build() =>
      ref.read(restaurantServiceProvider).dashboard();

  Future<void> refresh() async {
    state = const AsyncLoading();
    state = await AsyncValue.guard(
      () => ref.read(restaurantServiceProvider).dashboard(),
    );
  }
}

final dashboardProvider =
    AsyncNotifierProvider<DashboardNotifier, ({DashboardStats stats, List<OrderModel> activeDeliveries})>(
        DashboardNotifier.new);

// Order list
final ordersProvider = FutureProvider.family<List<OrderModel>, String?>((ref, status) async {
  return ref.read(restaurantServiceProvider).listOrders(status: status);
});

// Single order detail
final orderDetailProvider = FutureProvider.family<OrderModel, int>((ref, id) async {
  return ref.read(restaurantServiceProvider).getOrder(id);
});

// History
final historyProvider = FutureProvider<List<OrderModel>>((ref) async {
  return ref.read(restaurantServiceProvider).history();
});

// Reports (today + 7-day recent)
final reportsProvider = FutureProvider<ReportData>((ref) async {
  return ref.read(restaurantServiceProvider).reports();
});
