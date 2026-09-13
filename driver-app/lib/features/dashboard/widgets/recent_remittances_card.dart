import 'package:flutter/material.dart';

import '../../../core/theme/app_colors.dart';
import '../../../core/utils/formatters.dart';
import '../../../core/utils/parsers.dart';
import '../../../shared/widgets/card_surface.dart';
import '../../../shared/widgets/empty_state.dart';
import '../../../shared/widgets/section_heading.dart';
import '../../../shared/widgets/status_icon.dart';

/// Dashboard panel listing the latest remittances.
class RecentRemittancesCard extends StatelessWidget {
  const RecentRemittancesCard({
    super.key,
    required this.items,
    required this.onViewAll,
  });

  final List<dynamic> items;
  final VoidCallback onViewAll;

  @override
  Widget build(BuildContext context) => Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          SectionHeading(
            title: 'Recent remittances',
            trailing: TextButton(
              onPressed: onViewAll,
              child: const Text('View all'),
            ),
          ),
          const SizedBox(height: 8),
          CardSurface(
            child: items.isEmpty
                ? const EmptyState(
                    icon: Icons.receipt_long_outlined,
                    title: 'No remittances yet',
                    message: 'Generated and paid remittances will appear here.',
                  )
                : Column(
                    children: [
                      for (var index = 0; index < items.length; index++)
                        _RemittanceSummaryRow(
                          item: asMap(items[index]),
                          last: index == items.length - 1,
                        ),
                    ],
                  ),
          ),
        ],
      );
}

class _RemittanceSummaryRow extends StatelessWidget {
  const _RemittanceSummaryRow({required this.item, required this.last});

  final Map<String, dynamic> item;
  final bool last;

  @override
  Widget build(BuildContext context) => Container(
        padding: const EdgeInsets.symmetric(vertical: 11),
        decoration: BoxDecoration(
          border: last
              ? null
              : const Border(bottom: BorderSide(color: AppColors.stroke)),
        ),
        child: Row(children: [
          StatusIcon(status: (item['status'] ?? 'pending').toString()),
          const SizedBox(width: 11),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(
                  item['created_date']?.toString() ?? 'Daily remittance',
                  style: const TextStyle(
                    color: AppColors.navy,
                    fontWeight: FontWeight.w800,
                  ),
                ),
                const SizedBox(height: 3),
                Text(
                  prettyLabel((item['status'] ?? 'pending').toString()),
                  style: const TextStyle(
                    color: AppColors.muted,
                    fontSize: 10,
                  ),
                ),
              ],
            ),
          ),
          Text(
            money(asDouble(item['amount'])),
            style: const TextStyle(
              color: AppColors.navy,
              fontSize: 12,
              fontWeight: FontWeight.w900,
            ),
          ),
        ]),
      );
}
