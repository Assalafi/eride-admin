import 'package:flutter/material.dart';

import '../../../core/theme/app_colors.dart';

/// Rounded mint icon chip used beside settings tiles.
class SettingIcon extends StatelessWidget {
  const SettingIcon({super.key, required this.icon});

  final IconData icon;

  @override
  Widget build(BuildContext context) => Container(
        width: 42,
        height: 42,
        decoration: BoxDecoration(
          color: AppColors.mint,
          borderRadius: BorderRadius.circular(13),
        ),
        child: Icon(icon, color: AppColors.navy, size: 20),
      );
}
