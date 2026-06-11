import 'package:cached_network_image/cached_network_image.dart';
import 'package:flutter/foundation.dart';
import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';
import '../../core/theme.dart';
import '../../core/i18n.dart';
import '../../core/app_state.dart';
import '../../core/api_client.dart';
import '../../core/demo_credentials.dart';
import '../../core/providers/auth_provider.dart';
import '../../core/providers/app_config_provider.dart';
import '../../core/services/app_config_service.dart';
import '../../shared/widgets.dart';

class LoginScreen extends ConsumerStatefulWidget {
  const LoginScreen({super.key});
  @override
  ConsumerState<LoginScreen> createState() => _LoginScreenState();
}

class _LoginScreenState extends ConsumerState<LoginScreen> {
  bool isSender = true;
  bool _loading = false;
  String? _error;

  final _phoneCtrl = TextEditingController();
  final _passCtrl = TextEditingController();

  @override
  void dispose() {
    _phoneCtrl.dispose();
    _passCtrl.dispose();
    super.dispose();
  }

  Future<void> _signIn() async {
    final phone = _phoneCtrl.text.trim();
    final pass = _passCtrl.text;
    if (phone.isEmpty || pass.isEmpty) {
      setState(() => _error = tr('error_phone_pass'));
      return;
    }
    setState(() { _loading = true; _error = null; });
    try {
      await ref.read(authProvider.notifier).login(phone, pass);
      // GoRouter redirect will navigate based on role
    } catch (e) {
      setState(() => _error = apiErrorMessage(e));
    } finally {
      if (mounted) setState(() => _loading = false);
    }
  }

  Future<void> _demoLogin(DemoAccount account) async {
    setState(() {
      isSender = account.isSender;
      _phoneCtrl.text = account.phone;
      _passCtrl.text = account.password;
      _error = null;
    });
    await _signIn();
  }

