import 'dart:async';

import 'package:flutter/foundation.dart';
import 'package:flutter/material.dart';
import 'package:webview_flutter/webview_flutter.dart';

import '../core/api_client.dart';
import 'opay_web_view_stub.dart'
    if (dart.library.js_interop) 'opay_web_view_web.dart';

const _navy = Color(0xFF17105B);
const _pageBackground = Color(0xFFF7F8FC);
const _success = Color(0xFF2EA66F);
const _teal = Color(0xFF2CB6C5);

class OpayCheckoutPage extends StatefulWidget {
  const OpayCheckoutPage({
    super.key,
    required this.checkoutUrl,
    required this.reference,
    required this.amount,
  });

  final String checkoutUrl;
  final String reference;
  final double amount;

  @override
  State<OpayCheckoutPage> createState() => _OpayCheckoutPageState();
}

class _OpayCheckoutPageState extends State<OpayCheckoutPage> {
  WebViewController? controller;
  Timer? statusTimer;
  double progress = 0;
  bool checking = false;
  bool terminal = false;
  String? pageError;

  @override
  void initState() {
    super.initState();
    if (!kIsWeb) {
      controller = WebViewController()
        ..setJavaScriptMode(JavaScriptMode.unrestricted)
        ..setBackgroundColor(Colors.white)
        ..setNavigationDelegate(NavigationDelegate(
          onProgress: (value) {
            if (mounted) setState(() => progress = value / 100);
          },
          onPageStarted: (_) {
            if (mounted) setState(() => pageError = null);
          },
          onPageFinished: (_) => _checkStatus(),
          onWebResourceError: (error) {
            if (error.isForMainFrame == true && mounted) {
              setState(() => pageError = error.description);
            }
          },
          onNavigationRequest: (request) {
            final url = request.url.toLowerCase();
            if (url.contains('/driver/payment/return')) {
              _checkStatus(force: true);
            } else if (url.contains('/driver/payment/cancel')) {
              _finish(false, 'Payment was cancelled.');
            }
            return NavigationDecision.navigate;
          },
        ))
        ..loadRequest(Uri.parse(widget.checkoutUrl));
    }

    statusTimer = Timer.periodic(
      const Duration(seconds: 4),
      (_) => _checkStatus(),
    );
  }

  @override
  void dispose() {
    statusTimer?.cancel();
    super.dispose();
  }

  Future<void> _checkStatus({bool force = false}) async {
    if (checking || terminal) return;
    checking = true;
    try {
      final response = await ApiClient.instance.get(
        'driver/payments/opay/${widget.reference}',
      );
      final data = response['data'];
      final status =
          data is Map ? (data['status'] ?? '').toString().toLowerCase() : '';
      if (['success', 'successful', 'paid', 'completed'].contains(status)) {
        _finish(true, 'Payment confirmed successfully.');
      } else if (['fail', 'failed', 'close', 'cancelled'].contains(status)) {
        _finish(false, 'Payment was not completed.');
      } else if (force && mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          const SnackBar(content: Text('Confirming your payment with OPay…')),
        );
      }
    } on ApiException {
      // The checkout remains open and the next poll retries automatically.
    } finally {
      checking = false;
    }
  }

  void _finish(bool paid, String message) {
    if (!mounted || terminal) return;
    terminal = true;
    statusTimer?.cancel();
    Navigator.of(context).pop(OpayCheckoutResult(paid: paid, message: message));
  }

  Future<void> _goBack() async {
    if (controller != null && await controller!.canGoBack()) {
      await controller!.goBack();
      return;
    }
    if (mounted) Navigator.of(context).pop();
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: _pageBackground,
      appBar: AppBar(
        leading: IconButton(
          onPressed: _goBack,
          icon: const Icon(Icons.arrow_back_rounded),
        ),
        titleSpacing: 0,
        title: const Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Text('Secure OPay checkout',
                style: TextStyle(fontSize: 16, fontWeight: FontWeight.w800)),
            Text('Payment stays inside E-RIDE',
                style: TextStyle(fontSize: 10, fontWeight: FontWeight.w500)),
          ],
        ),
        actions: [
          IconButton(
            tooltip: 'Check payment status',
            onPressed: () => _checkStatus(force: true),
            icon: const Icon(Icons.refresh_rounded),
          ),
        ],
        bottom: PreferredSize(
          preferredSize: const Size.fromHeight(3),
          child: progress > 0 && progress < 1
              ? LinearProgressIndicator(value: progress, color: _teal)
              : const SizedBox(height: 3),
        ),
      ),
      body: Column(
        children: [
          Container(
            width: double.infinity,
            padding: const EdgeInsets.symmetric(horizontal: 18, vertical: 10),
            color: Colors.white,
            child: Row(
              children: [
                const Icon(Icons.lock_rounded, color: _success, size: 16),
                const SizedBox(width: 7),
                const Expanded(
                  child: Text('Encrypted payment session',
                      style: TextStyle(color: _navy, fontSize: 11)),
                ),
                Text(
                  '₦${widget.amount.toStringAsFixed(2)}',
                  style: const TextStyle(
                      color: _navy, fontWeight: FontWeight.w900),
                ),
              ],
            ),
          ),
          Expanded(
            child: pageError != null && !kIsWeb
                ? _CheckoutError(
                    message: pageError!,
                    onRetry: () => controller?.reload(),
                  )
                : kIsWeb
                    ? OpayWebView(url: widget.checkoutUrl)
                    : WebViewWidget(controller: controller!),
          ),
        ],
      ),
    );
  }
}

class _CheckoutError extends StatelessWidget {
  const _CheckoutError({required this.message, required this.onRetry});

  final String message;
  final VoidCallback onRetry;

  @override
  Widget build(BuildContext context) => Center(
        child: Padding(
          padding: const EdgeInsets.all(28),
          child: Column(
            mainAxisSize: MainAxisSize.min,
            children: [
              const Icon(Icons.wifi_off_rounded, size: 42, color: _navy),
              const SizedBox(height: 14),
              const Text('Checkout could not load',
                  style: TextStyle(
                      color: _navy, fontSize: 17, fontWeight: FontWeight.w900)),
              const SizedBox(height: 7),
              Text(message, textAlign: TextAlign.center),
              const SizedBox(height: 18),
              FilledButton(onPressed: onRetry, child: const Text('Try again')),
            ],
          ),
        ),
      );
}

class OpayCheckoutResult {
  const OpayCheckoutResult({required this.paid, required this.message});

  final bool paid;
  final String message;
}
