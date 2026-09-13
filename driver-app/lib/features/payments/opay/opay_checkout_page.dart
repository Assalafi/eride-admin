import 'dart:async';

import 'package:flutter/foundation.dart';
import 'package:flutter/material.dart';
import 'package:webview_flutter/webview_flutter.dart';

import '../../../core/api_client.dart';
import '../../../core/theme/app_colors.dart';
import 'opay_web_view_stub.dart'
    if (dart.library.js_interop) 'opay_web_view_web.dart';

/// Full-screen OPay hosted checkout wrapper with status polling.
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
  WebViewController? _controller;
  Timer? _statusTimer;
  double _progress = 0;
  bool _checking = false;
  bool _terminal = false;
  String? _pageError;

  @override
  void initState() {
    super.initState();
    if (!kIsWeb) {
      _controller = WebViewController()
        ..setJavaScriptMode(JavaScriptMode.unrestricted)
        ..setBackgroundColor(Colors.white)
        ..setNavigationDelegate(NavigationDelegate(
          onProgress: (value) {
            if (mounted) setState(() => _progress = value / 100);
          },
          onPageStarted: (_) {
            if (mounted) setState(() => _pageError = null);
          },
          onPageFinished: (_) => _checkStatus(),
          onWebResourceError: (error) {
            if (error.isForMainFrame == true && mounted) {
              setState(() => _pageError = error.description);
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

    _statusTimer = Timer.periodic(
      const Duration(seconds: 4),
      (_) => _checkStatus(),
    );
  }

  @override
  void dispose() {
    _statusTimer?.cancel();
    super.dispose();
  }

  Future<void> _checkStatus({bool force = false}) async {
    if (_checking || _terminal) return;
    _checking = true;
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
      _checking = false;
    }
  }

  void _finish(bool paid, String message) {
    if (!mounted || _terminal) return;
    _terminal = true;
    _statusTimer?.cancel();
    Navigator.of(context).pop(OpayCheckoutResult(paid: paid, message: message));
  }

  Future<void> _goBack() async {
    if (_controller != null && await _controller!.canGoBack()) {
      await _controller!.goBack();
      return;
    }
    if (mounted) Navigator.of(context).pop();
  }

  @override
  Widget build(BuildContext context) => Scaffold(
        backgroundColor: AppColors.pageBackground,
        appBar: AppBar(
          backgroundColor: Colors.white,
          surfaceTintColor: Colors.transparent,
          scrolledUnderElevation: 0,
          leading: IconButton(
            onPressed: _goBack,
            icon: const Icon(Icons.arrow_back_rounded),
          ),
          titleSpacing: 0,
          title: const Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Text(
                'Secure OPay checkout',
                style: TextStyle(fontSize: 16, fontWeight: FontWeight.w900),
              ),
              Text(
                'Payment stays inside eRide',
                style: TextStyle(
                  fontSize: 10,
                  fontWeight: FontWeight.w500,
                  color: AppColors.navy,
                ),
              ),
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
            child: _progress > 0 && _progress < 1
                ? LinearProgressIndicator(
                    value: _progress,
                    color: AppColors.teal,
                  )
                : const SizedBox(height: 3),
          ),
        ),
        body: Column(children: [
          _SecureBanner(amount: widget.amount),
          const SizedBox(height: 14),
          Expanded(
            child: _pageError != null && !kIsWeb
                ? _CheckoutError(
                    message: _pageError!,
                    onRetry: () => _controller?.reload(),
                  )
                : kIsWeb
                    ? OpayWebView(url: widget.checkoutUrl)
                    : WebViewWidget(controller: _controller!),
          ),
        ]),
      );
}

class _SecureBanner extends StatelessWidget {
  const _SecureBanner({required this.amount});

  final double amount;

  @override
  Widget build(BuildContext context) => Padding(
        padding: const EdgeInsets.fromLTRB(16, 14, 16, 0),
        child: Container(
          width: double.infinity,
          padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 13),
          decoration: BoxDecoration(
            color: Colors.white,
            borderRadius: BorderRadius.circular(18),
            border: Border.all(color: AppColors.stroke),
            boxShadow: [
              BoxShadow(
                color: AppColors.navy.withValues(alpha: .04),
                blurRadius: 18,
                offset: const Offset(0, 8),
              ),
            ],
          ),
          child: Row(children: [
            Container(
              width: 34,
              height: 34,
              decoration: BoxDecoration(
                color: AppColors.success.withValues(alpha: .12),
                borderRadius: BorderRadius.circular(11),
              ),
              child: const Icon(
                Icons.lock_rounded,
                color: AppColors.success,
                size: 17,
              ),
            ),
            const SizedBox(width: 11),
            const Expanded(
              child: Text(
                'Encrypted payment session',
                style: TextStyle(
                  color: AppColors.navy,
                  fontSize: 11,
                  fontWeight: FontWeight.w700,
                ),
              ),
            ),
            Text(
              '₦${amount.toStringAsFixed(2)}',
              style: const TextStyle(
                color: AppColors.navy,
                fontWeight: FontWeight.w900,
              ),
            ),
          ]),
        ),
      );
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
              const Icon(
                Icons.wifi_off_rounded,
                size: 42,
                color: AppColors.navy,
              ),
              const SizedBox(height: 14),
              const Text(
                'Checkout could not load',
                style: TextStyle(
                  color: AppColors.navy,
                  fontSize: 17,
                  fontWeight: FontWeight.w900,
                ),
              ),
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
