import 'package:flutter/material.dart';

import '../../core/api_client.dart';
import '../../core/theme/app_colors.dart';
import '../../core/utils/parsers.dart';
import '../../shared/widgets/app_page.dart';
import '../../shared/widgets/card_surface.dart';
import '../../shared/widgets/empty_state.dart';
import '../../shared/widgets/error_card.dart';
import '../../shared/widgets/loading_cards.dart';
import '../../shared/widgets/page_header.dart';
import 'widgets/vehicle_detail.dart';
import 'widgets/vehicle_hero.dart';

/// Vehicle tab: current assignment, details and assignment history.
class VehicleScreen extends StatefulWidget {
  const VehicleScreen({super.key});

  @override
  State<VehicleScreen> createState() => _VehicleScreenState();
}

class _VehicleScreenState extends State<VehicleScreen> {
  late Future<Map<String, dynamic>> _future;

  @override
  void initState() {
    super.initState();
    _future = _load();
  }

  Future<Map<String, dynamic>> _load() => ApiClient.instance
      .get('driver/vehicle/current')
      .then((value) => asMap(value['data']));

  Future<void> _refresh() async {
    final future = _load();
    setState(() => _future = future);
    await future;
  }

  @override
  Widget build(BuildContext context) {
    final content = FutureBuilder<Map<String, dynamic>>(
      future: _future,
      builder: (context, snapshot) {
        if (snapshot.connectionState == ConnectionState.waiting) {
          return const LoadingCards();
        }
        if (snapshot.hasError) {
          return ErrorCard(
            message: snapshot.error.toString(),
            onRetry: _refresh,
          );
        }

        final assignment = snapshot.data ?? {};
        final vehicle = asMap(assignment['vehicle']);
        if (assignment.isEmpty || vehicle.isEmpty) {
          return const EmptyState(
            icon: Icons.directions_car_outlined,
            title: 'No current assignment',
            message:
                'Your assigned vehicle will appear here when it is available.',
          );
        }

        final displayVehicle = <String, dynamic>{
          ...vehicle,
          'assigned_at': assignment['assigned_at'],
          'assigned_by': assignment['assigned_by'],
        };

        return Column(children: [
          VehicleHero(vehicle: displayVehicle),
          const SizedBox(height: 18),
          LayoutBuilder(
            builder: (context, constraints) {
              final width = (constraints.maxWidth - 12) / 2;
              return Wrap(
                spacing: 12,
                runSpacing: 12,
                children: [
                  SizedBox(
                    width: width,
                    child: VehicleDetail(
                      icon: Icons.calendar_today_outlined,
                      label: 'Model year',
                      value: vehicle['year']?.toString() ?? 'Not available',
                    ),
                  ),
                  SizedBox(
                    width: width,
                    child: VehicleDetail(
                      icon: Icons.palette_outlined,
                      label: 'Colour',
                      value: vehicle['color']?.toString() ?? 'Not available',
                    ),
                  ),
                  SizedBox(
                    width: width,
                    child: VehicleDetail(
                      icon: Icons.pin_outlined,
                      label: 'VIN',
                      value: vehicle['vin']?.toString() ?? 'Not available',
                    ),
                  ),
                  SizedBox(
                    width: width,
                    child: VehicleDetail(
                      icon: Icons.person_outline_rounded,
                      label: 'Assigned by',
                      value: assignment['assigned_by']?.toString() ??
                          'eRide admin',
                    ),
                  ),
                ],
              );
            },
          ),
          const SizedBox(height: 18),
          CardSurface(
            padding: const EdgeInsets.all(16),
            child: ListTile(
              contentPadding: EdgeInsets.zero,
              leading: Container(
                width: 44,
                height: 44,
                decoration: BoxDecoration(
                  color: AppColors.mint,
                  borderRadius: BorderRadius.circular(14),
                ),
                child: const Icon(
                  Icons.history_rounded,
                  color: AppColors.navy,
                ),
              ),
              title: const Text(
                'Assignment history',
                style: TextStyle(
                  color: AppColors.navy,
                  fontWeight: FontWeight.w800,
                ),
              ),
              subtitle: const Text(
                'View previous vehicle assignments',
                style: TextStyle(color: AppColors.muted, fontSize: 11),
              ),
              trailing: const Icon(
                Icons.chevron_right_rounded,
                color: AppColors.muted,
              ),
              onTap: () => _showHistory(context),
            ),
          ),
        ]);
      },
    );

    return AppPage(
      title: 'Vehicle',
      subtitle: 'Your current assignment at a glance.',
      actions: [
        HeaderAction(
          icon: Icons.refresh_rounded,
          tooltip: 'Refresh vehicle',
          onPressed: _refresh,
        ),
      ],
      onRefresh: _refresh,
      child: content,
    );
  }

  Future<void> _showHistory(BuildContext context) async {
    try {
      final response = await ApiClient.instance.get('driver/vehicle/history');
      final list = asList(response['data']);
      if (!context.mounted) return;
      showModalBottomSheet(
        context: context,
        showDragHandle: true,
        backgroundColor: Colors.white,
        builder: (_) => SafeArea(
          child: Padding(
            padding: const EdgeInsets.fromLTRB(22, 4, 22, 22),
            child: list.isEmpty
                ? const EmptyState(
                    icon: Icons.history,
                    title: 'No history',
                    message: 'No previous assignments were found.',
                  )
                : ListView.separated(
                    itemCount: list.length,
                    separatorBuilder: (_, __) => const Divider(height: 22),
                    itemBuilder: (_, index) {
                      final assignment = asMap(list[index]);
                      final item = asMap(assignment['vehicle']);
                      return ListTile(
                        contentPadding: EdgeInsets.zero,
                        leading: Container(
                          width: 42,
                          height: 42,
                          decoration: BoxDecoration(
                            color: AppColors.mint,
                            borderRadius: BorderRadius.circular(13),
                          ),
                          child: const Icon(
                            Icons.directions_car_outlined,
                            color: AppColors.navy,
                          ),
                        ),
                        title: Text(
                          '${item['make'] ?? ''} ${item['model'] ?? ''}',
                          style: const TextStyle(
                            fontWeight: FontWeight.w800,
                          ),
                        ),
                        subtitle: Text(
                          assignment['assigned_at']?.toString() ?? '',
                          style: const TextStyle(color: AppColors.muted),
                        ),
                        trailing: Text(
                          item['plate_number']?.toString() ?? '',
                          style: const TextStyle(
                            color: AppColors.navy,
                            fontWeight: FontWeight.w800,
                          ),
                        ),
                      );
                    },
                  ),
          ),
        ),
      );
    } on ApiException catch (exception) {
      if (context.mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(content: Text(exception.message)),
        );
      }
    }
  }
}
