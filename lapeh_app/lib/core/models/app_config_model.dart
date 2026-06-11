import '../constants.dart';

/// Runtime application config fetched from the public meta endpoint
/// (GET /api/meta/app-config). Lets the admin control branding, the Maps
/// client key and registration availability without an app release.
class AppConfig {
  final String appName;
  final String tagline;
  final String logoUrl;
  final String primaryColor;
  final String currency;
  final String locale;
  final String mapsKey;
  final double mapLat;
  final double mapLng;
  final bool senderRegistration;
  final bool driverRegistration;
  final bool maintenance;
  final String maintenanceMessage;

  const AppConfig({
    required this.appName,
    required this.tagline,
    required this.logoUrl,
    required this.primaryColor,
    required this.currency,
    required this.locale,
    required this.mapsKey,
    required this.mapLat,
    required this.mapLng,
    required this.senderRegistration,
    required this.driverRegistration,
    required this.maintenance,
    required this.maintenanceMessage,
  });

  /// Offline defaults — `mapsKey` falls back to the compiled-in key.
  static const fallback = AppConfig(
    appName: 'Lapeh',
    tagline: '',
    logoUrl: '',
    primaryColor: '#FB0E72',
    currency: 'AED',
    locale: 'en',
    mapsKey: ApiConfig.googleMapsKey,
    mapLat: 25.2048,
    mapLng: 55.2708,
    senderRegistration: true,
    driverRegistration: true,
    maintenance: false,
    maintenanceMessage: '',
  );

  bool get hasLogo => logoUrl.isNotEmpty;

  factory AppConfig.fromJson(Map<String, dynamic> j) {
    final center = (j['map_center'] as Map?) ?? const {};
    final reg = (j['registration'] as Map?) ?? const {};
    final maint = (j['maintenance'] as Map?) ?? const {};
    double toD(v, double d) => v == null ? d : (v is num ? v.toDouble() : double.tryParse('$v') ?? d);

    final mapsKey = (j['maps_key'] as String?)?.trim();
    return AppConfig(
      appName: (j['app_name'] as String?)?.trim().isNotEmpty == true ? j['app_name'] : fallback.appName,
      tagline: j['tagline'] ?? '',
      logoUrl: j['logo_url'] ?? '',
      primaryColor: (j['primary_color'] as String?)?.isNotEmpty == true ? j['primary_color'] : fallback.primaryColor,
      currency: j['currency'] ?? 'AED',
      locale: j['locale'] ?? 'en',
      mapsKey: (mapsKey != null && mapsKey.isNotEmpty) ? mapsKey : fallback.mapsKey,
      mapLat: toD(center['lat'], fallback.mapLat),
      mapLng: toD(center['lng'], fallback.mapLng),
      senderRegistration: reg['sender'] != false,
      driverRegistration: reg['driver'] != false,
      maintenance: maint['enabled'] == true,
      maintenanceMessage: maint['message'] ?? '',
    );
  }
}
