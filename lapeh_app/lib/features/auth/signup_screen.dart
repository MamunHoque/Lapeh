import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';
import '../../core/theme.dart';
import '../../core/i18n.dart';
import '../../core/api_client.dart';
import '../../core/providers/auth_provider.dart';
import '../../core/services/location_service.dart';
import '../../shared/widgets.dart';

class SignupScreen extends ConsumerStatefulWidget {
  const SignupScreen({super.key});
  @override
  ConsumerState<SignupScreen> createState() => _SignupScreenState();
}

class _SignupScreenState extends ConsumerState<SignupScreen> {
  String _type = 'individual';
  bool _loading = false;
  String? _error;
  double? _lat, _lng;

  final _name = TextEditingController();
  final _phone = TextEditingController();
  final _pass = TextEditingController();
  final _pickup = TextEditingController();
  final _bizName = TextEditingController();
  final _bizCategory = TextEditingController();
  final _contact = TextEditingController();

  @override
  void dispose() {
    _name.dispose(); _phone.dispose(); _pass.dispose(); _pickup.dispose();
    _bizName.dispose(); _bizCategory.dispose(); _contact.dispose();
    super.dispose();
  }

  Future<void> _useCurrentLocation() async {
    final loc = LocationService();
    final pos = await loc.getCurrentPosition();
    if (pos == null || !mounted) return;
    setState(() {
      _lat = pos.latitude;
      _lng = pos.longitude;
      _pickup.text = '${pos.latitude.toStringAsFixed(5)}, ${pos.longitude.toStringAsFixed(5)}';
    });
    final address = await loc.reverseGeocode(pos.latitude, pos.longitude);
    if (address != null && mounted) {
      setState(() => _pickup.text = address);
    }
  }

  Future<void> _submit() async {
    final name = _name.text.trim();
    final phone = _phone.text.trim();
    final pass = _pass.text;
    final isBiz = _type == 'business';

    if (name.isEmpty || phone.isEmpty || pass.length < 6) {
      setState(() => _error = tr('signup_required'));
      return;
    }
    if (isBiz && _bizName.text.trim().isEmpty) {
      setState(() => _error = tr('business_name_required'));
      return;
    }

    setState(() { _loading = true; _error = null; });
    try {
      final result = await ref.read(authProvider.notifier).registerSender(
        type: _type,
        name: name,
        phone: phone,
        password: pass,
        defaultPickupAddress: _pickup.text.trim().isEmpty ? null : _pickup.text.trim(),
        defaultPickupLat: _lat,
        defaultPickupLng: _lng,
        businessName: isBiz ? _bizName.text.trim() : null,
        businessCategory: isBiz && _bizCategory.text.trim().isNotEmpty ? _bizCategory.text.trim() : null,
        contactPersonName: isBiz && _contact.text.trim().isNotEmpty ? _contact.text.trim() : null,
      );
      if (!mounted) return;
      // Go to OTP screen; pass dev OTP so testing is one tap.
      context.go('/verify-otp', extra: result.devOtp);
    } catch (e) {
      setState(() => _error = apiErrorMessage(e));
    } finally {
      if (mounted) setState(() => _loading = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    final isBiz = _type == 'business';
    return Scaffold(
      appBar: AppBar(title: Text(tr('create_account'))),
      body: SafeArea(
        child: ListView(
          padding: const EdgeInsets.fromLTRB(20, 8, 20, 28),
          children: [
            Text(tr('sender_type'), style: T.mutedSm),
            const SizedBox(height: 8),
            Row(children: [
              _typePill('individual', tr('individual'), Icons.person_outline),
              const SizedBox(width: 8),
              _typePill('business', tr('business'), Icons.business_outlined),
            ]),
            const SizedBox(height: 16),
            LabeledField(label: isBiz ? tr('contact_name') : tr('full_name'), hint: 'Mariam Ahmed', icon: Icons.person_outline, controller: _name),
            const SizedBox(height: 12),
            LabeledField(label: tr('mobile_number'), hint: '+971 50 123 4567', icon: Icons.phone_outlined, keyboardType: TextInputType.phone, controller: _phone),
            const SizedBox(height: 12),
            LabeledField(label: tr('password'), hint: '••••••••', icon: Icons.lock_outline, obscure: true, controller: _pass),
            if (isBiz) ...[
              const SizedBox(height: 12),
              LabeledField(label: tr('business_name'), hint: 'Gulf Gadgets', icon: Icons.storefront_outlined, controller: _bizName),
              const SizedBox(height: 12),
              LabeledField(label: tr('business_category'), hint: 'Electronics', icon: Icons.category_outlined, controller: _bizCategory),
              const SizedBox(height: 12),
              LabeledField(label: tr('contact_person'), hint: 'Omar', icon: Icons.badge_outlined, controller: _contact),
            ],
            const SizedBox(height: 12),
            LabeledField(label: tr('default_pickup'), hint: tr('default_pickup_hint'), icon: Icons.location_on_outlined, maxLines: 2, controller: _pickup),
            const SizedBox(height: 8),
            OutlinedButton.icon(
              onPressed: _useCurrentLocation,
              icon: const Icon(Icons.my_location, size: 18),
              label: Text(_lat != null ? tr('location_captured') : tr('use_current_location')),
            ),
            if (_error != null) ...[
              const SizedBox(height: 12),
              Container(
                padding: const EdgeInsets.all(10),
                decoration: BoxDecoration(color: AppColors.redSoft, borderRadius: BorderRadius.circular(10)),
                child: Text(_error!, style: const TextStyle(color: AppColors.red, fontSize: 12.5, fontWeight: FontWeight.w600)),
              ),
            ],
            const SizedBox(height: 20),
            _loading
                ? const Center(child: CircularProgressIndicator(color: AppColors.pink))
                : LapehButton(label: tr('continue_otp'), icon: Icons.arrow_forward, onPressed: _submit),
          ],
        ),
      ),
    );
  }

  Widget _typePill(String value, String label, IconData icon) {
    final on = _type == value;
    return Expanded(
      child: GestureDetector(
        onTap: () => setState(() => _type = value),
        child: Container(
          padding: const EdgeInsets.symmetric(vertical: 12),
          decoration: BoxDecoration(
            color: on ? AppColors.pinkSoft : Colors.white,
            borderRadius: BorderRadius.circular(12),
            border: Border.all(color: on ? AppColors.pink : AppColors.line, width: 1.5),
          ),
          child: Row(mainAxisAlignment: MainAxisAlignment.center, children: [
            Icon(icon, size: 16, color: on ? AppColors.pinkDeep : AppColors.slate),
            const SizedBox(width: 6),
            Text(label, style: TextStyle(fontWeight: FontWeight.w700, fontSize: 12.5, color: on ? AppColors.pinkDeep : AppColors.slate)),
          ]),
        ),
      ),
    );
  }
}
