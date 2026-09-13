import 'package:flutter/material.dart';
import 'package:provider/provider.dart';

import '../../core/auth_state.dart';
import '../../core/theme/app_colors.dart';
import '../../core/utils/formatters.dart';
import '../../shared/widgets/brand.dart';
import '../dashboard/dashboard_screen.dart';
import '../payments/payments_screen.dart';
import '../profile/profile_screen.dart';
import '../vehicle/vehicle_screen.dart';
import 'shell_controller.dart';
import 'widgets/bottom_bar.dart';
import 'widgets/sidebar.dart';

/// Authenticated navigation frame: sidebar on desktop, top bar + bottom bar on
/// mobile. Tab state lives in [ShellController] so any screen can switch tabs.
class AppShell extends StatelessWidget {
  const AppShell({super.key});

  static const List<String> _labels = [
    'Home',
    'Payments',
    'Vehicle',
    'Profile'
  ];
  static const List<IconData> _icons = [
    Icons.grid_view_rounded,
    Icons.receipt_long_rounded,
    Icons.directions_car_filled_rounded,
    Icons.person_rounded,
  ];

  static const List<Widget> _pages = [
    DashboardScreen(),
    PaymentsScreen(),
    VehicleScreen(),
    ProfileScreen(),
  ];

  @override
  Widget build(BuildContext context) {
    final controller = context.watch<ShellController>();
    final wide = MediaQuery.sizeOf(context).width >= 980;
    final body = IndexedStack(index: controller.index, children: _pages);

    if (wide) {
      return Scaffold(
        body: Row(children: [
          SizedBox(
            width: 288,
            child: Sidebar(
              selected: controller.index,
              labels: _labels,
              icons: _icons,
              onSelect: controller.selectTab,
            ),
          ),
          Expanded(child: body),
        ]),
      );
    }

    return Scaffold(
      body: Column(children: [
        _MobileTopBar(
          onProfile: () => controller.selectTab(ShellController.profileIndex),
        ),
        Expanded(child: body),
      ]),
      bottomNavigationBar: BottomBar(
        selected: controller.index,
        labels: _labels,
        icons: _icons,
        onSelect: controller.selectTab,
      ),
    );
  }
}

/// Slim top bar shown on mobile: brand on the left, profile avatar on the
/// right (tapping it opens the Profile tab).
class _MobileTopBar extends StatelessWidget {
  const _MobileTopBar({required this.onProfile});

  final VoidCallback onProfile;

  @override
  Widget build(BuildContext context) {
    final auth = context.watch<AuthState>();

    return Container(
      decoration: const BoxDecoration(
        color: Colors.white,
        border: Border(bottom: BorderSide(color: AppColors.stroke)),
      ),
      child: SafeArea(
        bottom: false,
        child: Padding(
          padding: const EdgeInsets.fromLTRB(20, 12, 16, 12),
          child: Row(children: [
            const BrandMark(size: 34),
            const SizedBox(width: 10),
            const Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              mainAxisSize: MainAxisSize.min,
              children: [
                Text(
                  'eRide',
                  style: TextStyle(
                    color: AppColors.navy,
                    fontSize: 16,
                    fontWeight: FontWeight.w900,
                    height: 1,
                    letterSpacing: -.4,
                  ),
                ),
                SizedBox(height: 3),
                Text(
                  'DRIVER',
                  style: TextStyle(
                    color: AppColors.teal,
                    fontSize: 7.5,
                    fontWeight: FontWeight.w900,
                    letterSpacing: 2.4,
                  ),
                ),
              ],
            ),
            const Spacer(),
            InkWell(
              onTap: onProfile,
              borderRadius: BorderRadius.circular(40),
              child: Container(
                width: 42,
                height: 42,
                decoration: BoxDecoration(
                  gradient: AppColors.brandGradient,
                  shape: BoxShape.circle,
                  boxShadow: [
                    BoxShadow(
                      color: AppColors.navy.withValues(alpha: .22),
                      blurRadius: 14,
                      offset: const Offset(0, 6),
                    ),
                  ],
                ),
                child: Center(
                  child: Text(
                    initialsOf(auth.displayName),
                    style: const TextStyle(
                      color: Colors.white,
                      fontWeight: FontWeight.w900,
                      fontSize: 13,
                    ),
                  ),
                ),
              ),
            ),
          ]),
        ),
      ),
    );
  }
}
