import 'package:flutter/material.dart';

import '../../../core/theme/app_colors.dart';
import '../../../shared/widgets/app_glow.dart';

/// Gradient vehicle hero with plate badge.
class VehicleHero extends StatelessWidget {
  const VehicleHero({super.key, required this.vehicle});

  final Map<String, dynamic> vehicle;

  @override
  Widget build(BuildContext context) {
    final name = '${vehicle['make'] ?? ''} ${vehicle['model'] ?? ''}'.trim();
    return Container(
      padding: const EdgeInsets.all(24),
      decoration: BoxDecoration(
        gradient: AppColors.brandGradient,
        borderRadius: BorderRadius.circular(28),
        boxShadow: [
          BoxShadow(
            color: AppColors.navy.withValues(alpha: .20),
            blurRadius: 28,
            offset: const Offset(0, 14),
          ),
        ],
      ),
      child: Stack(children: [
        Positioned(
          right: -30,
          top: -40,
          child: AppGlow(
            size: 160,
            color: AppColors.teal.withValues(alpha: .18),
          ),
        ),
        Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
          Row(children: [
            Container(
              width: 54,
              height: 54,
              decoration: BoxDecoration(
                color: Colors.white.withValues(alpha: .13),
                borderRadius: BorderRadius.circular(17),
              ),
              child: const Icon(
                Icons.directions_car_filled_rounded,
                color: AppColors.teal,
                size: 29,
              ),
            ),
            const Spacer(),
            _PlateBadge(
              value: vehicle['plate_number']?.toString() ??
                  vehicle['plate']?.toString() ??
                  'N/A',
            ),
          ]),
          const SizedBox(height: 26),
          Text(
            name.isEmpty ? 'Assigned vehicle' : name,
            style: const TextStyle(
              color: Colors.white,
              fontSize: 25,
              fontWeight: FontWeight.w900,
            ),
          ),
          const SizedBox(height: 8),
          Text(
            vehicle['assigned_at'] == null
                ? 'Your current eRide assignment'
                : 'Assigned ${vehicle['assigned_at']}',
            style: const TextStyle(color: Colors.white70, fontSize: 13),
          ),
        ]),
      ]),
    );
  }
}

class _PlateBadge extends StatelessWidget {
  const _PlateBadge({required this.value});

  final String value;

  @override
  Widget build(BuildContext context) => Container(
        padding: const EdgeInsets.symmetric(horizontal: 13, vertical: 9),
        decoration: BoxDecoration(
          color: Colors.white,
          borderRadius: BorderRadius.circular(10),
        ),
        child: Text(
          value,
          style: const TextStyle(
            color: AppColors.navy,
            fontWeight: FontWeight.w900,
            letterSpacing: .7,
          ),
        ),
      );
}
