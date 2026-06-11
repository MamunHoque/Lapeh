import 'dart:typed_data';
import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:image_picker/image_picker.dart';
import 'package:cached_network_image/cached_network_image.dart';
import '../../core/theme.dart';
import '../../core/i18n.dart';
import '../../core/api_client.dart';
import '../../core/services/auth_service.dart';
import '../../core/services/sender_service.dart';
import '../../core/services/location_service.dart';
import '../../core/providers/auth_provider.dart';
import '../../shared/widgets.dart';

class EditProfileScreen extends ConsumerStatefulWidget {
  const EditProfileScreen({super.key});
  @override
  ConsumerState<EditProfileScreen> createState() => _EditProfileScreenState();
}

class _EditProfileScreenState extends ConsumerState<EditProfileScreen> {
  final _name = TextEditingController();
  final _email = TextEditingController();
  final _bizName = TextEditingController();
  final _bizCategory = TextEditingController();
  final _contact = TextEditingController();
  final _pickup = TextEditingController();
  double? _lat, _lng;

  Uint8List? _photoBytes;
  String _photoName = 'avatar.jpg';
  bool _loading = false;
  String? _error;

  @override
  void initState() {
    super.initState();
    final user = ref.read(authProvider).valueOrNull;
    _name.text = user?.name ?? '';
    _email.text = user?.email ?? '';
    final s = user?.sender;
    _bizName.text = s?.businessName ?? '';
    _bizCategory.text = s?.businessCategory ?? '';
    _contact.text = s?.contactPersonName ?? '';
    _pickup.text = s?.defaultPickupAddress ?? '';
    _lat = s?.defaultPickupLat;
    _lng = s?.defaultPickupLng;
  }

  @override
  void dispose() {
    _name.dispose(); _email.dispose(); _bizName.dispose();
    _bizCategory.dispose(); _contact.dispose(); _pickup.dispose();
    super.dispose();
  }

  Future<void> _pickPhoto() async {
    final x = await ImagePicker().pickImage(source: ImageSource.gallery, maxWidth: 1024, imageQuality: 80);
    if (x == null) return;
    final bytes = await x.readAsBytes();
    if (!mounted) return;
    setState(() {
      _photoBytes = bytes;
      _photoName = x.name.isNotEmpty ? x.name : 'avatar.jpg';
    });
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
    if (address != null && mounted) setState(() => _pickup.text = address);
  }

  Future<void> _save() async {
    final name = _name.text.trim();
    if (name.isEmpty) {
      setState(() => _error = tr('name_required'));
      return;
    }
    final user = ref.read(authProvider).valueOrNull;
    final isSender = user?.isSender ?? false;
    final isBiz = user?.sender?.isBusiness ?? false;

    setState(() { _loading = true; _error = null; });
    try {
      var updated = user;

      // Sender-specific fields (also persists name) — returns full user payload.
      if (isSender) {
        updated = await SenderService().updateProfile(
          name: name,
          defaultPickupAddress: _pickup.text.trim().isEmpty ? null : _pickup.text.trim(),
          defaultPickupLat: _lat,
          defaultPickupLng: _lng,
          businessName: isBiz && _bizName.text.trim().isNotEmpty ? _bizName.text.trim() : null,
          businessCategory: isBiz && _bizCategory.text.trim().isNotEmpty ? _bizCategory.text.trim() : null,
          contactPersonName: isBiz && _contact.text.trim().isNotEmpty ? _contact.text.trim() : null,
        );
      }

      // Name/email/avatar via the shared auth endpoint. Always call so non-sender
      // roles and email/avatar changes are covered; response is the final user.
      updated = await AuthService().updateProfile(
        name: name,
        email: _email.text.trim().isEmpty ? null : _email.text.trim(),
        avatarBytes: _photoBytes,
        avatarFilename: _photoName,
      );

      ref.read(authProvider.notifier).setUser(updated);
      if (!mounted) return;
      ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text(tr('profile_updated'))));
      Navigator.pop(context);
    } catch (e) {
      setState(() => _error = apiErrorMessage(e));
    } finally {
      if (mounted) setState(() => _loading = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    final user = ref.watch(authProvider).valueOrNull;
    final isBiz = user?.sender?.isBusiness ?? false;
    final isSender = user?.isSender ?? false;

    return Scaffold(
      appBar: AppBar(title: Text(tr('edit_profile'))),
      body: SafeArea(
        child: ListView(
          padding: const EdgeInsets.fromLTRB(20, 8, 20, 28),
          children: [
            Center(
              child: GestureDetector(
                onTap: _pickPhoto,
                child: Stack(
                  children: [
                    _avatar(user?.avatar),
                    Positioned(
                      right: 0, bottom: 0,
                      child: Container(
                        padding: const EdgeInsets.all(6),
                        decoration: BoxDecoration(
                          color: AppColors.pink,
                          shape: BoxShape.circle,
                          border: Border.all(color: Colors.white, width: 2),
                        ),
                        child: const Icon(Icons.camera_alt, size: 14, color: Colors.white),
                      ),
                    ),
                  ],
                ),
              ),
            ),
            const SizedBox(height: 8),
            Center(child: TextButton(onPressed: _pickPhoto, child: Text(tr('pick_photo')))),
            const SizedBox(height: 8),
            LabeledField(label: tr('full_name'), icon: Icons.person_outline, controller: _name),
            const SizedBox(height: 12),
            LabeledField(label: tr('email_optional'), hint: 'name@example.com', icon: Icons.mail_outline,
                keyboardType: TextInputType.emailAddress, controller: _email),
            if (isBiz) ...[
              const SizedBox(height: 12),
              LabeledField(label: tr('business_name'), icon: Icons.storefront_outlined, controller: _bizName),
              const SizedBox(height: 12),
              LabeledField(label: tr('business_category'), icon: Icons.category_outlined, controller: _bizCategory),
              const SizedBox(height: 12),
              LabeledField(label: tr('contact_person'), icon: Icons.badge_outlined, controller: _contact),
            ],
            if (isSender) ...[
              const SizedBox(height: 12),
              LabeledField(label: tr('default_pickup'), hint: tr('default_pickup_hint'),
                  icon: Icons.location_on_outlined, maxLines: 2, controller: _pickup),
              const SizedBox(height: 8),
              OutlinedButton.icon(
                onPressed: _useCurrentLocation,
                icon: const Icon(Icons.my_location, size: 18),
                label: Text(_lat != null ? tr('location_captured') : tr('use_current_location')),
              ),
            ],
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
                : LapehButton(label: tr('save_changes'), icon: Icons.check, onPressed: _save),
          ],
        ),
      ),
    );
  }

  Widget _avatar(String? url) {
    const r = 44.0;
    if (_photoBytes != null) {
      return CircleAvatar(radius: r, backgroundImage: MemoryImage(_photoBytes!));
    }
    if (url != null && url.isNotEmpty) {
      return CircleAvatar(
        radius: r,
        backgroundColor: AppColors.line,
        backgroundImage: CachedNetworkImageProvider(url),
      );
    }
    return CircleAvatar(
      radius: r,
      backgroundColor: AppColors.ink,
      child: Text(_initials(_name.text), style: const TextStyle(color: Colors.white, fontWeight: FontWeight.w800, fontSize: 24)),
    );
  }

  String _initials(String n) {
    final parts = n.trim().split(' ').where((e) => e.isNotEmpty).toList();
    if (parts.isEmpty) return '?';
    if (parts.length == 1) return (parts.first.length >= 2 ? parts.first.substring(0, 2) : parts.first).toUpperCase();
    return (parts.first[0] + parts.last[0]).toUpperCase();
  }
}
