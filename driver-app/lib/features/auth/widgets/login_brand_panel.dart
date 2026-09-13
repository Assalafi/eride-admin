import 'package:flutter/material.dart';

import '../../../core/theme/app_colors.dart';
import '../../../shared/widgets/app_glow.dart';
import '../../../shared/widgets/brand.dart';
import 'benefit_item.dart';

/// Desktop marketing panel shown beside the login card.
class LoginBrandPanel extends StatelessWidget {
  const LoginBrandPanel({super.key});

  @override
  Widget build(BuildContext context) => Container(
        decoration: const BoxDecoration(gradient: AppColors.brandGradient),
        child: Stack(children: [
          Positioned(
            top: -90,
            right: -70,
            child: AppGlow(
              size: 280,
              color: AppColors.teal.withValues(alpha: .20),
            ),
          ),
          Positioned(
            bottom: -110,
            left: -60,
            child: AppGlow(
              size: 330,
              color: Colors.white.withValues(alpha: .06),
            ),
          ),
          Padding(
            padding: const EdgeInsets.symmetric(horizontal: 64, vertical: 60),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              mainAxisAlignment: MainAxisAlignment.center,
              children: [
                const Wordmark(width: 168, light: true),
                const SizedBox(height: 70),
                const Text(
                  'Drive. Deliver.\nGet paid.',
                  style: TextStyle(
                    color: Colors.white,
                    fontSize: 44,
                    height: 1.12,
                    fontWeight: FontWeight.w900,
                    letterSpacing: -1.4,
                  ),
                ),
                const SizedBox(height: 22),
                Text(
                  'Everything you need for a smoother driving day, in one focused workspace.',
                  style: TextStyle(
                    color: Colors.white.withValues(alpha: .72),
                    fontSize: 16,
                    height: 1.6,
                  ),
                ),
                const SizedBox(height: 44),
                const BenefitItem(
                  icon: Icons.event_available_rounded,
                  title: 'Never miss a due date',
                  subtitle: 'Track daily remittances and pay in seconds.',
                ),
                const SizedBox(height: 16),
                const BenefitItem(
                  icon: Icons.directions_car_outlined,
                  title: 'Know your assignment',
                  subtitle: 'Vehicle details always within reach.',
                ),
                const SizedBox(height: 16),
                const BenefitItem(
                  icon: Icons.shield_moon_outlined,
                  title: 'Protected by design',
                  subtitle: 'Secure sign-in keeps your account safe.',
                ),
              ],
            ),
          ),
        ]),
      );
}
