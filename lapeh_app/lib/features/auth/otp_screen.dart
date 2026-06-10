import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import '../../core/theme.dart';
import '../../core/i18n.dart';
import '../../core/api_client.dart';
import '../../core/providers/auth_provider.dart';
import '../../shared/widgets.dart';

class OtpScreen extends ConsumerStatefulWidget {
  final String? devOtp;
  const OtpScreen({super.key, this.devOtp});
  @override
  ConsumerState<OtpScreen> createState() => _OtpScreenState();
}

class _OtpScreenState extends ConsumerState<OtpScreen> {
  final _code = TextEditingController();
  bool _loading = false;
  String? _error;
  String? _devOtp;

  @override
  void initState() {
    super.initState();
    _devOtp = widget.devOtp;
    if (_devOtp != null) _code.text = _devOtp!; // prefill in dev for one-tap testing
  }

  @override
  void dispose() {
    _code.dispose();
    super.dispose();
  }

  Future<void> _verify() async {
    final code = _code.text.trim();
    if (code.length < 4) {
      setState(() => _error = tr('otp_required'));
      return;
    }
    setState(() { _loading = true; _error = null; });
    try {
      await ref.read(authProvider.notifier).verifyOtp(code);
      // GoRouter redirect sends a verified sender to /sender automatically.
    } catch (e) {
      setState(() => _error = apiErrorMessage(e));
    } finally {
      if (mounted) setState(() => _loading = false);
    }
  }

  Future<void> _resend() async {
    try {
      final otp = await ref.read(authProvider.notifier).resendOtp();
      if (mounted) {
        setState(() { _devOtp = otp; if (otp != null) _code.text = otp; });
        ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text(tr('otp_resent'))));
      }
    } catch (e) {
      if (mounted) ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text(apiErrorMessage(e))));
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(title: Text(tr('verify_phone'))),
      body: SafeArea(
        child: ListView(
          padding: const EdgeInsets.fromLTRB(22, 16, 22, 28),
          children: [
            Center(
              child: Container(
                width: 56, height: 56,
                decoration: BoxDecoration(color: AppColors.pinkSoft, borderRadius: BorderRadius.circular(16)),
                child: const Icon(Icons.sms_outlined, color: AppColors.pink, size: 28),
              ),
            ),
            const SizedBox(height: 16),
            Text(tr('verify_phone'), textAlign: TextAlign.center, style: T.h2),
            const SizedBox(height: 6),
            Text(tr('otp_sub'), textAlign: TextAlign.center, style: T.muted),
            if (_devOtp != null) ...[
              const SizedBox(height: 12),
              Container(
                padding: const EdgeInsets.all(10),
                decoration: BoxDecoration(color: AppColors.amberSoft, borderRadius: BorderRadius.circular(10)),
                child: Text('${tr('dev_otp_label')}: $_devOtp',
                    textAlign: TextAlign.center,
                    style: const TextStyle(color: Color(0xFF9A6800), fontWeight: FontWeight.w700)),
              ),
            ],
            const SizedBox(height: 20),
            LabeledField(label: tr('otp_code'), hint: '123456', icon: Icons.lock_outline, keyboardType: TextInputType.number, controller: _code),
            if (_error != null) ...[
              const SizedBox(height: 10),
              Text(_error!, style: const TextStyle(color: AppColors.red, fontSize: 12.5, fontWeight: FontWeight.w600)),
            ],
            const SizedBox(height: 18),
            _loading
                ? const Center(child: CircularProgressIndicator(color: AppColors.pink))
                : LapehButton(label: tr('verify'), icon: Icons.check, onPressed: _verify),
            const SizedBox(height: 10),
            Center(child: TextButton(onPressed: _resend, child: Text(tr('resend_otp')))),
          ],
        ),
      ),
    );
  }
}
