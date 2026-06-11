import 'dart:async';
import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:google_maps_flutter/google_maps_flutter.dart';
import '../../core/theme.dart';
import '../../core/i18n.dart';
import '../../core/providers/auth_provider.dart';
import '../../core/providers/driver_provider.dart';
import '../../core/services/driver_service.dart';
import '../../core/services/fcm_service.dart';
import '../../core/services/location_service.dart';
import '../../shared/widgets.dart';
import '../../shared/map_placeholder.dart';
import 'incoming_request_sheet.dart';
import 'delivery_flow.dart';

class DriverHomeScreen extends ConsumerStatefulWidget {
  const DriverHomeScreen({super.key});
  @override
  ConsumerState<DriverHomeScreen> createState() => _DriverHomeScreenState();
}

class _DriverHomeScreenState extends ConsumerState<DriverHomeScreen> {
  Timer? _offerPoll;
  Timer? _orderPoll;
  bool _navigating = false; // guard against double-pushing DeliveryFlow
  @override
  void initState() {
    super.initState();
    // Adopt backend-reported online status so the toggle is correct after restart.
    final status = ref.read(authProvider).valueOrNull?.driver?.status;
    if (status != null && (status == 'online' || status == 'on_delivery')) {
      WidgetsBinding.instance.addPostFrameCallback((_) {
        if (mounted) ref.read(driverStatusProvider.notifier).sync(status);
      });
    }
    _initFcm();
  }

  Future<void> _initFcm() async {
    final fcm = FcmService();
    fcm.onOffer = (offer) {
      if (mounted && ref.read(driverStatusProvider) == 'online') {
        _offerPoll?.cancel();
        showIncomingRequest(context, offer, onDone: _startPolling);
      }
    };
    try {
      await fcm.init();
    } catch (_) {
      // FCM unavailable (no google-services.json) — polling is the fallback
    }
    _startPolling();
  }

  void _startPolling() {
    _offerPoll?.cancel();
    _orderPoll?.cancel();
    _offerPoll = Timer.periodic(const Duration(seconds: 5), (_) => _checkOffer());
    _orderPoll = Timer.periodic(const Duration(seconds: 8), (_) => _checkActiveOrder());
  }

  void _stopPolling() {
    _offerPoll?.cancel();
    _orderPoll?.cancel();
  }

  Future<void> _checkOffer() async {
    if (ref.read(driverStatusProvider) != 'online') return;
    try {
      final offer = await DriverService().currentOffer();
      if (offer != null && mounted) {
        _stopPolling();
        showIncomingRequest(context, offer, onDone: _startPolling);
      }
    } catch (_) {}
  }

  Future<void> _checkActiveOrder() async {
    if (_navigating) return;
    try {
      final order = await DriverService().currentOrder();
      if (order != null && !order.isTerminal && mounted && !_navigating) {
        final status = ref.read(driverStatusProvider);
        if (status == 'online' || status == 'offline') {
          _navigating = true;
          ref.read(driverStatusProvider.notifier).setOnDelivery();
          _stopPolling();
          LocationService().startBroadcasting();
          Navigator.push(
            context,
            MaterialPageRoute(builder: (_) => DeliveryFlowScreen(order: order)),
          ).then((_) {
            _navigating = false;
            LocationService().stopBroadcasting();
            _startPolling();
          });
        }
      }
    } catch (_) {}
  }

  void _showPermissionIssue(LocationOutcome outcome) {
    final blocked = outcome == LocationOutcome.deniedForever;
    ScaffoldMessenger.of(context).showSnackBar(SnackBar(
      content: Text(blocked ? tr('location_denied_forever') : tr('location_permission')),
      action: blocked
          ? SnackBarAction(
              label: tr('open_settings'),
              onPressed: () => LocationService().openSettings(),
            )
          : null,
    ));
  }

