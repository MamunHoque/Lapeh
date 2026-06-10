import 'dart:async';
import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
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
  @override
  void initState() {
    super.initState();
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
    try {
      final order = await DriverService().currentOrder();
      if (order != null && !order.isTerminal && mounted) {
        final status = ref.read(driverStatusProvider);
        if (status == 'online' || status == 'offline') {
          ref.read(driverStatusProvider.notifier).setOnDelivery();
          _stopPolling();
          LocationService().startBroadcasting();
          Navigator.push(
            context,
            MaterialPageRoute(builder: (_) => DeliveryFlowScreen(order: order)),
          ).then((_) {
            LocationService().stopBroadcasting();
            _startPolling();
          });
        }
      }
    } catch (_) {}
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

    return ListView(
      padding: const EdgeInsets.fromLTRB(16, 10, 16, 24),
      children: [
        Row(children: [
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
        const SizedBox(height: 14),
        MapPlaceholder(height: 210, showRoute: false, dropLabel: '', pickupLabel: tr('you_marker'), movingDotT: null),
        const SizedBox(height: 14),
        _StatusToggle(
          online: online,
          onChanged: (v) async {
            if (v) {
              final ok = await LocationService().requestPermission();
              if (!ok && mounted) {
                ScaffoldMessenger.of(context).showSnackBar(
                  SnackBar(content: Text(tr('location_permission'))),
                );
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
        const SizedBox(height: 14),
        _EarningsMini(),
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
        Expanded(child: _Mini(tr('today'), 'AED ${e.today.toStringAsFixed(0)}')),
        const SizedBox(width: 8),
        Expanded(child: _Mini(tr('trips'), '${e.history.length}')),
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
