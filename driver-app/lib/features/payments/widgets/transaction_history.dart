import 'package:flutter/material.dart';

import '../../../core/theme/app_colors.dart';
import '../../../core/utils/formatters.dart';
import '../../../core/utils/parsers.dart';
import '../../../shared/widgets/card_surface.dart';
import '../../../shared/widgets/empty_state.dart';
import '../../../shared/widgets/status_icon.dart';

/// Card listing remittance transactions.
class TransactionHistory extends StatelessWidget {
  const TransactionHistory({super.key, required this.items});

  final List<dynamic> items;

  @override
  Widget build(BuildContext context) {
    if (items.isEmpty) {
      return const CardSurface(
        child: EmptyState(
          icon: Icons.receipt_long_outlined,
          title: 'No transaction activity',
          message: 'Your payment activity will appear here.',
        ),
      );
    }
    return CardSurface(
      child: Column(children: [
        for (var index = 0; index < items.length; index++)
          _ActivityRow(
            item: asMap(items[index]),
            last: index == items.length - 1,
          ),
      ]),
    );
  }
}

class _ActivityRow extends StatelessWidget {
  const _ActivityRow({required this.item, required this.last});

  final Map<String, dynamic> item;
  final bool last;

  @override
  Widget build(BuildContext context) => Container(
        padding: const EdgeInsets.symmetric(vertical: 10),
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
                  prettyLabel(
                    (item['type'] ?? item['reference'] ?? 'Transaction')
                        .toString(),
                  ),
                  maxLines: 1,
                  overflow: TextOverflow.ellipsis,
                  style: const TextStyle(
                    color: AppColors.navy,
                    fontWeight: FontWeight.w800,
                  ),
                ),
                const SizedBox(height: 3),
                Text(
                  item['created_at']?.toString() ?? '',
                  style: const TextStyle(
                    color: AppColors.muted,
                    fontSize: 11,
                  ),
                ),
              ],
            ),
          ),
          Text(
            money(asDouble(item['amount'])),
            style: const TextStyle(
              color: AppColors.navy,
              fontWeight: FontWeight.w800,
            ),
          ),
        ]),
      );
}
