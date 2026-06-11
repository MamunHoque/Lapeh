import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:cached_network_image/cached_network_image.dart';
import '../../core/theme.dart';
import '../../core/i18n.dart';
import '../../core/app_state.dart';
import '../../core/models/user_model.dart';
import '../../core/providers/auth_provider.dart';
import '../../core/providers/notification_provider.dart';
import '../../core/services/app_config_service.dart';
import '../../shared/widgets.dart';
import 'edit_profile_screen.dart';
import 'change_password_screen.dart';
import 'notifications_screen.dart';
import 'help_support_screen.dart';

class ProfileScreen extends ConsumerStatefulWidget {
  const ProfileScreen({super.key});
  @override
  ConsumerState<ProfileScreen> createState() => _ProfileScreenState();
}

class _ProfileScreenState extends ConsumerState<ProfileScreen> {
  bool _loggingOut = false;

  Future<void> _logout() async {
    setState(() => _loggingOut = true);
    await ref.read(authProvider.notifier).logout();
    // GoRouter redirect will navigate to /login
  }

  void _toggleLanguage() {
    setState(toggleLocale);
    final code = localeNotifier.value.languageCode;
    ref.read(authProvider.notifier).updateLocale(code).catchError((_) {});
  }

  void _push(Widget screen) {
    Navigator.push(context, MaterialPageRoute(builder: (_) => screen));
  }

  @override
  Widget build(BuildContext context) {
    final user = ref.watch(authProvider).valueOrNull;
    final unread = ref.watch(unreadCountProvider).valueOrNull ?? 0;
    final isSender = user?.isSender ?? false;
    final sender = user?.sender;

    return ListView(
      padding: const EdgeInsets.all(16),
      children: [
        const SizedBox(height: 8),
        // ── Header ───────────────────────────────────────────────────────
        Center(
          child: GestureDetector(
            onTap: () => _push(const EditProfileScreen()),
            child: Stack(children: [
              _avatar(user),
              Positioned(
                right: 0, bottom: 0,
                child: Container(
                  padding: const EdgeInsets.all(5),
                  decoration: BoxDecoration(
                    color: AppColors.pink,
                    shape: BoxShape.circle,
                    border: Border.all(color: Colors.white, width: 2),
                  ),
                  child: const Icon(Icons.camera_alt, size: 12, color: Colors.white),
                ),
              ),
            ]),
          ),
        ),
        const SizedBox(height: 12),
        Center(child: Text(user?.name ?? '—', style: T.h2)),
        const SizedBox(height: 6),
        if (isSender)
          Center(child: _typeBadge(sender)),
        const SizedBox(height: 8),
        Center(
          child: Row(mainAxisSize: MainAxisSize.min, children: [
            const Icon(Icons.phone, size: 13, color: AppColors.slate),
            const SizedBox(width: 4),
            Text(user?.phone ?? '', style: T.muted),
            if (user?.phoneVerified ?? false) ...[
              const SizedBox(width: 6),
              const Icon(Icons.verified, size: 14, color: AppColors.green),
              const SizedBox(width: 2),
              Text(tr('verified'), style: const TextStyle(fontSize: 11, color: AppColors.green, fontWeight: FontWeight.w700)),
            ],
          ]),
        ),
        const SizedBox(height: 20),

        // ── Account info (sender) ────────────────────────────────────────
        if (isSender && sender != null) ...[
          SectionHeader(tr('account_info')),
          const SizedBox(height: 10),
          AppCard(
            child: Column(children: [
              if (sender.isBusiness) ...[
                _infoRow(tr('business_name'), sender.businessName),
                _infoRow(tr('business_category'), sender.businessCategory),
                _infoRow(tr('contact_person'), sender.contactPersonName),
              ] else
                _infoRow(tr('full_name'), user?.name),
              _infoRow(tr('default_pickup'), sender.defaultPickupAddress, last: true),
            ]),
          ),
          const SizedBox(height: 18),
        ],

        // ── Settings ─────────────────────────────────────────────────────
        AppCard(
          padding: EdgeInsets.zero,
          child: Column(children: [
            _row(Icons.edit_outlined, tr('edit_profile'),
                trailing: const Icon(Icons.chevron_right, color: AppColors.slate2),
                onTap: () => _push(const EditProfileScreen())),
            const Divider(height: 1, color: AppColors.line),
            _row(Icons.lock_outline, tr('change_password'),
                trailing: const Icon(Icons.chevron_right, color: AppColors.slate2),
                onTap: () => _push(const ChangePasswordScreen())),
            const Divider(height: 1, color: AppColors.line),
            _row(Icons.language, tr('language'),
                trailing: Text(isArabic ? 'العربية' : 'English', style: T.muted),
                onTap: _toggleLanguage),
            const Divider(height: 1, color: AppColors.line),
            _row(Icons.notifications_none, tr('notifications'),
                trailing: Row(mainAxisSize: MainAxisSize.min, children: [
                  if (unread > 0) _badge(unread),
                  const Icon(Icons.chevron_right, color: AppColors.slate2),
                ]),
                onTap: () async {
                  await Navigator.push(context, MaterialPageRoute(builder: (_) => const NotificationsScreen()));
                  ref.read(unreadCountProvider.notifier).refresh();
                }),
            const Divider(height: 1, color: AppColors.line),
            _row(Icons.help_outline, tr('help_support'),
                trailing: const Icon(Icons.chevron_right, color: AppColors.slate2),
                onTap: () => _push(const HelpSupportScreen())),
          ]),
        ),
        const SizedBox(height: 16),
        _loggingOut
            ? const Center(child: CircularProgressIndicator(color: AppColors.pink))
            : LapehButton(label: tr('logout'), icon: Icons.logout, ghost: true, onPressed: _logout),
        const SizedBox(height: 8),
        Center(child: Text('${AppConfigService.instance.current.appName} · v1.0.0', style: T.mutedSm.copyWith(color: AppColors.slate2))),
      ],
    );
  }

