import 'package:flutter/material.dart';

import '../../../shared/widgets/card_surface.dart';
import '../../../shared/widgets/empty_state.dart';
import '../../../shared/widgets/section_heading.dart';
import 'vehicle_summary.dart';

/// Dashboard panel showing the currently assigned vehicle.
class DashboardVehicleCard extends StatelessWidget {
  const DashboardVehicleCard({super.key, required this.vehicle});

  final Map<String, dynamic> vehicle;

  @override
  Widget build(BuildContext context) => Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          const SectionHeading(title: 'Current vehicle'),
          const SizedBox(height: 12),
          CardSurface(
            child: vehicle.isEmpty
                ? const EmptyState(
                    icon: Icons.directions_car_outlined,
                    title: 'No vehicle assigned',
                    message: 'Your active assignment will appear here.',
                  )
                : VehicleSummary(vehicle: vehicle),
          ),
        ],
      );
}
