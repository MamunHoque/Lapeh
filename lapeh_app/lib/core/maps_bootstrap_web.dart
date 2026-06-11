// ignore_for_file: avoid_web_libraries_in_flutter, deprecated_member_use
import 'dart:async';
import 'dart:html' as html;

/// Inject the Google Maps JavaScript SDK at runtime using the key fetched from
/// the API, so the web build has no hardcoded key in index.html. No-ops if the
/// key is empty or the SDK was already injected.
Future<void> loadGoogleMaps(String key) async {
  if (key.isEmpty) return;
  if (html.document.querySelector('#gmaps-sdk') != null) return;

  final completer = Completer<void>();
  final script = html.ScriptElement()
    ..id = 'gmaps-sdk'
    ..src = 'https://maps.googleapis.com/maps/api/js?key=$key'
    ..async = true
    ..defer = true;
  script.onLoad.listen((_) => completer.complete());
  script.onError.listen((_) => completer.complete());
  html.document.head!.append(script);

  return completer.future;
}
