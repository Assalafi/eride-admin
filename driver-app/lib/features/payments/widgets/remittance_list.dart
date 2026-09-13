import 'package:flutter/material.dart';

import '../../../core/theme/app_colors.dart';
import '../../../core/utils/formatters.dart';
import '../../../core/utils/parsers.dart';
import '../../../shared/widgets/card_surface.dart';
import '../../../shared/widgets/empty_state.dart';
import '../../../shared/widgets/status_icon.dart';

/// List of remittances, with an optional pay action per row.
class RemittanceList extends StatelessWidget {
  const RemittanceList({
    super.key,
    required this.items,
    required this.emptyTitle,
    required this.emptyMessage,
    this.onPay,
  });

  final List<dynamic> items;
  final String emptyTitle;
  final String emptyMessage;
  final ValueChanged<Map<String, dynamic>>? onPay;

  @override
  Widget build(BuildContext context) {
    if (items.isEmpty) {
      return CardSurface(
        child: EmptyState(
          icon: Icons.task_alt_rounded,
          title: emptyTitle,
          message: emptyMessage,
        ),
      );
    }
    return Column(children: [
      for (final raw in items) ...[
        _RemittancePaymentCard(item: asMap(raw), onPay: onPay),
        const SizedBox(height: 11),
      ],
    ]);
  }
}

class _RemittancePaymentCard extends StatelessWidget {
  const _RemittancePaymentCard({required this.item, this.onPay});

  final Map<String, dynamic> item;
  final ValueChanged<Map<String, dynamic>>? onPay;

  @override
  Widget build(BuildContext context) {
    final status = (item['status'] ?? 'pending').toString();
    final canPay =
        onPay != null && ['pending', 'due'].contains(status.toLowerCase());

    return CardSurface(
      padding: const EdgeInsets.all(16),
      child: Row(children: [
        StatusIcon(status: status),
        const SizedBox(width: 12),
        Expanded(
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Text(
                item['created_date']?.toString() ?? 'Daily remittance',
                style: const TextStyle(
                  color: AppColors.navy,
                  fontWeight: FontWeight.w900,
                ),
              ),
              const SizedBox(height: 4),
              Text(
                canPay ? 'Minimum generated amount' : prettyLabel(status),
                style: const TextStyle(color: AppColors.muted, fontSize: 10),
              ),
            ],
          ),
        ),
        Column(
          crossAxisAlignment: CrossAxisAlignment.end,
          children: [
            Text(
              money(asDouble(item['amount'])),
              style: const TextStyle(
                color: AppColors.navy,
                fontWeight: FontWeight.w900,
              ),
            ),
            if (canPay)
              TextButton(
                onPressed: () => onPay!(item),
                child: const Text('Choose amount'),
              ),
          ],
        ),
      ]),
    );
  }
}
