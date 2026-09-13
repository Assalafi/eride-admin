import 'package:flutter/material.dart';

import '../../../core/theme/app_colors.dart';

/// Sliding segmented control for the payments lists.
class PaymentSegments extends StatelessWidget {
  const PaymentSegments({
    super.key,
    required this.selected,
    required this.pendingCount,
    required this.onChanged,
  });

  final int selected;
  final int pendingCount;
  final ValueChanged<int> onChanged;

  @override
  Widget build(BuildContext context) => Container(
        padding: const EdgeInsets.all(5),
        decoration: BoxDecoration(
          color: const Color(0xFFEDEFF5),
          borderRadius: BorderRadius.circular(16),
        ),
        child: Row(children: [
          _SegmentButton(
            label: 'Due ($pendingCount)',
            selected: selected == 0,
            onTap: () => onChanged(0),
          ),
          _SegmentButton(
            label: 'Paid',
            selected: selected == 1,
            onTap: () => onChanged(1),
          ),
          _SegmentButton(
            label: 'All activity',
            selected: selected == 2,
            onTap: () => onChanged(2),
          ),
        ]),
      );
}

class _SegmentButton extends StatelessWidget {
  const _SegmentButton({
    required this.label,
    required this.selected,
    required this.onTap,
  });

  final String label;
  final bool selected;
  final VoidCallback onTap;

  @override
  Widget build(BuildContext context) => Expanded(
        child: Material(
          color: selected ? Colors.white : Colors.transparent,
          borderRadius: BorderRadius.circular(12),
          child: InkWell(
            onTap: onTap,
            borderRadius: BorderRadius.circular(12),
            child: AnimatedContainer(
              duration: const Duration(milliseconds: 200),
              padding: const EdgeInsets.symmetric(vertical: 12, horizontal: 4),
              decoration: BoxDecoration(
                borderRadius: BorderRadius.circular(12),
                boxShadow: selected
                    ? [
                        BoxShadow(
                          color: AppColors.navy.withValues(alpha: .08),
                          blurRadius: 12,
                          offset: const Offset(0, 5),
                        ),
                      ]
                    : null,
              ),
              child: Text(
                label,
                textAlign: TextAlign.center,
                style: TextStyle(
                  color: selected ? AppColors.navy : AppColors.muted,
                  fontSize: 10,
                  fontWeight: selected ? FontWeight.w900 : FontWeight.w600,
                ),
              ),
            ),
          ),
        ),
      );
}
