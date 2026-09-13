import 'package:flutter/material.dart';

import '../../core/theme/app_colors.dart';
import '../../shared/widgets/app_glow.dart';

/// Branded launch screen shown while the saved session is restored.
class SplashScreen extends StatefulWidget {
  const SplashScreen({super.key});

  @override
  State<SplashScreen> createState() => _SplashScreenState();
}

class _SplashScreenState extends State<SplashScreen>
    with SingleTickerProviderStateMixin {
  late final AnimationController _controller = AnimationController(
    vsync: this,
    duration: const Duration(milliseconds: 900),
  )..forward();

  @override
  void dispose() {
    _controller.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    final fade = CurvedAnimation(parent: _controller, curve: Curves.easeOut);
    final rise = Tween<Offset>(
      begin: const Offset(0, .05),
      end: Offset.zero,
    ).animate(CurvedAnimation(
      parent: _controller,
      curve: Curves.easeOutCubic,
    ));

    return Scaffold(
      backgroundColor: Colors.white,
      body: Stack(children: [
        Positioned(
          top: -120,
          right: -100,
          child: AppGlow(
            size: 280,
            color: AppColors.teal.withValues(alpha: .10),
          ),
        ),
        Positioned(
          bottom: -150,
          left: -120,
          child: AppGlow(
            size: 320,
            color: AppColors.navy.withValues(alpha: .06),
          ),
        ),
        FadeTransition(
          opacity: fade,
          child: SlideTransition(
            position: rise,
            child: Center(
              child: Column(mainAxisSize: MainAxisSize.min, children: [
                Image.asset(
                  'assets/images/splash.png',
                  width: 250,
                  fit: BoxFit.contain,
                ),
                const SizedBox(height: 52),
                const SizedBox(
                  width: 30,
                  height: 30,
                  child: CircularProgressIndicator(
                    strokeWidth: 3,
                    color: AppColors.teal,
                  ),
                ),
                const SizedBox(height: 18),
                const Text(
                  'Loading your workspace…',
                  style: TextStyle(
                    color: AppColors.muted,
                    fontSize: 12.5,
                    letterSpacing: .2,
                  ),
                ),
              ]),
            ),
          ),
        ),
      ]),
    );
  }
}