  @override
  Widget build(BuildContext context) {
    final driver = !isSender;
    final config = ref.watch(appConfigProvider).valueOrNull ?? AppConfigService.instance.current;
    return Scaffold(
      body: SafeArea(
        child: SingleChildScrollView(
          padding: const EdgeInsets.fromLTRB(22, 12, 22, 28),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.stretch,
            children: [
              Align(
                alignment: AlignmentDirectional.centerEnd,
                child: TextButton.icon(
                  onPressed: () => setState(toggleLocale),
                  icon: const Icon(Icons.language, size: 18, color: AppColors.slate),
                  label: Text(isArabic ? 'EN' : 'عربي', style: const TextStyle(color: AppColors.slate, fontWeight: FontWeight.w700)),
                ),
              ),
              const SizedBox(height: 8),
              Center(
                child: config.hasLogo
                    ? CachedNetworkImage(
                        imageUrl: config.logoUrl,
                        height: 64,
                        errorWidget: (_, __, ___) => const Icon(Icons.delivery_dining, size: 64, color: AppColors.pink),
                      )
                    : Image.asset('assets/images/logo.png', height: 64,
                        errorBuilder: (_, __, ___) => const Icon(Icons.delivery_dining, size: 64, color: AppColors.pink)),
              ),
              const SizedBox(height: 10),
              Text(config.appName, textAlign: TextAlign.center, style: T.h1.copyWith(fontSize: 20)),
              const SizedBox(height: 12),
              Text(driver ? tr('driver_signin') : tr('welcome_back'),
                  textAlign: TextAlign.center, style: T.h1.copyWith(fontSize: 26)),
              const SizedBox(height: 6),
              Text(driver ? tr('driver_tagline') : tr('dispatch_tagline'),
                  textAlign: TextAlign.center, style: T.muted),
              const SizedBox(height: 26),
              LabeledField(
                label: driver ? tr('mobile_number') : tr('phone_email'),
                hint: driver ? '+971 55 987 6543' : 'manager@alsafadi.ae',
                icon: driver ? Icons.phone_outlined : Icons.person_outline,
                controller: _phoneCtrl,
                keyboardType: TextInputType.phone,
              ),
              const SizedBox(height: 14),
              LabeledField(
                label: tr('password'),
                hint: '••••••••',
                icon: Icons.lock_outline,
                obscure: true,
                controller: _passCtrl,
              ),
              if (_error != null) ...[
                const SizedBox(height: 10),
                Container(
                  padding: const EdgeInsets.all(10),
                  decoration: BoxDecoration(color: AppColors.redSoft, borderRadius: BorderRadius.circular(10)),
                  child: Text(_error!, style: const TextStyle(color: AppColors.red, fontSize: 12.5, fontWeight: FontWeight.w600)),
                ),
              ],
              const SizedBox(height: 22),
              _loading
                  ? const Center(child: CircularProgressIndicator(color: AppColors.pink))
                  : LapehButton(label: tr('sign_in'), icon: Icons.arrow_forward, onPressed: _signIn),
              const SizedBox(height: 18),
              _RolePills(isSender: isSender, onChanged: (v) => setState(() => isSender = v)),
              const SizedBox(height: 14),
              if (isSender && config.senderRegistration)
                Center(
                  child: TextButton(
                    onPressed: () => context.push('/signup'),
                    child: Text.rich(TextSpan(children: [
                      TextSpan(text: '${tr('no_account')} ', style: T.mutedSm),
                      TextSpan(text: tr('sign_up'), style: const TextStyle(color: AppColors.pink, fontWeight: FontWeight.w700, fontSize: 12.5)),
                    ])),
                  ),
                )
              else if (!isSender)
                Text(tr('role_hint'), textAlign: TextAlign.center, style: T.mutedSm.copyWith(color: AppColors.slate2)),
              if (kDebugMode) ...[
                const SizedBox(height: 22),
                Container(
                  padding: const EdgeInsets.all(14),
                  decoration: BoxDecoration(
                    color: AppColors.bg,
                    borderRadius: BorderRadius.circular(14),
                    border: Border.all(color: AppColors.line, style: BorderStyle.solid),
                  ),
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.stretch,
                    children: [
                      Text(tr('demo_login'), style: T.mutedSm.copyWith(fontWeight: FontWeight.w700, color: AppColors.slate)),
                      const SizedBox(height: 4),
                      Text(tr('demo_login_hint'), style: T.mutedSm),
                      const SizedBox(height: 12),
                      Wrap(
                        spacing: 8,
                        runSpacing: 8,
                        children: [
                          for (final account in demoAccounts)
                            _DemoChip(
                              label: account.label,
                              onTap: _loading ? null : () => _demoLogin(account),
                            ),
                        ],
                      ),
                    ],
                  ),
                ),
              ],
            ],
          ),
        ),
      ),
    );
  }
}

class _DemoChip extends StatelessWidget {
  final String label;
  final VoidCallback? onTap;

  const _DemoChip({required this.label, this.onTap});

  @override
  Widget build(BuildContext context) {
    return ActionChip(
      label: Text(label, style: const TextStyle(fontSize: 11.5, fontWeight: FontWeight.w600)),
      onPressed: onTap,
      backgroundColor: Colors.white,
      side: const BorderSide(color: AppColors.line),
      padding: const EdgeInsets.symmetric(horizontal: 4, vertical: 0),
    );
  }
}

class _RolePills extends StatelessWidget {
  final bool isSender;
  final ValueChanged<bool> onChanged;
  const _RolePills({required this.isSender, required this.onChanged});

  @override
  Widget build(BuildContext context) {
    return Row(children: [
      _pill(tr('sender'), Icons.storefront_outlined, isSender, () => onChanged(true)),
      const SizedBox(width: 8),
      _pill(tr('driver'), Icons.pedal_bike, !isSender, () => onChanged(false)),
    ]);
  }

  Widget _pill(String label, IconData icon, bool on, VoidCallback onTap) {
    return Expanded(
      child: GestureDetector(
        onTap: onTap,
        child: Container(
          padding: const EdgeInsets.symmetric(vertical: 11),
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
