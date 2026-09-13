import 'package:flutter/material.dart';

import '../../../core/theme/app_colors.dart';
import '../../../core/utils/formatters.dart';
import '../../../shared/widgets/app_glow.dart';

/// Gradient header summarising outstanding remittance and status counts.
class PaymentsHeader extends StatelessWidget {
  const PaymentsHeader({
    super.key,
    required this.outstanding,
    required this.pendingCount,
    required this.paidCount,
  });

  final double outstanding;
  final int pendingCount;
  final int paidCount;

  @override
  Widget build(BuildContext context) => Container(
        padding: const EdgeInsets.all(24),
        decoration: BoxDecoration(
          gradient: AppColors.brandGradient,
          borderRadius: BorderRadius.circular(28),
          boxShadow: [
            BoxShadow(
              color: AppColors.navy.withValues(alpha: .22),
              blurRadius: 28,
              offset: const Offset(0, 14),
            ),
          ],
        ),
        child: Stack(children: [
          Positioned(
            right: -30,
            top: -50,
            child: AppGlow(
              size: 170,
              color: AppColors.teal.withValues(alpha: .20),
            ),
          ),
          Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Row(children: [
                const Icon(
                  Icons.receipt_long_rounded,
                  color: AppColors.teal,
                  size: 22,
                ),
                const SizedBox(width: 10),
                Text(
                  'Outstanding remittance',
                  style: TextStyle(
                    color: Colors.white.withValues(alpha: .72),
                    fontSize: 12,
                  ),
                ),
              ]),
              const SizedBox(height: 16),
              Text(
                money(outstanding),
                style: const TextStyle(
                  color: Colors.white,
                  fontSize: 32,
                  fontWeight: FontWeight.w900,
                  letterSpacing: -.9,
                  height: 1.1,
                ),
              ),
              const SizedBox(height: 8),
              Text(
                pendingCount == 0
                    ? 'You have no pending remittance'
                    : 'Across $pendingCount pending remittance${pendingCount == 1 ? '' : 's'}',
                style: TextStyle(
                  color: Colors.white.withValues(alpha: .62),
                  fontSize: 11,
                ),
              ),
              const SizedBox(height: 18),
              Row(children: [
                _HeaderStat(
                  label: 'Pending',
                  value: '$pendingCount',
                  tone: AppColors.warning,
                ),
                const SizedBox(width: 10),
                _HeaderStat(
                  label: 'Paid',
                  value: '$paidCount',
                  tone: AppColors.success,
                ),
              ]),
            ],
          ),
        ]),
      );
}

class _HeaderStat extends StatelessWidget {
  const _HeaderStat({
    required this.label,
    required this.value,
    required this.tone,
  });

  final String label;
  final String value;
  final Color tone;

  @override
  Widget build(BuildContext context) => Container(
        padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 8),
        decoration: BoxDecoration(
          color: Colors.white.withValues(alpha: .1),
          borderRadius: BorderRadius.circular(30),
          border: Border.all(color: Colors.white.withValues(alpha: .12)),
        ),
        child: Row(mainAxisSize: MainAxisSize.min, children: [
          Container(
            width: 7,
            height: 7,
            decoration: BoxDecoration(color: tone, shape: BoxShape.circle),
          ),
          const SizedBox(width: 7),
          Text(
            '$label  $value',
            style: const TextStyle(
              color: Colors.white,
              fontSize: 10,
              fontWeight: FontWeight.w700,
            ),
          ),
        ]),
      );
}