  Widget _avatar(UserModel? user) {
    const r = 40.0;
    final url = user?.avatar;
    if (url != null && url.isNotEmpty) {
      return CircleAvatar(radius: r, backgroundColor: AppColors.line, backgroundImage: CachedNetworkImageProvider(url));
    }
    return CircleAvatar(
      radius: r,
      backgroundColor: AppColors.ink,
      child: Text(_initials(user?.name ?? '?'),
          style: const TextStyle(color: Colors.white, fontWeight: FontWeight.w800, fontSize: 22)),
    );
  }

  Widget _typeBadge(SenderProfile? s) {
    final biz = s?.isBusiness ?? false;
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 4),
      decoration: BoxDecoration(
        color: biz ? AppColors.indigoSoft : AppColors.pinkSoft,
        borderRadius: BorderRadius.circular(999),
      ),
      child: Row(mainAxisSize: MainAxisSize.min, children: [
        Icon(biz ? Icons.business : Icons.person, size: 13, color: biz ? AppColors.indigo : AppColors.pink),
        const SizedBox(width: 5),
        Text(biz ? tr('business_account') : tr('individual_account'),
            style: TextStyle(fontSize: 11.5, fontWeight: FontWeight.w700, color: biz ? AppColors.indigo : AppColors.pinkDeep)),
      ]),
    );
  }

  Widget _badge(int count) => Container(
        margin: const EdgeInsetsDirectional.only(end: 4),
        padding: const EdgeInsets.symmetric(horizontal: 7, vertical: 2),
        decoration: const BoxDecoration(color: AppColors.pink, borderRadius: BorderRadius.all(Radius.circular(999))),
        child: Text('$count', style: const TextStyle(color: Colors.white, fontSize: 11, fontWeight: FontWeight.w800)),
      );

  Widget _infoRow(String label, String? value, {bool last = false}) {
    return Padding(
      padding: EdgeInsets.only(bottom: last ? 0 : 12),
      child: Row(crossAxisAlignment: CrossAxisAlignment.start, children: [
        SizedBox(width: 120, child: Text(label, style: T.muted)),
        Expanded(child: Text((value == null || value.isEmpty) ? '—' : value, style: const TextStyle(fontSize: 13, fontWeight: FontWeight.w600))),
      ]),
    );
  }

  Widget _row(IconData icon, String label, {Widget? trailing, VoidCallback? onTap}) {
    return ListTile(
      onTap: onTap,
      leading: Container(
        width: 36, height: 36,
        decoration: BoxDecoration(color: AppColors.pinkSoft, borderRadius: BorderRadius.circular(10)),
        child: Icon(icon, size: 18, color: AppColors.pink),
      ),
      title: Text(label, style: const TextStyle(fontSize: 14, fontWeight: FontWeight.w600)),
      trailing: trailing,
    );
  }

  String _initials(String n) {
    final parts = n.trim().split(' ').where((e) => e.isNotEmpty).toList();
    if (parts.isEmpty) return '?';
    if (parts.length == 1) {
      final p = parts.first;
      return (p.length >= 2 ? p.substring(0, 2) : p).toUpperCase();
    }
    return (parts.first[0] + parts.last[0]).toUpperCase();
  }
}
