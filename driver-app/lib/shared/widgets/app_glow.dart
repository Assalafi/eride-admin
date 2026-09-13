import 'package:flutter/material.dart';

/// A soft radial light used to decorate gradient surfaces.
class AppGlow extends StatelessWidget {
  const AppGlow({super.key, required this.size, required this.color});

  final double size;
  final Color color;

  @override
  Widget build(BuildContext context) => IgnorePointer(
        child: Container(
          width: size,
          height: size,
          decoration: BoxDecoration(
            shape: BoxShape.circle,
            color: color,
            boxShadow: [
              BoxShadow(color: color, blurRadius: 90, spreadRadius: 28),
            ],
          ),
        ),
      );
}
