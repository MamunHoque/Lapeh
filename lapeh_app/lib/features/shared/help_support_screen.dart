import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:url_launcher/url_launcher.dart';
import '../../core/theme.dart';
import '../../core/i18n.dart';
import '../../core/app_state.dart';
import '../../core/services/meta_service.dart';
import '../../core/providers/notification_provider.dart';
import '../../shared/widgets.dart';

class HelpSupportScreen extends ConsumerWidget {
  const HelpSupportScreen({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final async = ref.watch(supportProvider);
    return Scaffold(
      appBar: AppBar(title: Text(tr('help_support'))),
      body: async.when(
        loading: () => const Center(child: CircularProgressIndicator(color: AppColors.pink)),
        error: (e, _) => ErrorRetry(error: e, onRetry: () => ref.invalidate(supportProvider)),
        data: (s) => _HelpBody(s: s),
      ),
    );
  }
}

class _HelpBody extends StatelessWidget {
  final SupportInfo s;
  const _HelpBody({required this.s});

  Future<void> _launch(Uri uri) async {
    if (await canLaunchUrl(uri)) {
      await launchUrl(uri, mode: LaunchMode.externalApplication);
    }
  }

  @override
  Widget build(BuildContext context) {
    final ar = isArabic;
    return ListView(
      padding: const EdgeInsets.fromLTRB(16, 14, 16, 28),
      children: [
        SectionHeader(tr('contact_support')),
        const SizedBox(height: 10),
        if (s.phone != null && s.phone!.isNotEmpty)
          _ContactCard(
            icon: Icons.call,
            color: AppColors.green,
            label: tr('call_us'),
            value: s.phone!,
            onTap: () => _launch(Uri(scheme: 'tel', path: s.phone)),
          ),
        if (s.email != null && s.email!.isNotEmpty)
          _ContactCard(
            icon: Icons.mail_outline,
            color: AppColors.blue,
            label: tr('email_us'),
            value: s.email!,
            onTap: () => _launch(Uri(scheme: 'mailto', path: s.email)),
          ),
        if (s.whatsapp != null && s.whatsapp!.isNotEmpty)
          _ContactCard(
            icon: Icons.chat,
            color: const Color(0xFF25D366),
            label: tr('whatsapp'),
            value: s.whatsapp!,
            onTap: () => _launch(Uri.parse('https://wa.me/${s.whatsapp!.replaceAll(RegExp(r'[^0-9]'), '')}')),
          ),
        const SizedBox(height: 22),
        SectionHeader(tr('faq')),
        const SizedBox(height: 10),
        ...s.faq.map((f) => Padding(
              padding: const EdgeInsets.only(bottom: 8),
              child: AppCard(
                padding: EdgeInsets.zero,
                child: Theme(
                  data: Theme.of(context).copyWith(dividerColor: Colors.transparent),
                  child: ExpansionTile(
                    tilePadding: const EdgeInsets.symmetric(horizontal: 14),
                    childrenPadding: const EdgeInsets.fromLTRB(14, 0, 14, 14),
                    title: Text(f.question(ar), style: const TextStyle(fontSize: 13.5, fontWeight: FontWeight.w700)),
                    iconColor: AppColors.pink,
                    collapsedIconColor: AppColors.slate2,
                    children: [
                      Align(
                        alignment: AlignmentDirectional.centerStart,
                        child: Text(f.answer(ar), style: T.muted),
                      ),
                    ],
                  ),
                ),
              ),
            )),
      ],
    );
  }
}

class _ContactCard extends StatelessWidget {
  final IconData icon;
  final Color color;
  final String label, value;
  final VoidCallback onTap;
  const _ContactCard({required this.icon, required this.color, required this.label, required this.value, required this.onTap});

  @override
  Widget build(BuildContext context) {
    return Padding(
      padding: const EdgeInsets.only(bottom: 8),
      child: GestureDetector(
        onTap: onTap,
        child: AppCard(
          padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 13),
          child: Row(children: [
            Container(
              width: 38, height: 38,
              decoration: BoxDecoration(color: color.withValues(alpha: 0.12), borderRadius: BorderRadius.circular(11)),
              child: Icon(icon, size: 19, color: color),
            ),
            const SizedBox(width: 12),
            Expanded(
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text(label, style: const TextStyle(fontSize: 13.5, fontWeight: FontWeight.w700)),
                  Text(value, style: T.mutedSm),
                ],
              ),
            ),
            const Icon(Icons.chevron_right, color: AppColors.slate2),
          ]),
        ),
      ),
    );
  }
}
