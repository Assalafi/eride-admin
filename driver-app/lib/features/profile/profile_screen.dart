import 'package:flutter/foundation.dart';
import 'package:flutter/material.dart';
import 'package:provider/provider.dart';

import '../../core/api_client.dart';
import '../../core/auth_state.dart';
import '../../core/theme/app_colors.dart';
import '../../core/utils/parsers.dart';
import '../../shared/widgets/app_page.dart';
import '../../shared/widgets/card_surface.dart';
import '../../shared/widgets/error_card.dart';
import '../../shared/widgets/loading_cards.dart';
import '../../shared/widgets/page_header.dart';
import '../../shared/widgets/section_heading.dart';
import 'change_password_sheet.dart';
import 'widgets/profile_detail_row.dart';
import 'widgets/profile_hero.dart';
import 'widgets/setting_icon.dart';

/// Profile tab: personal details and account security.
class ProfileScreen extends StatefulWidget {
  const ProfileScreen({super.key});

  @override
  State<ProfileScreen> createState() => _ProfileScreenState();
}

class _ProfileScreenState extends State<ProfileScreen> {
  late Future<Map<String, dynamic>> _future;

  @override
  void initState() {
    super.initState();
    _future = _load();
  }

  Future<Map<String, dynamic>> _load() => ApiClient.instance
      .get('driver/profile')
      .then((response) => asMap(response['data']));

  Future<void> _refresh() async {
    final future = _load();
    setState(() => _future = future);
    await future;
  }

  @override
  Widget build(BuildContext context) {
    final auth = context.watch<AuthState>();

    return AppPage(
      title: 'Profile',
      subtitle: 'Personal details and account security.',
      actions: [
        HeaderAction(
          icon: Icons.refresh_rounded,
          tooltip: 'Refresh profile',
          onPressed: _refresh,
        ),
      ],
      onRefresh: _refresh,
      child: FutureBuilder<Map<String, dynamic>>(
        future: _future,
        builder: (context, snapshot) {
          if (snapshot.connectionState == ConnectionState.waiting) {
            return const LoadingCards();
          }
          if (snapshot.hasError) {
            return ErrorCard(
              message: snapshot.error.toString(),
              onRetry: _refresh,
            );
          }

          final profile = snapshot.data ?? const <String, dynamic>{};
          return Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              ProfileHero(profile: profile, fallbackName: auth.displayName),
              const SizedBox(height: 26),
              const SectionHeading(title: 'Driver information'),
              const SizedBox(height: 14),
              CardSurface(
                child: Column(children: [
                  ProfileDetailRow(
                    icon: Icons.phone_outlined,
                    label: 'Phone number',
                    value: profile['phone_number']?.toString(),
                  ),
                  const Divider(height: 1),
                  ProfileDetailRow(
                    icon: Icons.badge_outlined,
                    label: 'Driver licence',
                    value: profile['license_number']?.toString(),
                  ),
                  const Divider(height: 1),
                  ProfileDetailRow(
                    icon: Icons.event_available_outlined,
                    label: 'Licence expiry',
                    value: profile['license_expiry']?.toString(),
                  ),
                  const Divider(height: 1),
                  ProfileDetailRow(
                    icon: Icons.location_on_outlined,
                    label: 'Address',
                    value: profile['address']?.toString(),
                  ),
                ]),
              ),
              const SizedBox(height: 26),
              const SectionHeading(title: 'Security'),
              const SizedBox(height: 14),
              CardSurface(
                child: Column(children: [
                  ListTile(
                    contentPadding: EdgeInsets.zero,
                    leading: const SettingIcon(
                      icon: Icons.fingerprint_rounded,
                    ),
                    title: const Text(
                      'Biometric app lock',
                      style: TextStyle(
                        color: AppColors.navy,
                        fontWeight: FontWeight.w900,
                      ),
                    ),
                    subtitle: Text(
                      kIsWeb
                          ? 'Available in the Android app'
                          : 'Require your fingerprint or device biometrics at launch',
                      style: const TextStyle(
                        color: AppColors.muted,
                        fontSize: 11,
                      ),
                    ),
                    trailing: Switch(
                      value: auth.biometricEnabled,
                      onChanged: kIsWeb
                          ? null
                          : (value) async {
                              final enabled = await auth.toggleBiometric(value);
                              if (!enabled && context.mounted) {
                                ScaffoldMessenger.of(context).showSnackBar(
                                  const SnackBar(
                                    content: Text(
                                      'Biometrics are unavailable or verification was cancelled.',
                                    ),
                                  ),
                                );
                              }
                            },
                    ),
                  ),
                  const Divider(height: 12),
                  ListTile(
                    contentPadding: EdgeInsets.zero,
                    leading: const SettingIcon(icon: Icons.password_rounded),
                    title: const Text(
                      'Change password',
                      style: TextStyle(
                        color: AppColors.navy,
                        fontWeight: FontWeight.w900,
                      ),
                    ),
                    subtitle: const Text(
                      'Update your eRide login password',
                      style: TextStyle(
                        color: AppColors.muted,
                        fontSize: 11,
                      ),
                    ),
                    trailing: const Icon(
                      Icons.chevron_right_rounded,
                      color: AppColors.muted,
                    ),
                    onTap: () => showChangePasswordSheet(context),
                  ),
                ]),
              ),
              const SizedBox(height: 20),
              SizedBox(
                width: double.infinity,
                child: OutlinedButton.icon(
                  onPressed: _confirmSignOut,
                  style: OutlinedButton.styleFrom(
                    foregroundColor: AppColors.danger,
                    side: const BorderSide(color: Color(0xFFFFD7D7)),
                  ),
                  icon: const Icon(Icons.logout_rounded),
                  label: const Text('Sign out of this device'),
                ),
              ),
            ],
          );
        },
      ),
    );
  }

  Future<void> _confirmSignOut() async {
    final confirmed = await showDialog<bool>(
      context: context,
      builder: (context) => AlertDialog(
        title: const Text('Sign out?'),
        content: const Text(
          'You will need your eRide credentials to sign in again.',
        ),
        actions: [
          TextButton(
            onPressed: () => Navigator.pop(context, false),
            child: const Text('Cancel'),
          ),
          FilledButton(
            onPressed: () => Navigator.pop(context, true),
            child: const Text('Sign out'),
          ),
        ],
      ),
    );
    if (confirmed == true && mounted) {
      await context.read<AuthState>().logout();
    }
  }
}
