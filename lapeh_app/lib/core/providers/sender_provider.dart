import 'package:flutter_riverpod/flutter_riverpod.dart';
import '../models/order_model.dart';
import '../services/sender_service.dart';

final senderServiceProvider = Provider((_) => SenderService());

/// Selected bottom-nav tab in the sender shell (0 home · 1 deliveries · 2 reports · 3 profile).
final senderTabProvider = StateProvider<int>((_) => 0);

// Dashboard state
class DashboardNotifier extends AsyncNotifier<({DashboardStats stats, List<OrderModel> activeDeliveries})> {
  @override
  Future<({DashboardStats stats, List<OrderModel> activeDeliveries})> build() =>
      ref.read(senderServiceProvider).dashboard();

  Future<void> refresh() async {
    state = const AsyncLoading();
    state = await AsyncValue.guard(
      () => ref.read(senderServiceProvider).dashboard(),
    );
  }
}

final dashboardProvider =
    AsyncNotifierProvider<DashboardNotifier, ({DashboardStats stats, List<OrderModel> activeDeliveries})>(
        DashboardNotifier.new);

// Order list
final ordersProvider = FutureProvider.family<List<OrderModel>, String?>((ref, status) async {
  return ref.read(senderServiceProvider).listOrders(status: status);
});

// Single order detail
final orderDetailProvider = FutureProvider.family<OrderModel, int>((ref, id) async {
  return ref.read(senderServiceProvider).getOrder(id);
});

// History
final historyProvider = FutureProvider<List<OrderModel>>((ref) async {
  return ref.read(senderServiceProvider).history();
});

// Reports (today + 7-day recent)
final reportsProvider = FutureProvider<ReportData>((ref) async {
  return ref.read(senderServiceProvider).reports();
});
