import 'package:flutter/material.dart';
import 'package:provider/provider.dart';

import 'core/auth_state.dart';
import 'core/theme/app_theme.dart';
import 'features/auth/login_screen.dart';
import 'features/shell/app_shell.dart';
import 'features/splash/splash_screen.dart';

/// Root widget: selects splash, login or the authenticated shell based on
/// [AuthState].
class DriverApp extends StatelessWidget {
  const DriverApp({super.key});

  @override
  Widget build(BuildContext context) => MaterialApp(
        title: 'eRide Driver',
        debugShowCheckedModeBanner: false,
        theme: AppTheme.light(),
        home: Consumer<AuthState>(
          builder: (context, auth, _) {
            if (auth.initializing) return const SplashScreen();
            if (auth.isLoggedIn) return const AppShell();
            return const LoginScreen();
          },
        ),
      );
}
