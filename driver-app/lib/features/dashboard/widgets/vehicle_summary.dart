import 'package:flutter/material.dart';

import '../../../core/theme/app_colors.dart';

/// One-line vehicle summary: icon, name, plate and assignment date.
class VehicleSummary extends StatelessWidget {
  const VehicleSummary({super.key, required this.vehicle});

  final Map<String, dynamic> vehicle;

  @override
  Widget build(BuildContext context) {
    final name = '${vehicle['make'] ?? ''} ${vehicle['model'] ?? ''}'.trim();
    return Row(children: [
      Container(
        width: 54,
        height: 54,
        decoration: BoxDecoration(
          color: AppColors.mint,
          borderRadius: BorderRadius.circular(16),
        ),
        child: const Icon(
          Icons.directions_car_filled_rounded,
          color: AppColors.navy,
          size: 27,
        ),
      ),
      const SizedBox(width: 14),
      Expanded(
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Text(
              name.isEmpty ? 'Assigned vehicle' : name,
              style: const TextStyle(
                color: AppColors.navy,
                fontSize: 16,
                fontWeight: FontWeight.w900,
              ),
            ),
            const SizedBox(height: 5),
            Text(
              vehicle['plate_number']?.toString() ??
                  vehicle['plate']?.toString() ??
                  'Plate number unavailable',
              style: const TextStyle(color: AppColors.muted),
            ),
            if (vehicle['assigned_at'] != null)
              Text(
                'Assigned ${vehicle['assigned_at']}',
                style: const TextStyle(
                  color: AppColors.muted,
                  fontSize: 11,
                ),
              ),
          ],
        ),
      ),
    ]);
  }
}
