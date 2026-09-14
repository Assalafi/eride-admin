import 'package:flutter/material.dart';

import '../../../core/theme/app_colors.dart';
import '../../../core/utils/formatters.dart';
import '../../../core/utils/parsers.dart';
import '../../../shared/widgets/card_surface.dart';
import '../../../shared/widgets/empty_state.dart';
import '../../../shared/widgets/status_icon.dart';

class OpayPaymentHistory extends StatelessWidget {
  const OpayPaymentHistory({
    super.key,
    required this.items,
    required this.onVerify,
    this.verifyingReference,
  });

  final List<dynamic> items;
  final Future<void> Function(Map<String, dynamic> item) onVerify;
  final String? verifyingReference;

  @override
  Widget build(BuildContext context) {
    if (items.isEmpty) {
      return const CardSurface(
        child: EmptyState(
          icon: Icons.sync_alt_rounded,
          title: 'No OPay payments yet',
          message: 'Every OPay checkout and its final status will appear here.',
        ),
      );
    }

    return Column(children: [
      for (final raw in items) ...[
        _OpayPaymentCard(
          item: asMap(raw),
          onVerify: onVerify,
          verifying: asMap(raw)['reference']?.toString() == verifyingReference,
        ),
        const SizedBox(height: 11),
      ],
    ]);
  }
}

class _OpayPaymentCard extends StatelessWidget {
  const _OpayPaymentCard({
    required this.item,
    required this.onVerify,
    required this.verifying,
  });

  final Map<String, dynamic> item;
  final Future<void> Function(Map<String, dynamic> item) onVerify;
  final bool verifying;

  @override
  Widget build(BuildContext context) {
    final status = (item['status'] ?? 'pending').toString().toUpperCase();
    final opayStatus = (item['opay_status'] ?? status).toString().toUpperCase();
    final reference = item['reference']?.toString() ?? '';
    final canVerify = status != 'SUCCESS';
    final environment =
        (item['environment'] ?? 'live').toString().toUpperCase();

    return CardSurface(
      padding: const EdgeInsets.all(16),
      child: Row(crossAxisAlignment: CrossAxisAlignment.start, children: [
        StatusIcon(status: status),
        const SizedBox(width: 12),
        Expanded(
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Text(
                '${prettyLabel((item['purpose'] ?? 'payment').toString())} via OPay',
                style: const TextStyle(
                  color: AppColors.navy,
                  fontWeight: FontWeight.w900,
                ),
              ),
              const SizedBox(height: 4),
              Text(
                reference,
                maxLines: 1,
                overflow: TextOverflow.ellipsis,
                style: const TextStyle(color: AppColors.muted, fontSize: 10),
              ),
              const SizedBox(height: 7),
              Wrap(spacing: 6, runSpacing: 5, children: [
                _InfoBadge(label: environment, demo: environment == 'DEMO'),
                _InfoBadge(label: 'OPay: $opayStatus'),
              ]),
              const SizedBox(height: 3),
              Text(
                item['created_at']?.toString() ?? '',
                style: const TextStyle(color: AppColors.muted, fontSize: 10),
              ),
            ],
          ),
        ),
        const SizedBox(width: 8),
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
            if (canVerify)
              TextButton.icon(
                onPressed: verifying ? null : () => onVerify(item),
                icon: verifying
                    ? const SizedBox(
                        width: 13,
                        height: 13,
                        child: CircularProgressIndicator(strokeWidth: 2),
                      )
                    : const Icon(Icons.refresh_rounded, size: 15),
                label: Text(verifying ? 'Checking' : 'Verify'),
              ),
          ],
        ),
      ]),
    );
  }
}

class _InfoBadge extends StatelessWidget {
  const _InfoBadge({required this.label, this.demo = false});

  final String label;
  final bool demo;

  @override
  Widget build(BuildContext context) => Container(
        padding: const EdgeInsets.symmetric(horizontal: 7, vertical: 4),
        decoration: BoxDecoration(
          color: demo ? const Color(0xfffff3cd) : AppColors.pageBackground,
          borderRadius: BorderRadius.circular(7),
        ),
        child: Text(
          label,
          style: TextStyle(
            color: demo ? const Color(0xff8a5a00) : AppColors.muted,
            fontSize: 9,
            fontWeight: FontWeight.w800,
          ),
        ),
      );
}
