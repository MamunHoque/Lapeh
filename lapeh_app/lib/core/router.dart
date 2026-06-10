import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';
import '../features/auth/login_screen.dart';
import '../features/auth/signup_screen.dart';
import '../features/auth/otp_screen.dart';
import '../features/sender/sender_shell.dart';
import '../features/driver/driver_shell.dart';
import 'providers/auth_provider.dart';

final routerProvider = Provider<GoRouter>((ref) {
  final auth = ref.watch(authProvider);

  return GoRouter(
    initialLocation: '/login',
    redirect: (context, state) {
      final user = auth.valueOrNull;
      final isLoading = auth.isLoading;
      if (isLoading) return null;

      final loc = state.matchedLocation;
      final onAuthPage = loc == '/login' || loc == '/signup';
      final onOtpPage = loc == '/verify-otp';

      // Unauthenticated → allow login/signup only.
      if (user == null) return onAuthPage ? null : '/login';

      // Senders must verify their phone before using the app.
      if (user.isSender && !user.phoneVerified) {
        return onOtpPage ? null : '/verify-otp';
      }

      // Authenticated + (verified or not a sender) → send to role home.
      if (onAuthPage || onOtpPage) {
        return user.isDriver ? '/driver' : '/sender';
      }
      return null;
    },
    routes: [
      GoRoute(path: '/login', builder: (_, __) => const LoginScreen()),
      GoRoute(path: '/signup', builder: (_, __) => const SignupScreen()),
      GoRoute(path: '/verify-otp', builder: (_, state) => OtpScreen(devOtp: state.extra as String?)),
      GoRoute(path: '/sender', builder: (_, __) => const SenderShell()),
      GoRoute(path: '/driver', builder: (_, __) => const DriverShell()),
    ],
  );
});
