import 'package:flutter/material.dart';

class OpayWebView extends StatelessWidget {
  const OpayWebView({super.key, required this.url});

  final String url;

  @override
  Widget build(BuildContext context) => const Center(
        child: Text('Embedded checkout is only available on web.'),
      );
}
