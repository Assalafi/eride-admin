import 'package:flutter/material.dart';
import 'package:provider/provider.dart';

import '../../core/auth_state.dart';
import '../../core/theme/app_colors.dart';
import '../../shared/widgets/app_glow.dart';
import 'widgets/login_brand_panel.dart';
import 'widgets/login_card.dart';

/// Driver sign-in screen with an adaptive desktop/mobile layout.
class LoginScreen extends StatefulWidget {
  const LoginScreen({super.key});

  @override
  State<LoginScreen> createState() => _LoginScreenState();
}

class _LoginScreenState extends State<LoginScreen> {
  final _formKey = GlobalKey<FormState>();
  final _email = TextEditingController();
  final _password = TextEditingController();
  bool _obscure = true;

  @override
  void dispose() {
    _email.dispose();
    _password.dispose();
    super.dispose();
  }

  Future<void> _submit() async {
    if (!(_formKey.currentState?.validate() ?? false)) return;
    final auth = context.read<AuthState>();
    final success = await auth.login(_email.text, _password.text);
    if (!success && mounted) _toast(auth.error ?? 'Sign in failed');
  }

  Future<void> _biometric() async {
    final auth = context.read<AuthState>();
    final success = await auth.biometricLogin();
    if (!success && mounted) {
      _toast(auth.error ?? 'Biometric login is not available yet');
    }
  }

  void _toast(String message) => ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(content: Text(message)),
      );

  LoginCard _card({required bool showBrand}) => LoginCard(
        formKey: _formKey,
        email: _email,
        password: _password,
        obscure: _obscure,
        busy: context.watch<AuthState>().busy,
        hasSavedSession: context.watch<AuthState>().hasSavedSession,
        showBrand: showBrand,
        onToggleObscure: () => setState(() => _obscure = !_obscure),
        onSubmit: _submit,
        onBiometric: _biometric,
      );

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      body: LayoutBuilder(
        builder: (context, constraints) {
          if (constraints.maxWidth < 920) {
            return _buildMobile(_card(showBrand: true));
          }
          return _buildWide(_card(showBrand: false));
        },
      ),
    );
  }

  Widget _buildMobile(Widget card) => Container(
        decoration: const BoxDecoration(gradient: AppColors.brandGradient),
        child: Stack(children: [
          Positioned(
            top: -120,
            right: -90,
            child: AppGlow(
              size: 280,
              color: AppColors.teal.withValues(alpha: .20),
            ),
          ),
          Positioned(
            bottom: -150,
            left: -110,
            child: AppGlow(
              size: 340,
              color: Colors.white.withValues(alpha: .06),
            ),
          ),
          SafeArea(
            child: Center(
              child: SingleChildScrollView(
                padding: const EdgeInsets.fromLTRB(22, 28, 22, 34),
                child: ConstrainedBox(
                  constraints: const BoxConstraints(maxWidth: 460),
                  child: Column(children: [
                    Container(
                      padding: const EdgeInsets.all(26),
                      decoration: BoxDecoration(
                        color: Colors.white,
                        borderRadius: BorderRadius.circular(28),
                        boxShadow: [
                          BoxShadow(
                            color: AppColors.navyDark.withValues(alpha: .30),
                            blurRadius: 40,
                            offset: const Offset(0, 20),
                          ),
                        ],
                      ),
                      child: card,
                    ),
                    const SizedBox(height: 22),
                    Text(
                      'Need help signing in? Contact your eRide administrator.',
                      textAlign: TextAlign.center,
                      style: TextStyle(
                        color: Colors.white.withValues(alpha: .66),
                        fontSize: 11,
                        height: 1.4,
                      ),
                    ),
                  ]),
                ),
              ),
            ),
          ),
        ]),
      );

  Widget _buildWide(Widget card) => Row(children: [
        const Expanded(flex: 11, child: LoginBrandPanel()),
        Expanded(
          flex: 9,
          child: Container(
            color: AppColors.pageBackground,
            child: Center(
              child: SingleChildScrollView(
                padding: const EdgeInsets.all(48),
                child: ConstrainedBox(
                  constraints: const BoxConstraints(maxWidth: 450),
                  child: Container(
                    padding: const EdgeInsets.all(34),
                    decoration: BoxDecoration(
                      color: Colors.white,
                      borderRadius: BorderRadius.circular(28),
                      border: Border.all(color: AppColors.stroke),
                      boxShadow: [
                        BoxShadow(
                          color: AppColors.navy.withValues(alpha: .07),
                          blurRadius: 36,
                          offset: const Offset(0, 18),
                        ),
                      ],
                    ),
                    child: card,
                  ),
                ),
              ),
            ),
          ),
        ),
      ]);
}