  @override
  void dispose() {
    _stopPolling();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    final status = ref.watch(driverStatusProvider);
    final online = status == 'online';
    final user = ref.watch(authProvider).valueOrNull;
    final driverProfile = user?.driver;

    return Stack(
      children: [
        // Full-bleed map fills the home tab; shell already reserves the nav bar.
        const Positioned.fill(child: _DriverMap()),

        // Subtle top gradient so the floating header stays readable over the map.
        const Positioned(
          top: 0, left: 0, right: 0,
          child: IgnorePointer(
            child: SizedBox(
              height: 140,
              child: DecoratedBox(
                decoration: BoxDecoration(
                  gradient: LinearGradient(
                    begin: Alignment.topCenter,
                    end: Alignment.bottomCenter,
                    colors: [Color(0x33000000), Color(0x00000000)],
                  ),
                ),
              ),
            ),
          ),
        ),

        // Floating driver header.
        Positioned(
          top: 0, left: 0, right: 0,
          child: SafeArea(
            bottom: false,
            child: Padding(
              padding: const EdgeInsets.fromLTRB(16, 10, 16, 0),
              child: Container(
                padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 10),
                decoration: BoxDecoration(
                  color: Colors.white.withValues(alpha: 0.94),
                  borderRadius: BorderRadius.circular(16),
                  border: Border.all(color: AppColors.line),
                  boxShadow: const [BoxShadow(color: Color(0x1A000000), blurRadius: 14, offset: Offset(0, 4))],
                ),
                child: Row(children: [
                  Expanded(
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Text(user?.name ?? tr('driver'),
                            style: const TextStyle(fontSize: 12.5, color: AppColors.slate, fontWeight: FontWeight.w600)),
                        Row(children: [
                          const Icon(Icons.star, size: 15, color: AppColors.amber),
                          const SizedBox(width: 4),
                          Text(
                            '${driverProfile?.ratingAvg.toStringAsFixed(1) ?? "–"} · ${driverProfile?.vehiclePlate ?? "–"}',
                            style: T.h2,
                          ),
                        ]),
                      ],
                    ),
                  ),
                  Container(
                    width: 40, height: 40,
                    decoration: BoxDecoration(color: Colors.white, borderRadius: BorderRadius.circular(12), border: Border.all(color: AppColors.line)),
                    child: const Icon(Icons.account_balance_wallet_outlined, size: 19),
                  ),
                ]),
              ),
            ),
          ),
        ),

        // Bottom action panel pinned above the nav bar.
        Positioned(
          left: 0, right: 0, bottom: 0,
          child: SafeArea(
            top: false,
            child: Container(
              padding: const EdgeInsets.fromLTRB(16, 8, 16, 18),
              decoration: const BoxDecoration(
                color: Colors.white,
                borderRadius: BorderRadius.vertical(top: Radius.circular(20)),
                boxShadow: [BoxShadow(color: Color(0x1F000000), blurRadius: 18, offset: Offset(0, -4))],
              ),
              child: Column(
                mainAxisSize: MainAxisSize.min,
                children: [
                  Container(
                    width: 38, height: 4,
                    margin: const EdgeInsets.only(bottom: 12),
                    decoration: BoxDecoration(color: AppColors.line, borderRadius: BorderRadius.circular(2)),
                  ),
                  _StatusToggle(
                    online: online,
                    onChanged: (v) async {
                      if (v) {
                        final outcome = await LocationService().requestPermissionDetailed();
                        if (outcome != LocationOutcome.granted) {
                          if (mounted) _showPermissionIssue(outcome);
                          return;
                        }
                        await ref.read(driverStatusProvider.notifier).goOnline();
                        LocationService().startBroadcasting();
                        _startPolling();
                      } else {
                        _stopPolling();
                        LocationService().stopBroadcasting();
                        await ref.read(driverStatusProvider.notifier).goOffline();
                      }
                    },
                  ),
                  const SizedBox(height: 12),
                  _EarningsMini(),
                ],
              ),
            ),
          ),
        ),
      ],
    );
  }
}

