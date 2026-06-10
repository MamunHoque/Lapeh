import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import '../../core/theme.dart';
import '../../core/i18n.dart';
import '../../core/api_client.dart';
import '../../core/models/order_model.dart';
import '../../core/providers/auth_provider.dart';
import '../../core/providers/sender_provider.dart';
import '../../core/services/location_service.dart';
import '../../shared/widgets.dart';
import 'waiting_screen.dart';

/// Accepts +, digits, spaces, dashes; needs 7–15 digits total.
bool _isValidPhone(String s) {
  final digits = s.replaceAll(RegExp(r'[^0-9]'), '');
  return digits.length >= 7 && digits.length <= 15 && RegExp(r'^\+?[0-9 \-]+$').hasMatch(s);
}

class _ItemRow {
  final name = TextEditingController();
  final qty = TextEditingController(text: '1');
  final price = TextEditingController();
  final desc = TextEditingController();
  void dispose() { name.dispose(); qty.dispose(); price.dispose(); desc.dispose(); }
}

class CreateRequestScreen extends ConsumerStatefulWidget {
  const CreateRequestScreen({super.key});
  @override
  ConsumerState<CreateRequestScreen> createState() => _CreateRequestScreenState();
}

class _CreateRequestScreenState extends ConsumerState<CreateRequestScreen> {
  final _nameCtrl = TextEditingController();
  final _phoneCtrl = TextEditingController();
  final _notesCtrl = TextEditingController();
  final _pickupCtrl = TextEditingController();
  double? _pickupLat, _pickupLng;
  final List<_ItemRow> _items = [_ItemRow()];
  bool _loading = false;
  String? _error;

  @override
  void initState() {
    super.initState();
    // Prefill pickup from the sender's default.
    final sender = ref.read(authProvider).valueOrNull?.sender;
    if (sender != null) {
      _pickupCtrl.text = sender.defaultPickupAddress ?? '';
      _pickupLat = sender.defaultPickupLat;
      _pickupLng = sender.defaultPickupLng;
    }
  }

  @override
  void dispose() {
    _nameCtrl.dispose(); _phoneCtrl.dispose(); _notesCtrl.dispose(); _pickupCtrl.dispose();
    for (final i in _items) { i.dispose(); }
    super.dispose();
  }

  double get _total => _items.fold(0, (sum, i) {
        final q = int.tryParse(i.qty.text.trim()) ?? 0;
        final p = double.tryParse(i.price.text.trim()) ?? 0;
        return sum + q * p;
      });

  Future<void> _useCurrentLocation() async {
    final loc = LocationService();
    final pos = await loc.getCurrentPosition();
    if (pos == null || !mounted) return;
    setState(() {
      _pickupLat = pos.latitude;
      _pickupLng = pos.longitude;
      // Show coords immediately, then replace with a readable address.
      _pickupCtrl.text = '${pos.latitude.toStringAsFixed(5)}, ${pos.longitude.toStringAsFixed(5)}';
    });
    final address = await loc.reverseGeocode(pos.latitude, pos.longitude);
    if (address != null && mounted) {
      setState(() => _pickupCtrl.text = address);
    }
  }

