import 'package:flutter/material.dart';

import '../../../core/theme/app_colors.dart';
import '../../../core/utils/formatters.dart';
import '../../../shared/widgets/app_glow.dart';

/// Hero card showing today's remittance and payment status.
class RemittanceHero extends StatelessWidget {
  const RemittanceHero({
    super.key,
    required this.minimumDue,
    required this.hasDue,
    required this.pendingCount,
    required this.paidCount,
    required this.onPay,
  });

  final double minimumDue;
  final bool hasDue;
  final int pendingCount;
  final int paidCount;
  final VoidCallback? onPay;

  @override
  Widget build(BuildContext context) => Container(
        padding: const EdgeInsets.all(24),
        decoration: BoxDecoration(
          gradient: AppColors.brandGradient,
          borderRadius: BorderRadius.circular(28),
          boxShadow: [
            BoxShadow(
              color: AppColors.navy.withValues(alpha: .24),
              blurRadius: 32,
              offset: const Offset(0, 18),
            ),
          ],
        ),
        child: Stack(children: [
          Positioned(
            right: -40,
            top: -60,
            child: AppGlow(
              size: 190,
              color: AppColors.teal.withValues(alpha: .20),
            ),
          ),
          Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Row(children: [
                Text(
                  'REMITTANCE DUE',
                  style: TextStyle(
                    color: Colors.white.withValues(alpha: .68),
                    fontSize: 10,
                    fontWeight: FontWeight.w900,
                    letterSpacing: 1.6,
                  ),
                ),
                const Spacer(),
                Container(
                  padding:
                      const EdgeInsets.symmetric(horizontal: 10, vertical: 6),
                  decoration: BoxDecoration(
                    color: Colors.white.withValues(alpha: .13),
                    borderRadius: BorderRadius.circular(30),
                  ),
                  child: const Row(
                    mainAxisSize: MainAxisSize.min,
                    children: [
                      Icon(
                        Icons.verified_rounded,
                        color: AppColors.teal,
                        size: 14,
                      ),
                      SizedBox(width: 5),
                      Text(
                        'Active',
                        style: TextStyle(
                          color: Colors.white,
                          fontSize: 11,
                          fontWeight: FontWeight.w700,
                        ),
                      ),
                    ],
                  ),
                ),
              ]),
              const SizedBox(height: 16),
              Text(
                hasDue ? money(minimumDue) : 'All caught up',
                style: const TextStyle(
                  color: Colors.white,
                  fontSize: 34,
                  fontWeight: FontWeight.w900,
                  letterSpacing: -1,
                  height: 1.1,
                ),
              ),
              const SizedBox(height: 10),
              Row(children: [
                Icon(
                  hasDue
                      ? Icons.event_note_rounded
                      : Icons.check_circle_rounded,
                  color: Colors.white.withValues(alpha: .72),
                  size: 15,
                ),
                const SizedBox(width: 6),
                Text(
                  hasDue
                      ? "Today's minimum remittance"
                      : 'No payment due today',
                  style: TextStyle(
                    color: Colors.white.withValues(alpha: .62),
                    fontSize: 11,
                  ),
                ),
              ]),
              const SizedBox(height: 22),
              Container(
                height: 1,
                color: Colors.white.withValues(alpha: .12),
              ),
              const SizedBox(height: 16),
              Row(children: [
                _HeroCount(
                  icon: Icons.check_rounded,
                  label: '$paidCount paid',
                  tone: AppColors.teal,
                ),
                const SizedBox(width: 22),
                _HeroCount(
                  icon: Icons.schedule_rounded,
                  label: '$pendingCount pending',
                  tone: AppColors.warning,
                ),
              ]),
              const SizedBox(height: 20),
              SizedBox(
                width: double.infinity,
                child: FilledButton.icon(
                  onPressed: onPay,
                  style: FilledButton.styleFrom(
                    backgroundColor: AppColors.teal,
                    foregroundColor: AppColors.navyDark,
                    disabledBackgroundColor:
                        Colors.white.withValues(alpha: .12),
                    disabledForegroundColor: Colors.white.withValues(alpha: .5),
                    minimumSize: const Size(0, 50),
                    shape: RoundedRectangleBorder(
                      borderRadius: BorderRadius.circular(15),
                    ),
                  ),
                  icon: const Icon(Icons.arrow_forward_rounded, size: 18),
                  label: const Text('Pay remittance'),
                ),
              ),
            ],
          ),
        ]),
      );
}

class _HeroCount extends StatelessWidget {
  const _HeroCount({
    required this.icon,
    required this.label,
    required this.tone,
  });

  final IconData icon;
  final String label;
  final Color tone;

  @override
  Widget build(BuildContext context) => Row(
        mainAxisSize: MainAxisSize.min,
        children: [
          Container(
            width: 26,
            height: 26,
            decoration: BoxDecoration(
              color: tone.withValues(alpha: .20),
              borderRadius: BorderRadius.circular(9),
            ),
            child: Icon(icon, color: tone, size: 15),
          ),
          const SizedBox(width: 8),
          Text(
            label,
            style: const TextStyle(
              color: Colors.white,
              fontSize: 12,
              fontWeight: FontWeight.w700,
            ),
          ),
        ],
      );
}
