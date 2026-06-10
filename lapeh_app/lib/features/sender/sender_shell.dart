import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import '../../core/theme.dart';
import '../../core/i18n.dart';
import '../../core/providers/sender_provider.dart';
import '../shared/profile_screen.dart';
import 'dashboard_screen.dart';
import 'deliveries_screen.dart';
import 'reports_screen.dart';

class SenderShell extends ConsumerWidget {
  const SenderShell({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final index = ref.watch(senderTabProvider);
    const pages = [
      DashboardScreen(),
      DeliveriesScreen(),
      ReportsScreen(),
      ProfileScreen(),
    ];
    return Scaffold(
      body: SafeArea(bottom: false, child: IndexedStack(index: index, children: pages)),
      bottomNavigationBar: NavigationBarTheme(
        data: NavigationBarThemeData(
          backgroundColor: Colors.white,
          indicatorColor: AppColors.pinkSoft,
        ),
        child: NavigationBar(
          height: 64,
          selectedIndex: index,
          onDestinationSelected: (i) => ref.read(senderTabProvider.notifier).state = i,
          destinations: [
            NavigationDestination(icon: const Icon(Icons.home_outlined), selectedIcon: const Icon(Icons.home, color: AppColors.pink), label: tr('home')),
            NavigationDestination(icon: const Icon(Icons.list_alt_outlined), selectedIcon: const Icon(Icons.list_alt, color: AppColors.pink), label: tr('deliveries')),
            NavigationDestination(icon: const Icon(Icons.bar_chart_outlined), selectedIcon: const Icon(Icons.bar_chart, color: AppColors.pink), label: tr('reports')),
            NavigationDestination(icon: const Icon(Icons.person_outline), selectedIcon: const Icon(Icons.person, color: AppColors.pink), label: tr('profile')),
          ],
        ),
      ),
    );
  }
}
