import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import '../../core/theme.dart';
import '../../core/i18n.dart';
import '../../core/app_state.dart';
import '../../core/providers/auth_provider.dart';
import '../../shared/widgets.dart';

class ProfileScreen extends ConsumerStatefulWidget {
  // Legacy params kept for compatibility — real data comes from authProvider
  final String name;
  final String subtitle;
  const ProfileScreen({super.key, required this.name, required this.subtitle});
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

  @override
  Widget build(BuildContext context) {
    final user = ref.watch(authProvider).valueOrNull;
    final displayName = user?.name ?? widget.name;
    final displaySub = user != null
        ? '${user.isDriver ? "Driver" : "Restaurant"} · ${user.phone}'
        : widget.subtitle;

    return ListView(
      padding: const EdgeInsets.all(16),
      children: [
        const SizedBox(height: 8),
        Center(
          child: CircleAvatar(
            radius: 38,
            backgroundColor: AppColors.ink,
            child: Text(_initials(displayName),
                style: const TextStyle(color: Colors.white, fontWeight: FontWeight.w800, fontSize: 22)),
          ),
        ),
        const SizedBox(height: 12),
        Center(child: Text(displayName, style: T.h2)),
        const SizedBox(height: 2),
        Center(child: Text(displaySub, style: T.muted)),
        const SizedBox(height: 22),
        AppCard(
          padding: EdgeInsets.zero,
          child: Column(children: [
            _row(Icons.language, tr('language'),
                trailing: Text(isArabic ? 'العربية' : 'English', style: T.muted),
                onTap: () => setState(toggleLocale)),
            const Divider(height: 1, color: AppColors.line),
            _row(Icons.notifications_none, 'Notifications', trailing: const Icon(Icons.chevron_right, color: AppColors.slate2)),
            const Divider(height: 1, color: AppColors.line),
            _row(Icons.help_outline, 'Help & Support', trailing: const Icon(Icons.chevron_right, color: AppColors.slate2)),
          ]),
        ),
        const SizedBox(height: 16),
        _loggingOut
            ? const Center(child: CircularProgressIndicator(color: AppColors.pink))
            : LapehButton(label: tr('logout'), icon: Icons.logout, ghost: true, onPressed: _logout),
        const SizedBox(height: 8),
        Center(child: Text('Lapeh · v1.0.0', style: T.mutedSm.copyWith(color: AppColors.slate2))),
      ],
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
