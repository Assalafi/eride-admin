import 'package:flutter/material.dart';
import 'package:provider/provider.dart';

import '../../core/api_client.dart';
import '../../core/auth_state.dart';
import '../../core/theme/app_colors.dart';
import '../../core/utils/formatters.dart';
import '../../core/utils/parsers.dart';
import '../../shared/widgets/app_page.dart';
import '../../shared/widgets/error_card.dart';
import '../../shared/widgets/loading_cards.dart';
import '../../shared/widgets/page_header.dart';
import '../../shared/widgets/section_heading.dart';
import '../shell/shell_controller.dart';
import 'widgets/dashboard_vehicle_card.dart';
import 'widgets/hire_purchase_card.dart';
import 'widgets/insights_grid.dart';
import 'widgets/quick_actions.dart';
import 'widgets/recent_remittances_card.dart';
import 'widgets/remittance_hero.dart';

/// Home tab: remittance due, quick actions, progress, insights and activity.
class DashboardScreen extends StatefulWidget {
  const DashboardScreen({super.key});

  @override
  State<DashboardScreen> createState() => _DashboardScreenState();
}

class _DashboardScreenState extends State<DashboardScreen> {
  late Future<Map<String, dynamic>> _future;

  @override
  void initState() {
    super.initState();
    _future = _load();
  }

  Future<Map<String, dynamic>> _load() => ApiClient.instance
      .get('driver/dashboard')
      .then((response) => asMap(response['data']));

  Future<void> _refresh() async {
    final future = _load();
    setState(() => _future = future);
    await future;
  }

  void _openPayments({int segment = 0}) =>
      context.read<ShellController>().openPayments(segment: segment);

  void _openVehicle() => context.read<ShellController>().openVehicle();

  @override
  Widget build(BuildContext context) {
    final auth = context.watch<AuthState>();
    final firstName = auth.displayName.trim().split(' ').first;

    return AppPage(
      title: 'Good ${partOfDay()}, $firstName',
      subtitle: todayLabel(),
      actions: [
        HeaderAction(
          icon: Icons.refresh_rounded,
          tooltip: 'Refresh dashboard',
          onPressed: _refresh,
        ),
      ],
      onRefresh: _refresh,
      child: FutureBuilder<Map<String, dynamic>>(
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

          final data = snapshot.data ?? const <String, dynamic>{};
          final daily = asMap(data['daily_balance']);
          final summary = asMap(data['remittance_summary']);
          final totals = asMap(data['total_counts']);
          final hirePurchase = asMap(data['hire_purchase']);
          final nextRemittance = asMap(data['next_remittance']);
          final vehicle = asMap(data['vehicle']);
          final recent = asList(data['recent_remittances']);
          final minimumDue = asDouble(nextRemittance['minimum_amount'] ??
              daily['balance'] ??
              daily['required']);
          final hasDue = nextRemittance.isNotEmpty;
          final pendingCount =
              int.tryParse('${summary['pending_count'] ?? 0}') ?? 0;
          final paidCount = int.tryParse(
                '${summary['paid_count'] ?? totals['paid_remittances'] ?? 0}',
              ) ??
              0;

          return Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              RemittanceHero(
                minimumDue: minimumDue,
                hasDue: hasDue,
                pendingCount: pendingCount,
                paidCount: paidCount,
                onPay: hasDue ? () => _openPayments() : null,
              ),
              const SizedBox(height: 22),
              QuickActions(actions: [
                QuickAction(
                  icon: Icons.payments_outlined,
                  label: 'Pay',
                  onTap: () => _openPayments(),
                ),
                QuickAction(
                  icon: Icons.directions_car_outlined,
                  label: 'Vehicle',
                  onTap: _openVehicle,
                ),
                QuickAction(
                  icon: Icons.receipt_long_outlined,
                  label: 'Activity',
                  onTap: () => _openPayments(segment: 2),
                ),
                QuickAction(
                  icon: Icons.calendar_month_outlined,
                  label: 'History',
                  onTap: () => _openPayments(segment: 1),
                ),
              ]),
              if (hirePurchase['has_active_contract'] == true) ...[
                const SizedBox(height: 28),
                const SectionHeading(title: 'Hire purchase'),
                const SizedBox(height: 12),
                HirePurchaseCard(data: hirePurchase),
              ],
              const SizedBox(height: 28),
              const SectionHeading(title: 'Payment overview'),
              const SizedBox(height: 12),
              InsightsGrid(items: [
                Insight(
                  icon: Icons.task_alt_rounded,
                  label: 'Remittances paid',
                  value: '$paidCount',
                  color: AppColors.success,
                ),
                Insight(
                  icon: Icons.pending_actions_rounded,
                  label: 'Pending',
                  value: '$pendingCount',
                  color: AppColors.warning,
                ),
                Insight(
                  icon: Icons.payments_outlined,
                  label: 'Total paid',
                  value: money(asDouble(summary['total_paid'])),
                  color: AppColors.navy,
                ),
                Insight(
                  icon: Icons.calendar_month_rounded,
                  label: 'Paid this month',
                  value: money(asDouble(summary['this_month_paid'])),
                  color: AppColors.teal,
                ),
              ]),
              const SizedBox(height: 28),
              LayoutBuilder(
                builder: (context, constraints) {
                  final remittances = RecentRemittancesCard(
                    items: recent,
                    onViewAll: () => _openPayments(),
                  );
                  final assignment = DashboardVehicleCard(vehicle: vehicle);
                  if (constraints.maxWidth < 820) {
                    return Column(children: [
                      remittances,
                      const SizedBox(height: 22),
                      assignment,
                    ]);
                  }
                  return Row(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Expanded(flex: 3, child: remittances),
                      const SizedBox(width: 22),
                      Expanded(flex: 2, child: assignment),
                    ],
                  );
                },
              ),
            ],
          );
        },
      ),
    );
  }
}
