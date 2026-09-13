import 'package:flutter/foundation.dart';
import 'package:flutter/material.dart';

import '../../../core/theme/app_colors.dart';
import '../../../shared/widgets/brand.dart';

/// Credentials form used by the login screen.
class LoginCard extends StatelessWidget {
  const LoginCard({
    super.key,
    required this.formKey,
    required this.email,
    required this.password,
    required this.obscure,
    required this.busy,
    required this.hasSavedSession,
    required this.showBrand,
    required this.onToggleObscure,
    required this.onSubmit,
    required this.onBiometric,
  });

  final GlobalKey<FormState> formKey;
  final TextEditingController email;
  final TextEditingController password;
  final bool obscure;
  final bool busy;
  final bool hasSavedSession;
  final bool showBrand;
  final VoidCallback onToggleObscure;
  final VoidCallback onSubmit;
  final VoidCallback onBiometric;

  @override
  Widget build(BuildContext context) =>
      Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
        if (showBrand) ...[
          const Center(child: Wordmark(width: 158)),
          const SizedBox(height: 30),
        ],
        const Text(
          'Welcome back',
          style: TextStyle(
            color: AppColors.navy,
            fontSize: 29,
            fontWeight: FontWeight.w900,
            letterSpacing: -.8,
            height: 1.1,
          ),
        ),
        const SizedBox(height: 8),
        const Text(
          'Sign in to continue to your eRide driver account.',
          style: TextStyle(
            color: AppColors.muted,
            height: 1.5,
            fontSize: 13,
          ),
        ),
        const SizedBox(height: 26),
        Form(
          key: formKey,
          child: Column(children: [
            TextFormField(
              controller: email,
              keyboardType: TextInputType.emailAddress,
              textInputAction: TextInputAction.next,
              autofillHints: const [AutofillHints.email],
              decoration: const InputDecoration(
                labelText: 'Email address',
                prefixIcon: Icon(Icons.mail_outline_rounded),
              ),
              validator: (value) => value == null || !value.contains('@')
                  ? 'Enter a valid email address'
                  : null,
            ),
            const SizedBox(height: 14),
            TextFormField(
              controller: password,
              obscureText: obscure,
              textInputAction: TextInputAction.done,
              autofillHints: const [AutofillHints.password],
              decoration: InputDecoration(
                labelText: 'Password',
                prefixIcon: const Icon(Icons.lock_outline_rounded),
                suffixIcon: IconButton(
                  onPressed: onToggleObscure,
                  icon: Icon(obscure
                      ? Icons.visibility_outlined
                      : Icons.visibility_off_outlined),
                ),
              ),
              validator: (value) => value == null || value.length < 6
                  ? 'Enter your password'
                  : null,
              onFieldSubmitted: (_) => onSubmit(),
            ),
            const SizedBox(height: 22),
            SizedBox(
              width: double.infinity,
              child: FilledButton(
                onPressed: busy ? null : onSubmit,
                child: busy
                    ? const SizedBox.square(
                        dimension: 21,
                        child: CircularProgressIndicator(
                          strokeWidth: 2,
                          color: Colors.white,
                        ),
                      )
                    : const Row(
                        mainAxisAlignment: MainAxisAlignment.center,
                        children: [
                          Text('Sign in'),
                          SizedBox(width: 8),
                          Icon(Icons.arrow_forward_rounded, size: 18),
                        ],
                      ),
              ),
            ),
            if (hasSavedSession && !kIsWeb) ...[
              const SizedBox(height: 18),
              const Row(children: [
                Expanded(child: Divider(height: 1)),
                Padding(
                  padding: EdgeInsets.symmetric(horizontal: 12),
                  child: Text(
                    'or',
                    style: TextStyle(color: AppColors.muted, fontSize: 11),
                  ),
                ),
                Expanded(child: Divider(height: 1)),
              ]),
              const SizedBox(height: 18),
              SizedBox(
                width: double.infinity,
                child: OutlinedButton.icon(
                  onPressed: busy ? null : onBiometric,
                  icon: const Icon(Icons.fingerprint_rounded),
                  label: const Text('Unlock with biometrics'),
                ),
              ),
            ],
          ]),
        ),
        const SizedBox(height: 22),
        Container(
          width: double.infinity,
          padding: const EdgeInsets.all(14),
          decoration: BoxDecoration(
            color: AppColors.mint.withValues(alpha: .65),
            borderRadius: BorderRadius.circular(16),
            border: Border.all(color: AppColors.mint),
          ),
          child: const Row(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Icon(
                Icons.verified_user_outlined,
                color: AppColors.navy,
                size: 18,
              ),
              SizedBox(width: 10),
              Expanded(
                child: Text(
                  'Protected by eRide secure sign-in.',
                  style: TextStyle(
                    color: AppColors.navy,
                    fontSize: 12,
                    height: 1.4,
                    fontWeight: FontWeight.w700,
                  ),
                ),
              ),
            ],
          ),
        ),
      ]);
}
