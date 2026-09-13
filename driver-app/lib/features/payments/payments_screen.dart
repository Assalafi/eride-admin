import 'package:flutter/material.dart';
import 'package:provider/provider.dart';

import '../../core/api_client.dart';
import '../../core/theme/app_colors.dart';
import '../../core/utils/parsers.dart';
import '../../shared/widgets/app_page.dart';
import '../../shared/widgets/error_card.dart';
import '../../shared/widgets/loading_cards.dart';
import '../../shared/widgets/page_header.dart';
import '../shell/shell_controller.dart';
import 'opay/opay_checkout_page.dart';
import 'payment_amount_sheet.dart';
import 'widgets/payment_segments.dart';
import 'widgets/payments_header.dart';
import 'widgets/remittance_list.dart';
import 'widgets/transaction_history.dart';

/// Payments tab: pay remittances and review every transaction.
class PaymentsScreen extends StatefulWidget {
  const PaymentsScreen({super.key});

  @override
  State<PaymentsScreen> createState() => _PaymentsScreenState();
}

class _PaymentsScreenState extends State<PaymentsScreen> {
  late Future<List<Map<String, dynamic>>> _future;
  bool _startingPayment = false;

  @override
  void initState() {
    super.initState();
    _future = _load();
  }

  Future<List<Map<String, dynamic>>> _load() => Future.wait([
        ApiClient.instance.get('driver/dashboard'),
        ApiClient.instance.get('driver/remittance/all'),
        ApiClient.instance.get('driver/transactions'),
      ]);

  Future<void> _refresh() async {
    final future = _load();
    setState(() => _future = future);
    await future;
  }

  @override
  Widget build(BuildContext context) {
    final controller = context.watch<ShellController>();
    final segment = controller.paymentsSegment;
    final content = FutureBuilder<List<Map<String, dynamic>>>(
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

        final dashboard = asMap(snapshot.data![0]['data']);
        final hirePurchase = asMap(dashboard['hire_purchase']);
        final summary = asMap(dashboard['remittance_summary']);
        final allRemittances = asList(snapshot.data![1]['data']);
        final transactions = asList(snapshot.data![2]['data']);

        final pending = allRemittances.where((item) {
          final status = (asMap(item)['status'] ?? '').toString().toLowerCase();
          return ['pending', 'due', 'submitted'].contains(status);
        }).toList();
        final history = allRemittances.where((item) {
          final status = (asMap(item)['status'] ?? '').toString().toLowerCase();
          return !['pending', 'due', 'submitted'].contains(status);
        }).toList();
        final outstanding = pending.fold<double>(
          0,
          (sum, item) => sum + asDouble(asMap(item)['amount']),
        );

        return Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            PaymentsHeader(
              outstanding: outstanding,
              pendingCount: pending.length,
              paidCount: int.tryParse(
                    '${summary['paid_count'] ?? history.length}',
                  ) ??
                  history.length,
            ),
            const SizedBox(height: 22),
            PaymentSegments(
              selected: segment,
              pendingCount: pending.length,
              onChanged: controller.setPaymentsSegment,
            ),
            const SizedBox(height: 16),
            if (segment == 0)
              RemittanceList(
                items: pending,
                emptyTitle: 'No payment due',
                emptyMessage:
                    'You are up to date. New remittances appear here.',
                onPay: _startingPayment
                    ? null
                    : (item) => _payRemittance(
                          item,
                          maximum: asDouble(hirePurchase['total_balance']),
                        ),
              )
            else if (segment == 1)
              RemittanceList(
                items: history,
                emptyTitle: 'No payment history',
                emptyMessage: 'Completed remittances will appear here.',
              )
            else
              TransactionHistory(items: transactions),
          ],
        );
      },
    );

    return AppPage(
      title: 'Payments',
      subtitle: 'Pay remittances and review every transaction.',
      actions: [
        HeaderAction(
          icon: Icons.refresh_rounded,
          tooltip: 'Refresh payments',
          onPressed: _refresh,
        ),
      ],
      onRefresh: _refresh,
      child: content,
    );
  }

  Future<void> _payRemittance(
    Map<String, dynamic> item, {
    double maximum = 0,
  }) async {
    final minimum = asDouble(item['minimum_amount'] ?? item['amount']);
    final amount = await showPaymentAmountSheet(
      context,
      title: 'Pay remittance',
      minimum: minimum,
      maximum: maximum > 0 ? maximum : null,
      description:
          'The generated amount is the minimum. You can increase it before continuing.',
    );
    if (amount != null) {
      await _createCheckout(
        purpose: 'remittance',
        amount: amount,
        transactionId: item['id'],
      );
    }
  }

  Future<void> _createCheckout({
    required String purpose,
    required double amount,
    dynamic transactionId,
  }) async {
    setState(() => _startingPayment = true);
    try {
      final response = await ApiClient.instance.post(
        'driver/payments/opay/checkout',
        {
          'purpose': purpose,
          'amount': amount,
          if (transactionId != null) 'transaction_id': transactionId,
        },
      );
      final data = asMap(response['data']);
      final checkoutUrl = data['checkout_url']?.toString() ?? '';
      final reference = data['reference']?.toString() ?? '';
      final checkoutAmount = asDouble(data['amount']);
      if (checkoutUrl.isEmpty || reference.isEmpty) {
        throw ApiException('OPay did not return a valid checkout session.');
      }
      if (!mounted) return;

      final result = await Navigator.of(context).push<OpayCheckoutResult>(
        MaterialPageRoute(
          fullscreenDialog: true,
          builder: (_) => OpayCheckoutPage(
            checkoutUrl: checkoutUrl,
            reference: reference,
            amount: checkoutAmount > 0 ? checkoutAmount : amount,
          ),
        ),
      );

      if (!mounted) return;
      if (result != null) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(
            content: Text(result.message),
            backgroundColor: result.paid ? AppColors.success : null,
          ),
        );
      }
      await _refresh();
    } on ApiException catch (exception) {
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(content: Text(exception.message)),
        );
      }
    } finally {
      if (mounted) setState(() => _startingPayment = false);
    }
  }
}
