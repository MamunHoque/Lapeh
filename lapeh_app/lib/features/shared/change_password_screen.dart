import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import '../../core/theme.dart';
import '../../core/i18n.dart';
import '../../core/api_client.dart';
import '../../core/services/auth_service.dart';
import '../../shared/widgets.dart';

class ChangePasswordScreen extends ConsumerStatefulWidget {
  const ChangePasswordScreen({super.key});
  @override
  ConsumerState<ChangePasswordScreen> createState() => _ChangePasswordScreenState();
}

class _ChangePasswordScreenState extends ConsumerState<ChangePasswordScreen> {
  final _current = TextEditingController();
  final _new = TextEditingController();
  final _confirm = TextEditingController();
  bool _loading = false;
  String? _error;

  @override
  void dispose() {
    _current.dispose();
    _new.dispose();
    _confirm.dispose();
    super.dispose();
  }

  Future<void> _submit() async {
    final current = _current.text;
    final next = _new.text;
    final confirm = _confirm.text;

    if (current.isEmpty) {
      setState(() => _error = tr('current_password_required'));
      return;
    }
    if (next.length < 6) {
      setState(() => _error = tr('password_min'));
      return;
    }
    if (next != confirm) {
      setState(() => _error = tr('passwords_no_match'));
      return;
    }

    setState(() { _loading = true; _error = null; });
    try {
      await AuthService().changePassword(currentPassword: current, newPassword: next);
      if (!mounted) return;
      ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text(tr('password_changed'))));
      Navigator.pop(context);
    } catch (e) {
      setState(() => _error = apiErrorMessage(e));
    } finally {
      if (mounted) setState(() => _loading = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(title: Text(tr('change_password'))),
      body: SafeArea(
        child: ListView(
          padding: const EdgeInsets.fromLTRB(20, 8, 20, 28),
          children: [
            _PasswordField(label: tr('current_password'), controller: _current),
            const SizedBox(height: 14),
            _PasswordField(label: tr('new_password'), controller: _new),
            const SizedBox(height: 14),
            _PasswordField(label: tr('confirm_password'), controller: _confirm),
            if (_error != null) ...[
              const SizedBox(height: 14),
              Container(
                padding: const EdgeInsets.all(10),
                decoration: BoxDecoration(color: AppColors.redSoft, borderRadius: BorderRadius.circular(10)),
                child: Text(_error!, style: const TextStyle(color: AppColors.red, fontSize: 12.5, fontWeight: FontWeight.w600)),
              ),
            ],
            const SizedBox(height: 22),
            _loading
                ? const Center(child: CircularProgressIndicator(color: AppColors.pink))
                : LapehButton(label: tr('change_password'), icon: Icons.lock_reset, onPressed: _submit),
          ],
        ),
      ),
    );
  }
}

/// Password input with a show/hide toggle, styled like LabeledField.
class _PasswordField extends StatefulWidget {
  final String label;
  final TextEditingController controller;
  const _PasswordField({required this.label, required this.controller});
  @override
  State<_PasswordField> createState() => _PasswordFieldState();
}

class _PasswordFieldState extends State<_PasswordField> {
  bool _obscure = true;
  @override
  Widget build(BuildContext context) {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Text(widget.label, style: const TextStyle(fontSize: 12, fontWeight: FontWeight.w700, color: AppColors.ink2)),
        const SizedBox(height: 6),
        TextField(
          controller: widget.controller,
          obscureText: _obscure,
          style: const TextStyle(fontSize: 14, fontWeight: FontWeight.w500),
          decoration: InputDecoration(
            hintText: '••••••••',
            hintStyle: const TextStyle(color: AppColors.slate2, fontWeight: FontWeight.w400),
            prefixIcon: const Icon(Icons.lock_outline, size: 19, color: AppColors.slate2),
            suffixIcon: IconButton(
              icon: Icon(_obscure ? Icons.visibility_outlined : Icons.visibility_off_outlined, size: 19, color: AppColors.slate2),
              onPressed: () => setState(() => _obscure = !_obscure),
            ),
            isDense: true,
            contentPadding: const EdgeInsets.symmetric(horizontal: 14, vertical: 14),
            filled: true,
            fillColor: Colors.white,
            enabledBorder: OutlineInputBorder(
              borderRadius: BorderRadius.circular(12),
              borderSide: const BorderSide(color: Color(0xFFD7DAE4), width: 1.4),
            ),
            focusedBorder: OutlineInputBorder(
              borderRadius: BorderRadius.circular(12),
              borderSide: const BorderSide(color: AppColors.pink, width: 1.6),
            ),
          ),
        ),
      ],
    );
  }
}
