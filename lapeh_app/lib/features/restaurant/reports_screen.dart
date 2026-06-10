import 'package:flutter/material.dart';
import '../../core/theme.dart';
import '../../shared/widgets.dart';

class ReportsScreen extends StatelessWidget {
  const ReportsScreen({super.key});

  @override
  Widget build(BuildContext context) {
    return ListView(
      padding: const EdgeInsets.fromLTRB(16, 12, 16, 24),
      children: [
        const Text('Reports', style: T.h1),
        const SizedBox(height: 14),
        Container(
          padding: const EdgeInsets.all(16),
          decoration: BoxDecoration(
            gradient: const LinearGradient(colors: [Color(0xFFFF3A8D), AppColors.pinkDeep]),
            borderRadius: BorderRadius.circular(18),
          ),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              const Text('Delivery revenue · today', style: TextStyle(color: Colors.white70, fontSize: 11.5, fontWeight: FontWeight.w600)),
              const SizedBox(height: 4),
              Row(crossAxisAlignment: CrossAxisAlignment.baseline, textBaseline: TextBaseline.alphabetic, children: const [
                Text('312.40', style: TextStyle(color: Colors.white, fontSize: 30, fontWeight: FontWeight.w800)),
                SizedBox(width: 6),
                Text('AED', style: TextStyle(color: Colors.white70, fontSize: 14, fontWeight: FontWeight.w700)),
              ]),
              const SizedBox(height: 6),
              Container(
                padding: const EdgeInsets.symmetric(horizontal: 9, vertical: 3),
                decoration: BoxDecoration(color: Colors.white24, borderRadius: BorderRadius.circular(999)),
                child: const Text('▲ 12% vs yesterday', style: TextStyle(color: Colors.white, fontSize: 11, fontWeight: FontWeight.w700)),
              ),
            ],
          ),
        ),
        const SizedBox(height: 14),
        Row(children: const [
          Expanded(child: _Mini('Orders', '18')),
          SizedBox(width: 8),
          Expanded(child: _Mini('Completed', '16')),
          SizedBox(width: 8),
          Expanded(child: _Mini('Avg fee', '17 AED')),
        ]),
        const SizedBox(height: 18),
        const SectionHeader('Recent', action: 'Export'),
        const SizedBox(height: 10),
        _histRow('#2039 · Layla', 'Delivered · 11.5 AED fee', 'delivered'),
        _histRow('#2038 · Yousef', 'Delivered · 14.0 AED fee', 'delivered'),
        _histRow('#2037 · Mariam', 'Cancelled · refunded', 'cancelled'),
      ],
    );
  }

  Widget _histRow(String title, String sub, String status) => Padding(
        padding: const EdgeInsets.only(bottom: 10),
        child: AppCard(
          child: Row(children: [
            Expanded(
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text(title, style: const TextStyle(fontSize: 13, fontWeight: FontWeight.w700)),
                  Text(sub, style: T.mutedSm),
                ],
              ),
            ),
            StatusBadge(status: status),
          ]),
        ),
      );
}

class _Mini extends StatelessWidget {
  final String label, value;
  const _Mini(this.label, this.value);
  @override
  Widget build(BuildContext context) {
    return AppCard(
      padding: const EdgeInsets.all(11),
      child: Column(children: [
        Text(label, style: const TextStyle(fontSize: 10.5, color: AppColors.slate, fontWeight: FontWeight.w600)),
        const SizedBox(height: 2),
        Text(value, style: const TextStyle(fontSize: 18, fontWeight: FontWeight.w800)),
      ]),
    );
  }
}
