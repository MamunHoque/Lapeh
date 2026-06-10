import 'dart:convert';
import 'package:firebase_messaging/firebase_messaging.dart';
import '../models/order_model.dart';
import 'auth_service.dart';

/// Top-level handler required by Firebase for background messages.
@pragma('vm:entry-point')
Future<void> _firebaseBackgroundHandler(RemoteMessage message) async {
  // Background messages: data is available but no UI. Store for next open.
}

typedef OfferCallback = void Function(DeliveryOffer offer);

class FcmService {
  static final FcmService _instance = FcmService._();
  factory FcmService() => _instance;
  FcmService._();

  final _messaging = FirebaseMessaging.instance;

  // Caller (DriverHomeScreen) registers this to show the offer sheet
  OfferCallback? onOffer;

  Future<void> init() async {
    FirebaseMessaging.onBackgroundMessage(_firebaseBackgroundHandler);

    await _messaging.requestPermission(
      alert: true,
      badge: true,
      sound: true,
    );

    // Save token to backend
    final token = await _messaging.getToken();
    if (token != null) {
      try {
        await AuthService().updateFcmToken(token);
      } catch (_) {}
    }

    // Refresh token
    _messaging.onTokenRefresh.listen((token) async {
      try {
        await AuthService().updateFcmToken(token);
      } catch (_) {}
    });

    // Foreground messages
    FirebaseMessaging.onMessage.listen(_handleMessage);

    // App opened from notification (background → foreground)
    FirebaseMessaging.onMessageOpenedApp.listen(_handleMessage);

    // App launched from terminated state via notification
    final initial = await _messaging.getInitialMessage();
    if (initial != null) _handleMessage(initial);
  }

  void _handleMessage(RemoteMessage message) {
    final type = message.data['type'];
    if (type == 'new_offer' && onOffer != null) {
      try {
        final offer = DeliveryOffer.fromJson(
          Map<String, dynamic>.from(json.decode(message.data['offer'] ?? '{}')),
        );
        onOffer!(offer);
      } catch (_) {}
    }
  }
}
