import 'package:flutter/material.dart';

import '../../core/theme/app_colors.dart';

/// Circular status indicator: paid, pending or failed.
class StatusIcon extends StatelessWidget {
  const StatusIcon({super.key, required this.status});

  final String status;

  @override
  Widget build(BuildContext context) {
    final normalized = status.toLowerCase();
    final good = ['paid', 'success', 'successful', 'approved', 'completed']
        .contains(normalized);
    final rejected = [
      'rejected',
      'failed',
      'fail',
      'close',
      'closed',
      'cancelled',
    ].contains(normalized);
    final color = good
        ? AppColors.success
        : rejected
            ? AppColors.danger
            : AppColors.warning;

    return Container(
      width: 38,
      height: 38,
      decoration: BoxDecoration(
        color: color.withValues(alpha: .12),
        shape: BoxShape.circle,
      ),
      child: Icon(
        good
            ? Icons.check_rounded
            : rejected
                ? Icons.close_rounded
                : Icons.schedule_rounded,
        color: color,
        size: 18,
      ),
    );
  }
}
