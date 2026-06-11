/// Non-web platforms load the Maps SDK via native config (AndroidManifest /
/// AppDelegate), so there is nothing to inject at runtime.
Future<void> loadGoogleMaps(String key) async {}
