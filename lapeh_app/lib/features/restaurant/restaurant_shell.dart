import 'package:flutter/material.dart';
import '../../core/theme.dart';
import '../../core/i18n.dart';
import '../shared/profile_screen.dart';
import 'dashboard_screen.dart';
import 'deliveries_screen.dart';
import 'reports_screen.dart';

class RestaurantShell extends StatefulWidget {
  const RestaurantShell({super.key});
  @override
  State<RestaurantShell> createState() => _RestaurantShellState();
}

class _RestaurantShellState extends State<RestaurantShell> {
  int index = 0;

  @override
  Widget build(BuildContext context) {
    final pages = const [
      DashboardScreen(),
      DeliveriesScreen(),
      ReportsScreen(),
      ProfileScreen(name: 'Al Safadi', subtitle: 'Restaurant · Jumeirah'),
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
          onDestinationSelected: (i) => setState(() => index = i),
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
