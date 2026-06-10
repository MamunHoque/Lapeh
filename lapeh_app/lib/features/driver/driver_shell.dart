import 'package:flutter/material.dart';
import '../../core/theme.dart';
import '../../core/i18n.dart';
import '../shared/profile_screen.dart';
import 'driver_home_screen.dart';
import 'earnings_screen.dart';
import 'trips_screen.dart';

class DriverShell extends StatefulWidget {
  const DriverShell({super.key});
  @override
  State<DriverShell> createState() => _DriverShellState();
}

class _DriverShellState extends State<DriverShell> {
  int index = 0;

  @override
  Widget build(BuildContext context) {
    final pages = const [
      DriverHomeScreen(),
      TripsScreen(),
      EarningsScreen(),
      ProfileScreen(name: 'Bilal Hassan', subtitle: 'Driver · D-7841 · ★ 4.9'),
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
            NavigationDestination(icon: const Icon(Icons.list_alt_outlined), selectedIcon: const Icon(Icons.list_alt, color: AppColors.pink), label: tr('trips')),
            NavigationDestination(icon: const Icon(Icons.account_balance_wallet_outlined), selectedIcon: const Icon(Icons.account_balance_wallet, color: AppColors.pink), label: tr('earnings')),
            NavigationDestination(icon: const Icon(Icons.person_outline), selectedIcon: const Icon(Icons.person, color: AppColors.pink), label: tr('profile')),
          ],
        ),
      ),
    );
  }
}
