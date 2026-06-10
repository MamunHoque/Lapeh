import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';
import '../features/auth/login_screen.dart';
import '../features/restaurant/restaurant_shell.dart';
import '../features/driver/driver_shell.dart';
import 'providers/auth_provider.dart';

final routerProvider = Provider<GoRouter>((ref) {
  final auth = ref.watch(authProvider);

  return GoRouter(
    initialLocation: '/login',
    redirect: (context, state) {
      final user = auth.valueOrNull;
      final isLoading = auth.isLoading;
      final onLogin = state.matchedLocation == '/login';

      if (isLoading) return null;
      if (user == null && !onLogin) return '/login';
      if (user != null && onLogin) {
        return user.isDriver ? '/driver' : '/restaurant';
      }
      return null;
    },
    routes: [
      GoRoute(path: '/login', builder: (_, __) => const LoginScreen()),
      GoRoute(path: '/restaurant', builder: (_, __) => const RestaurantShell()),
      GoRoute(path: '/driver', builder: (_, __) => const DriverShell()),
    ],
  );
});
