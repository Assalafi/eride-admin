import 'package:flutter/material.dart';
import 'package:provider/provider.dart';

import 'app.dart';
import 'core/auth_state.dart';
import 'features/shell/shell_controller.dart';

void main() {
  WidgetsFlutterBinding.ensureInitialized();
  runApp(
    MultiProvider(
      providers: [
        ChangeNotifierProvider(create: (_) => AuthState()..initialize()),
        ChangeNotifierProvider(create: (_) => ShellController()),
      ],
      child: const DriverApp(),
    ),
  );
}
