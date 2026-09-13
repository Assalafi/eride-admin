import 'package:flutter/material.dart';

import '../../core/theme/app_colors.dart';
import 'page_header.dart';

/// Standard page scaffold: a [PageHeader] followed by scrollable content.
///
/// Pass [onRefresh] to enable pull-to-refresh for the whole page.
class AppPage extends StatelessWidget {
  const AppPage({
    super.key,
    required this.title,
    required this.subtitle,
    required this.child,
    this.actions = const [],
    this.leading,
    this.onRefresh,
  });

  final String title;
  final String subtitle;
  final Widget child;
  final List<Widget> actions;
  final Widget? leading;
  final Future<void> Function()? onRefresh;

  @override
  Widget build(BuildContext context) => Scaffold(
        body: AppPageBody(
          onRefresh: onRefresh,
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              PageHeader(
                title: title,
                subtitle: subtitle,
                leading: leading,
                actions: actions,
              ),
              const SizedBox(height: 24),
              child,
            ],
          ),
        ),
      );
}

/// Responsive, max-width constrained, scrollable page body.
class AppPageBody extends StatelessWidget {
  const AppPageBody({super.key, required this.child, this.onRefresh});

  final Widget child;
  final Future<void> Function()? onRefresh;

  @override
  Widget build(BuildContext context) => LayoutBuilder(
        builder: (context, constraints) {
          final wide = MediaQuery.sizeOf(context).width >= 980;
          final scroll = SingleChildScrollView(
            physics: const AlwaysScrollableScrollPhysics(),
            padding: EdgeInsets.fromLTRB(
              wide ? 44 : 20,
              wide ? 38 : 24,
              wide ? 44 : 20,
              40,
            ),
            child: ConstrainedBox(
              constraints: BoxConstraints(
                maxWidth: 1180,
                minWidth: constraints.maxWidth > 1210 ? 900 : 0,
              ),
              child: child,
            ),
          );
          if (onRefresh == null) return scroll;
          return RefreshIndicator(
            color: AppColors.teal,
            onRefresh: onRefresh!,
            child: scroll,
          );
        },
      );
}