class _StatusToggle extends StatelessWidget {
  final bool online;
  final ValueChanged<bool> onChanged;
  const _StatusToggle({required this.online, required this.onChanged});

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.all(14),
      decoration: BoxDecoration(
        gradient: online ? const LinearGradient(colors: [Color(0xFFEAFFF3), Color(0xFFDFF7EA)]) : null,
        color: online ? null : Colors.white,
        borderRadius: BorderRadius.circular(16),
        border: Border.all(color: online ? const Color(0xFFBFECCF) : AppColors.line),
      ),
      child: Row(children: [
        Expanded(
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Row(children: [
                Text(online ? tr('youre_online') : tr('youre_offline'),
                    style: TextStyle(fontSize: 15, fontWeight: FontWeight.w800, color: online ? AppColors.green : AppColors.ink)),
                if (online) const Padding(padding: EdgeInsets.only(left: 6), child: Icon(Icons.power_settings_new, size: 15, color: AppColors.green)),
              ]),
              Text(online ? tr('searching_hint') : tr('go_online_hint'), style: T.mutedSm),
            ],
          ),
        ),
        Switch(
          value: online,
          activeThumbColor: Colors.white,
          activeTrackColor: AppColors.green,
          onChanged: onChanged,
        ),
      ]),
    );
  }
}

class _EarningsMini extends ConsumerWidget {
  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final earningsAsync = ref.watch(earningsProvider);
    return earningsAsync.when(
      loading: () => Row(children: [
        Expanded(child: _Mini(tr('today'), '–')),
        const SizedBox(width: 8),
        Expanded(child: _Mini(tr('trips'), '–')),
        const SizedBox(width: 8),
        Expanded(child: _Mini(tr('online'), '–')),
      ]),
      error: (_, __) => const SizedBox(),
      data: (e) => Row(children: [
        Expanded(child: _Mini(tr('today'), 'AED ${e.today.earnings.toStringAsFixed(0)}')),
        const SizedBox(width: 8),
        Expanded(child: _Mini(tr('trips'), '${e.today.trips}')),
        const SizedBox(width: 8),
        Expanded(child: _Mini(tr('online'), '–')),
      ]),
    );
  }
}

class _Mini extends StatelessWidget {
  final String label, value;
  const _Mini(this.label, this.value);
  @override
  Widget build(BuildContext context) {
    return AppCard(
      padding: const EdgeInsets.all(11),
      child: Column(children: [
        Text(label, style: const TextStyle(fontSize: 10.5, color: AppColors.slate, fontWeight: FontWeight.w600)),
        const SizedBox(height: 2),
        Text(value, style: const TextStyle(fontSize: 18, fontWeight: FontWeight.w800)),
      ]),
    );
  }
}

/// Live map of the driver's own position. Falls back to the painted
/// placeholder while permission is pending or location is unavailable.
class _DriverMap extends StatefulWidget {
  const _DriverMap();
  @override
  State<_DriverMap> createState() => _DriverMapState();
}

class _DriverMapState extends State<_DriverMap> {
  GoogleMapController? _ctrl;
  LatLng? _pos;
  Timer? _timer;

  @override
  void initState() {
    super.initState();
    _update();
    _timer = Timer.periodic(const Duration(seconds: 10), (_) => _update());
  }

  Future<void> _update() async {
    final p = await LocationService().getCurrentPosition();
    if (p != null && mounted) {
      final next = LatLng(p.latitude, p.longitude);
      setState(() => _pos = next);
      _ctrl?.animateCamera(CameraUpdate.newLatLng(next));
    }
  }

  @override
  void dispose() {
    _timer?.cancel();
    _ctrl?.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    if (_pos == null) {
      return MapPlaceholder(
        height: double.infinity,
        radius: BorderRadius.zero,
        showRoute: false,
        dropLabel: '',
        pickupLabel: tr('you_marker'),
        movingDotT: null,
      );
    }
    return SizedBox.expand(
      child: GoogleMap(
        initialCameraPosition: CameraPosition(target: _pos!, zoom: 15),
        myLocationEnabled: true,
        myLocationButtonEnabled: false,
        zoomControlsEnabled: false,
        markers: {
          Marker(
            markerId: const MarkerId('you'),
            position: _pos!,
            icon: BitmapDescriptor.defaultMarkerWithHue(BitmapDescriptor.hueAzure),
            infoWindow: InfoWindow(title: tr('you_marker')),
          ),
        },
        onMapCreated: (c) => _ctrl = c,
      ),
    );
  }
}
