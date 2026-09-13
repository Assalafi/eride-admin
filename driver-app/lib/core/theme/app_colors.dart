import 'package:flutter/material.dart';

/// Central colour palette for the eRide Driver app.
///
/// Every screen must source colours from here so the brand stays consistent.
class AppColors {
  AppColors._();

  static const Color navy = Color(0xFF17105B);
  static const Color navyDark = Color(0xFF0F0A3C);
  static const Color navyMid = Color(0xFF2B2380);
  static const Color teal = Color(0xFF2CB6C5);
  static const Color mint = Color(0xFFE5F7F6);
  static const Color pageBackground = Color(0xFFF7F8FC);
  static const Color ink = Color(0xFF182039);
  static const Color muted = Color(0xFF7E879B);
  static const Color success = Color(0xFF2EA66F);
  static const Color warning = Color(0xFFE39A22);
  static const Color danger = Color(0xFFE5484D);
  static const Color stroke = Color(0xFFEDEFF5);

  static const LinearGradient brandGradient = LinearGradient(
    begin: Alignment.topLeft,
    end: Alignment.bottomRight,
    colors: [navyDark, navy, navyMid],
  );
}
