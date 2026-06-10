import 'dart:async';
import 'package:geolocator/geolocator.dart';
import 'driver_service.dart';

enum LocationOutcome { granted, denied, deniedForever, serviceDisabled }

class LocationService {
  static final LocationService _instance = LocationService._();
  factory LocationService() => _instance;
  LocationService._();

  StreamSubscription<Position>? _sub;
  bool _broadcasting = false;

  bool get isBroadcasting => _broadcasting;

  Future<bool> requestPermission() async {
    return (await requestPermissionDetailed()) == LocationOutcome.granted;
  }

  /// Detailed permission result so the UI can distinguish "denied this time"
  /// from "blocked forever" (needs app settings) and "GPS off".
  Future<LocationOutcome> requestPermissionDetailed() async {
    final serviceEnabled = await Geolocator.isLocationServiceEnabled();
    if (!serviceEnabled) return LocationOutcome.serviceDisabled;

    LocationPermission perm = await Geolocator.checkPermission();
    if (perm == LocationPermission.denied) {
      perm = await Geolocator.requestPermission();
    }
    if (perm == LocationPermission.deniedForever) return LocationOutcome.deniedForever;
    if (perm == LocationPermission.denied) return LocationOutcome.denied;
    return LocationOutcome.granted;
  }

  Future<bool> openSettings() => Geolocator.openAppSettings();

  Future<Position?> getCurrentPosition() async {
    final ok = await requestPermission();
    if (!ok) return null;
    try {
      return await Geolocator.getCurrentPosition(
        locationSettings: const LocationSettings(accuracy: LocationAccuracy.high),
      );
    } catch (_) {
      return null;
    }
  }

  Future<void> startBroadcasting() async {
    if (_broadcasting) return;
    final ok = await requestPermission();
    if (!ok) return;

    _broadcasting = true;
    const settings = LocationSettings(
      accuracy: LocationAccuracy.high,
      distanceFilter: 20, // only emit when moved 20m
    );

    _sub = Geolocator.getPositionStream(locationSettings: settings).listen(
      (pos) async {
        try {
          await DriverService().pushLocation(pos.latitude, pos.longitude);
        } catch (_) {}
      },
      onError: (_) {},
    );
  }

  void stopBroadcasting() {
    _sub?.cancel();
    _sub = null;
    _broadcasting = false;
  }
}
