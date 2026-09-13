import 'package:flutter/material.dart';

import '../../../core/theme/app_colors.dart';
import '../../../shared/widgets/card_surface.dart';

/// A single insight metric.
class Insight {
  const Insight({
    required this.icon,
    required this.label,
    required this.value,
    required this.color,
  });

  final IconData icon;
  final String label;
  final String value;
  final Color color;
}

/// A 2x2 metrics grid rendered as one unified card with dividers.
class InsightsGrid extends StatelessWidget {
  const InsightsGrid({super.key, required this.items});

  final List<Insight> items;

  @override
  Widget build(BuildContext context) => CardSurface(
        padding: EdgeInsets.zero,
        child: Column(children: [
          IntrinsicHeight(
            child: Row(children: [
              Expanded(child: _InsightCell(item: items[0])),
              const VerticalDivider(
                width: 1,
                thickness: 1,
                color: AppColors.stroke,
              ),
              Expanded(child: _InsightCell(item: items[1])),
            ]),
          ),
          const Divider(height: 1, thickness: 1, color: AppColors.stroke),
          IntrinsicHeight(
            child: Row(children: [
              Expanded(child: _InsightCell(item: items[2])),
              const VerticalDivider(
                width: 1,
                thickness: 1,
                color: AppColors.stroke,
              ),
              Expanded(child: _InsightCell(item: items[3])),
            ]),
          ),
        ]),
      );
}

class _InsightCell extends StatelessWidget {
  const _InsightCell({required this.item});

  final Insight item;

  @override
  Widget build(BuildContext context) => Padding(
        padding: const EdgeInsets.all(18),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Row(children: [
              Container(
                width: 34,
                height: 34,
                decoration: BoxDecoration(
                  color: item.color.withValues(alpha: .11),
                  borderRadius: BorderRadius.circular(11),
                ),
                child: Icon(item.icon, color: item.color, size: 17),
              ),
              const SizedBox(width: 9),
              Expanded(
                child: Text(
                  item.label,
                  maxLines: 2,
                  style: const TextStyle(
                    color: AppColors.muted,
                    fontSize: 11,
                    height: 1.25,
                  ),
                ),
              ),
            ]),
            const SizedBox(height: 14),
            Text(
              item.value,
              maxLines: 1,
              overflow: TextOverflow.ellipsis,
              style: const TextStyle(
                color: AppColors.navy,
                fontSize: 18,
                fontWeight: FontWeight.w900,
              ),
            ),
          ],
        ),
      );
}
