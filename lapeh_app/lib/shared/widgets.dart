import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import '../core/theme.dart';
import '../core/i18n.dart';
import '../core/api_client.dart';
import '../core/status_meta.dart';

/// Copies [link] to the clipboard and shows a confirmation snackbar.
Future<void> copyLink(BuildContext context, String link) async {
  await Clipboard.setData(ClipboardData(text: link));
  if (context.mounted) {
    ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text(tr('link_copied'))));
  }
}

/// Compact pink copy-icon button for list rows / cards.
class CopyLinkIcon extends StatelessWidget {
  final String link;
  const CopyLinkIcon({super.key, required this.link});

  @override
  Widget build(BuildContext context) {
    return IconButton(
      onPressed: () => copyLink(context, link),
      tooltip: tr('copy_link'),
      visualDensity: VisualDensity.compact,
      constraints: const BoxConstraints(minWidth: 36, minHeight: 36),
      padding: EdgeInsets.zero,
      icon: const Icon(Icons.copy_rounded, size: 17, color: AppColors.pink),
    );
  }
}

/// Centered error message with a Retry button — for AsyncValue.error states.
class ErrorRetry extends StatelessWidget {
  final Object error;
  final VoidCallback onRetry;
  const ErrorRetry({super.key, required this.error, required this.onRetry});

  @override
  Widget build(BuildContext context) {
    return Center(
      child: Padding(
        padding: const EdgeInsets.all(28),
        child: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            const Icon(Icons.cloud_off_rounded, size: 40, color: AppColors.slate2),
            const SizedBox(height: 12),
            Text(apiErrorMessage(error),
                textAlign: TextAlign.center,
                style: const TextStyle(color: AppColors.slate, fontSize: 13.5)),
            const SizedBox(height: 16),
            SizedBox(
              width: 160,
              child: LapehButton(label: tr('retry'), icon: Icons.refresh, ghost: true, onPressed: onRetry),
            ),
          ],
        ),
      ),
    );
  }
}

/// Centered empty-state message with an optional icon.
class EmptyState extends StatelessWidget {
  final String message;
  final IconData icon;
  const EmptyState({super.key, required this.message, this.icon = Icons.inbox_outlined});

  @override
  Widget build(BuildContext context) {
    return Center(
      child: Padding(
        padding: const EdgeInsets.all(32),
        child: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            Icon(icon, size: 38, color: AppColors.slate2),
            const SizedBox(height: 10),
            Text(message, textAlign: TextAlign.center, style: const TextStyle(color: AppColors.slate)),
          ],
        ),
      ),
    );
  }
}

/// Primary gradient button
class LapehButton extends StatelessWidget {
  final String label;
  final IconData? icon;
  final VoidCallback? onPressed;
  final bool ghost;
  const LapehButton({super.key, required this.label, this.icon, this.onPressed, this.ghost = false});

  @override
  Widget build(BuildContext context) {
    return SizedBox(
      width: double.infinity,
      child: Material(
        color: Colors.transparent,
        child: InkWell(
          onTap: onPressed,
          borderRadius: BorderRadius.circular(14),
          child: Ink(
            decoration: BoxDecoration(
              gradient: ghost ? null : const LinearGradient(colors: [Color(0xFFFF2D86), AppColors.pink]),
              color: ghost ? Colors.white : null,
              border: ghost ? Border.all(color: AppColors.line, width: 1.4) : null,
              borderRadius: BorderRadius.circular(14),
              boxShadow: ghost ? null : const [BoxShadow(color: Color(0x66FB0E72), blurRadius: 22, offset: Offset(0, 12))],
            ),
            child: Padding(
              padding: const EdgeInsets.symmetric(vertical: 15),
              child: Row(
                mainAxisAlignment: MainAxisAlignment.center,
                children: [
                  if (icon != null) ...[
                    Icon(icon, size: 19, color: ghost ? AppColors.ink : Colors.white),
                    const SizedBox(width: 8),
                  ],
                  Text(label,
                      style: TextStyle(
                          fontSize: 15, fontWeight: FontWeight.w700, color: ghost ? AppColors.ink : Colors.white)),
                ],
              ),
            ),
          ),
        ),
      ),
    );
  }
}

/// Status badge
class StatusBadge extends StatelessWidget {
  final String status;
  const StatusBadge({super.key, required this.status});

  @override
  Widget build(BuildContext context) {
    final m = statusMeta(status);
    final c = _toneColors(m.tone);
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 5),
      decoration: BoxDecoration(color: c.$1, borderRadius: BorderRadius.circular(999)),
      child: Text(m.label, style: TextStyle(fontSize: 11, fontWeight: FontWeight.w700, color: c.$2)),
    );
  }
}

(Color, Color) _toneColors(String tone) {
  switch (tone) {
    case 'green':
      return (AppColors.greenSoft, AppColors.green);
    case 'blue':
      return (AppColors.blueSoft, AppColors.blue);
    case 'indigo':
      return (AppColors.indigoSoft, AppColors.indigo);
    case 'pink':
      return (AppColors.pinkSoft, AppColors.pinkDeep);
    case 'amber':
      return (AppColors.amberSoft, const Color(0xFFB97B00));
    case 'red':
      return (AppColors.redSoft, AppColors.red);
    default:
      return (const Color(0xFFEEF0F4), AppColors.slate);
  }
}

