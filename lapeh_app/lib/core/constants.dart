class ApiConfig {
  // Override per environment:
  //   flutter run --dart-define=API_URL=http://10.0.2.2:8000/api   (Android emulator)
  //   flutter run --dart-define=API_URL=http://192.168.x.x:8000/api (physical device)
  //   flutter build apk --dart-define=API_URL=https://api.yourdomain.com/api (production)
  static const baseUrl = String.fromEnvironment(
    'API_URL',
    defaultValue: 'http://127.0.0.1:8000/api',
  );
  static const googleMapsKey = 'AIzaSyDlELuIJTtPvbK_dKgKilYPQZRf5K_OTE4';
}

class AppStrings {
  static const tokenKey = 'lapeh_token';
  static const userKey = 'lapeh_user';
}
