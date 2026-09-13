import 'package:flutter/material.dart';

import '../../core/theme/app_colors.dart';

/// The rounded logo tile used in navigation and heroes.
class BrandMark extends StatelessWidget {
  const BrandMark({super.key, this.size = 42});

  final double size;

  @override
  Widget build(BuildContext context) => Container(
        width: size,
        height: size,
        decoration: BoxDecoration(
          color: Colors.white,
          borderRadius: BorderRadius.circular(size * .3),
          boxShadow: [
            BoxShadow(
              color: AppColors.navy.withValues(alpha: .10),
              blurRadius: 14,
              offset: const Offset(0, 7),
            ),
          ],
        ),
        clipBehavior: Clip.antiAlias,
        child: Transform.scale(
          scale: 1.36,
          child: Image.asset('assets/images/logo.png'),
        ),
      );
}

/// The eRide driver wordmark (logo + name).
class Wordmark extends StatelessWidget {
  const Wordmark({super.key, this.width = 138, this.light = false});

  final double width;
  final bool light;

  @override
  Widget build(BuildContext context) => SizedBox(
        width: width,
        child: Row(mainAxisSize: MainAxisSize.min, children: [
          BrandMark(size: width * .28),
          SizedBox(width: width * .08),
          Expanded(
            child: Column(
              mainAxisSize: MainAxisSize.min,
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                FittedBox(
                  fit: BoxFit.scaleDown,
                  alignment: Alignment.centerLeft,
                  child: Text(
                    'eRide',
                    style: TextStyle(
                      color: light ? Colors.white : AppColors.navy,
                      fontSize: 23,
                      fontWeight: FontWeight.w900,
                      letterSpacing: -.5,
                    ),
                  ),
                ),
                Text(
                  'DRIVER',
                  style: TextStyle(
                    color: light ? AppColors.teal : AppColors.muted,
                    fontSize: 8,
                    fontWeight: FontWeight.w900,
                    letterSpacing: 2.4,
                  ),
                ),
              ],
            ),
          ),
        ]),
      );
}