  Future<void> _submit() async {
    final name = _nameCtrl.text.trim();
    final phone = _phoneCtrl.text.trim();

    if (name.isEmpty || phone.isEmpty) {
      setState(() => _error = tr('error_fields'));
      return;
    }
    if (!_isValidPhone(phone)) {
      setState(() => _error = tr('error_phone_format'));
      return;
    }

    final items = <OrderItem>[];
    for (final row in _items) {
      final n = row.name.text.trim();
      if (n.isEmpty) continue;
      final q = int.tryParse(row.qty.text.trim()) ?? 0;
      final p = double.tryParse(row.price.text.trim()) ?? 0;
      if (q <= 0) { setState(() => _error = tr('error_item_qty')); return; }
      items.add(OrderItem(name: n, quantity: q, unitPrice: p, totalPrice: q * p,
          description: row.desc.text.trim().isEmpty ? null : row.desc.text.trim()));
    }
    if (items.isEmpty) {
      setState(() => _error = tr('error_no_items'));
      return;
    }

    setState(() { _loading = true; _error = null; });
    try {
      final result = await ref.read(senderServiceProvider).createOrder(
        customerName: name,
        customerPhone: phone,
        items: items,
        pickupAddress: _pickupCtrl.text.trim().isEmpty ? null : _pickupCtrl.text.trim(),
        pickupLat: _pickupLat,
        pickupLng: _pickupLng,
        notes: _notesCtrl.text.trim().isEmpty ? null : _notesCtrl.text.trim(),
      );
      if (!mounted) return;
      ref.invalidate(dashboardProvider);
      ref.invalidate(ordersProvider(null));
      Navigator.pushReplacement(
        context,
        MaterialPageRoute(builder: (_) => WaitingScreen(order: result.order)),
      );
    } catch (e) {
      setState(() => _error = apiErrorMessage(e));
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
            // ── Pickup ──────────────────────────────────────────────
            SectionHeader(tr('pickup_location')),
            const SizedBox(height: 8),
            LabeledField(label: tr('pickup_address'), hint: tr('default_pickup_hint'), icon: Icons.store_outlined, maxLines: 2, controller: _pickupCtrl),
            const SizedBox(height: 8),
            OutlinedButton.icon(
              onPressed: _useCurrentLocation,
              icon: const Icon(Icons.my_location, size: 18),
              label: Text(_pickupLat != null ? tr('location_captured') : tr('use_current_location')),
            ),
            const SizedBox(height: 18),

            // ── Receiver ────────────────────────────────────────────
            SectionHeader(tr('receiver')),
            const SizedBox(height: 8),
            LabeledField(label: tr('customer_name'), hint: 'Ahmed Khalil', icon: Icons.person_outline, controller: _nameCtrl),
            const SizedBox(height: 12),
            LabeledField(label: tr('mobile_number_label'), hint: '+971 50 123 4567', icon: Icons.phone_outlined, keyboardType: TextInputType.phone, controller: _phoneCtrl),
            const SizedBox(height: 18),

            // ── Package items ───────────────────────────────────────
            SectionHeader(tr('package_items')),
            const SizedBox(height: 8),
            ..._items.asMap().entries.map((e) => _itemCard(e.key, e.value)),
            TextButton.icon(
              onPressed: () => setState(() => _items.add(_ItemRow())),
              icon: const Icon(Icons.add, size: 18),
              label: Text(tr('add_item')),
            ),
            const SizedBox(height: 4),
            Row(mainAxisAlignment: MainAxisAlignment.spaceBetween, children: [
              Text(tr('total_value'), style: const TextStyle(fontWeight: FontWeight.w700)),
              Text('AED ${_total.toStringAsFixed(2)}', style: const TextStyle(fontWeight: FontWeight.w800, color: AppColors.pink)),
            ]),
            const SizedBox(height: 16),
            LabeledField(label: tr('notes_optional'), hint: tr('notes_hint'), maxLines: 2, controller: _notesCtrl),
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
                Expanded(child: Text(tr('sms_hint'), style: const TextStyle(fontSize: 11.5, fontWeight: FontWeight.w600, color: AppColors.pinkDeep))),
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

  Widget _itemCard(int index, _ItemRow row) {
    return Padding(
      padding: const EdgeInsets.only(bottom: 12),
      child: AppCard(
        child: Column(children: [
          Row(children: [
            Expanded(child: LabeledField(label: tr('item_name'), hint: 'Gift box', controller: row.name)),
            if (_items.length > 1)
              Padding(
                padding: const EdgeInsets.only(top: 18, left: 6),
                child: IconButton(
                  onPressed: () => setState(() { row.dispose(); _items.removeAt(index); }),
                  icon: const Icon(Icons.delete_outline, color: AppColors.red, size: 20),
                ),
              ),
          ]),
          const SizedBox(height: 10),
          Row(children: [
            Expanded(child: LabeledField(label: tr('quantity'), hint: '1', keyboardType: TextInputType.number, controller: row.qty, onChanged: (_) => setState(() {}))),
            const SizedBox(width: 10),
            Expanded(child: LabeledField(label: tr('unit_value'), hint: '0', keyboardType: const TextInputType.numberWithOptions(decimal: true), controller: row.price, onChanged: (_) => setState(() {}))),
          ]),
          const SizedBox(height: 10),
          LabeledField(label: tr('item_notes_optional'), hint: tr('item_notes_hint'), controller: row.desc),
        ]),
      ),
    );
  }
}
