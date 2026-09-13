import 'dart:ui_web' as ui_web;

import 'package:flutter/material.dart';
import 'package:web/web.dart' as web;

/// Web implementation of the embedded OPay checkout iframe.
class OpayWebView extends StatefulWidget {
  const OpayWebView({super.key, required this.url});

  final String url;

  @override
  State<OpayWebView> createState() => _OpayWebViewState();
}

class _OpayWebViewState extends State<OpayWebView> {
  late final String _viewType;

  @override
  void initState() {
    super.initState();
    _viewType = 'opay-checkout-${identityHashCode(this)}';
    ui_web.platformViewRegistry.registerViewFactory(_viewType, (viewId) {
      final frame = web.HTMLIFrameElement()
        ..src = widget.url
        ..style.border = '0'
        ..style.width = '100%'
        ..style.height = '100%';
      frame.setAttribute(
        'allow',
        'payment *; clipboard-read *; clipboard-write *',
      );
      return frame;
    });
  }

  @override
  Widget build(BuildContext context) => HtmlElementView(viewType: _viewType);
}
