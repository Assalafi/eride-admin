import 'package:flutter/material.dart';

import '../../core/theme/app_colors.dart';
import 'card_surface.dart';

/// Error panel with a retry affordance, used by async pages.
class ErrorCard extends StatelessWidget {
  const ErrorCard({super.key, required this.message, required this.onRetry});

  final String message;
  final VoidCallback onRetry;

  @override
  Widget build(BuildContext context) => CardSurface(
        child: Column(children: [
          Container(
            width: 58,
            height: 58,
            decoration: BoxDecoration(
              color: const Color(0xFFFFF3DD),
              borderRadius: BorderRadius.circular(19),
            ),
            child: const Icon(
              Icons.cloud_off_rounded,
              color: AppColors.warning,
              size: 27,
            ),
          ),
          const SizedBox(height: 15),
          const Text(
            'We could not load this page',
            style: TextStyle(
              color: AppColors.navy,
              fontWeight: FontWeight.w900,
            ),
          ),
          const SizedBox(height: 6),
          Text(
            message,
            textAlign: TextAlign.center,
            style: const TextStyle(color: AppColors.muted, fontSize: 12),
          ),
          const SizedBox(height: 16),
          OutlinedButton(onPressed: onRetry, child: const Text('Try again')),
        ]),
      );
}
