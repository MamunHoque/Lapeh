import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import '../../core/theme.dart';
import '../../core/i18n.dart';
import '../../core/providers/restaurant_provider.dart';
import '../../shared/widgets.dart';
import 'waiting_screen.dart';

class CreateRequestScreen extends ConsumerStatefulWidget {
  const CreateRequestScreen({super.key});
  @override
  ConsumerState<CreateRequestScreen> createState() => _CreateRequestScreenState();
}

class _CreateRequestScreenState extends ConsumerState<CreateRequestScreen> {
  final _nameCtrl = TextEditingController();
  final _phoneCtrl = TextEditingController();
  final _valueCtrl = TextEditingController();
  final _prepCtrl = TextEditingController();
  final _notesCtrl = TextEditingController();
  bool _loading = false;
  String? _error;

  @override
  void dispose() {
    _nameCtrl.dispose(); _phoneCtrl.dispose(); _valueCtrl.dispose();
    _prepCtrl.dispose(); _notesCtrl.dispose();
    super.dispose();
  }

  Future<void> _submit() async {
    final name = _nameCtrl.text.trim();
    final phone = _phoneCtrl.text.trim();
    final valueStr = _valueCtrl.text.trim();

    if (name.isEmpty || phone.isEmpty || valueStr.isEmpty) {
      setState(() => _error = tr('error_fields'));
      return;
    }
    final value = double.tryParse(valueStr);
    if (value == null || value <= 0) {
      setState(() => _error = tr('error_value'));
      return;
    }

    setState(() { _loading = true; _error = null; });
    try {
      final result = await ref.read(restaurantServiceProvider).createOrder(
        customerName: name,
        customerPhone: phone,
        orderValue: value,
        prepTimeMin: int.tryParse(_prepCtrl.text.trim()),
        notes: _notesCtrl.text.trim().isEmpty ? null : _notesCtrl.text.trim(),
      );
      if (!mounted) return;
      // Invalidate orders so list refreshes
      ref.invalidate(dashboardProvider);
      ref.invalidate(ordersProvider(null));
      Navigator.pushReplacement(
        context,
        MaterialPageRoute(builder: (_) => WaitingScreen(order: result.order)),
      );
    } catch (e) {
      setState(() => _error = e.toString().replaceFirst('Exception: ', ''));
    } finally {
      if (mounted) setState(() => _loading = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(title: Text(tr('new_delivery'))),
      body: SafeArea(
        child: ListView(
          padding: const EdgeInsets.fromLTRB(16, 6, 16, 24),
          children: [
            LabeledField(label: tr('customer_name'), hint: 'Ahmed Khalil', icon: Icons.person_outline, controller: _nameCtrl),
            const SizedBox(height: 14),
            LabeledField(label: tr('mobile_number_label'), hint: '+971 50 123 4567', icon: Icons.phone_outlined, keyboardType: TextInputType.phone, controller: _phoneCtrl),
            const SizedBox(height: 14),
            Row(children: [
              Expanded(child: LabeledField(label: tr('order_value'), hint: '85 AED', icon: Icons.payments_outlined, keyboardType: TextInputType.number, controller: _valueCtrl)),
              const SizedBox(width: 10),
              Expanded(child: LabeledField(label: tr('prep_time'), hint: '20 min', icon: Icons.schedule, keyboardType: TextInputType.number, controller: _prepCtrl)),
            ]),
            const SizedBox(height: 14),
            LabeledField(label: tr('notes_optional'), hint: 'Extra napkins, ring twice.', maxLines: 3, controller: _notesCtrl),
            const SizedBox(height: 16),
            Container(
              padding: const EdgeInsets.all(12),
              decoration: BoxDecoration(
                color: AppColors.pinkSoft,
                borderRadius: BorderRadius.circular(12),
                border: Border.all(color: const Color(0xFFFFD6EA)),
              ),
              child: Row(children: [
                const Icon(Icons.chat_bubble_outline, size: 17, color: AppColors.pinkDeep),
                const SizedBox(width: 8),
                Expanded(
                  child: Text(tr('sms_hint'),
                      style: const TextStyle(fontSize: 11.5, fontWeight: FontWeight.w600, color: AppColors.pinkDeep)),
                ),
              ]),
            ),
            if (_error != null) ...[
              const SizedBox(height: 10),
              Container(
                padding: const EdgeInsets.all(10),
                decoration: BoxDecoration(color: AppColors.redSoft, borderRadius: BorderRadius.circular(10)),
                child: Text(_error!, style: const TextStyle(color: AppColors.red, fontSize: 12.5, fontWeight: FontWeight.w600)),
              ),
            ],
            const SizedBox(height: 18),
            _loading
                ? const Center(child: CircularProgressIndicator(color: AppColors.pink))
                : LapehButton(label: tr('create_send'), icon: Icons.arrow_forward, onPressed: _submit),
          ],
        ),
      ),
    );
  }
}