/// White card container
class AppCard extends StatelessWidget {
  final Widget child;
  final EdgeInsets padding;
  const AppCard({super.key, required this.child, this.padding = const EdgeInsets.all(14)});

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: padding,
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(16),
        border: Border.all(color: AppColors.line),
        boxShadow: const [BoxShadow(color: Color(0x0F141A2B), blurRadius: 24, offset: Offset(0, 12))],
      ),
      child: child,
    );
  }
}

/// Small stat tile (icon + label + value)
class StatTile extends StatelessWidget {
  final IconData icon;
  final String label;
  final String value;
  final Color tone;
  final Color toneBg;
  const StatTile({super.key, required this.icon, required this.label, required this.value, required this.tone, required this.toneBg});

  @override
  Widget build(BuildContext context) {
    return AppCard(
      padding: const EdgeInsets.all(13),
      child: Row(children: [
        Container(
          width: 40, height: 40,
          decoration: BoxDecoration(color: toneBg, borderRadius: BorderRadius.circular(11)),
          child: Icon(icon, color: tone, size: 19),
        ),
        const SizedBox(width: 11),
        Expanded(
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Text(label, style: const TextStyle(fontSize: 11.5, color: AppColors.slate, fontWeight: FontWeight.w500)),
              const SizedBox(height: 1),
              Text(value, style: const TextStyle(fontSize: 20, fontWeight: FontWeight.w800, color: AppColors.ink)),
            ],
          ),
        ),
      ]),
    );
  }
}

/// Vertical status timeline
class StatusStep {
  final String label;
  final String state; // done | active | todo
  const StatusStep(this.label, this.state);
}

class StatusTimeline extends StatelessWidget {
  final List<StatusStep> steps;
  const StatusTimeline({super.key, required this.steps});

  @override
  Widget build(BuildContext context) {
    return Column(
      children: List.generate(steps.length, (i) {
        final s = steps[i];
        final last = i == steps.length - 1;
        final done = s.state == 'done';
        final active = s.state == 'active';
        return IntrinsicHeight(
          child: Row(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Column(children: [
                Container(
                  width: 22, height: 22,
                  decoration: BoxDecoration(
                    color: done ? AppColors.green : Colors.white,
                    shape: BoxShape.circle,
                    border: Border.all(color: done ? AppColors.green : (active ? AppColors.pink : AppColors.line), width: 2),
                    boxShadow: active ? [BoxShadow(color: AppColors.pinkSoft, blurRadius: 0, spreadRadius: 3)] : null,
                  ),
                  child: done
                      ? const Icon(Icons.check, size: 13, color: Colors.white)
                      : (active ? Center(child: Container(width: 8, height: 8, decoration: const BoxDecoration(color: AppColors.pink, shape: BoxShape.circle))) : null),
                ),
                if (!last)
                  Expanded(child: Container(width: 2, color: done ? AppColors.green : AppColors.line)),
              ]),
              const SizedBox(width: 12),
              Padding(
                padding: const EdgeInsets.only(top: 2, bottom: 14),
                child: Text(s.label,
                    style: TextStyle(
                        fontSize: 13,
                        fontWeight: active ? FontWeight.w700 : FontWeight.w600,
                        color: done ? AppColors.ink : (active ? AppColors.pinkDeep : AppColors.slate))),
              ),
            ],
          ),
        );
      }),
    );
  }
}

/// Labeled input field (display only for prototype)
class LabeledField extends StatelessWidget {
  final String label;
  final String hint;
  final IconData? icon;
  final TextEditingController? controller;
  final TextInputType? keyboardType;
  final bool obscure;
  final int maxLines;
  final ValueChanged<String>? onChanged;
  const LabeledField({
    super.key,
    required this.label,
    this.hint = '',
    this.icon,
    this.controller,
    this.keyboardType,
    this.obscure = false,
    this.maxLines = 1,
    this.onChanged,
  });

  @override
  Widget build(BuildContext context) {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Text(label, style: const TextStyle(fontSize: 12, fontWeight: FontWeight.w700, color: AppColors.ink2)),
        const SizedBox(height: 6),
        TextField(
          controller: controller,
          keyboardType: keyboardType,
          obscureText: obscure,
          maxLines: maxLines,
          onChanged: onChanged,
          style: const TextStyle(fontSize: 14, fontWeight: FontWeight.w500),
          decoration: InputDecoration(
            hintText: hint,
            hintStyle: const TextStyle(color: AppColors.slate2, fontWeight: FontWeight.w400),
            prefixIcon: icon != null ? Icon(icon, size: 19, color: AppColors.slate2) : null,
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

/// Section header row
class SectionHeader extends StatelessWidget {
  final String title;
  final String? action;
  final VoidCallback? onAction;
  const SectionHeader(this.title, {super.key, this.action, this.onAction});

  @override
  Widget build(BuildContext context) {
    return Row(
      mainAxisAlignment: MainAxisAlignment.spaceBetween,
      children: [
        Text(title, style: T.title),
        if (action != null)
          GestureDetector(
            onTap: onAction,
            child: Text(action!, style: const TextStyle(color: AppColors.pink, fontSize: 12.5, fontWeight: FontWeight.w700)),
          ),
      ],
    );
  }
}
