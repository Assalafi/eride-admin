import 'package:flutter/material.dart';

import '../../core/theme/app_colors.dart';
import '../../core/utils/formatters.dart';

/// Bottom sheet that collects a payment amount with validation.
Future<double?> showPaymentAmountSheet(
  BuildContext context, {
  required String title,
  required double minimum,
  required String description,
  double? maximum,
}) =>
    showModalBottomSheet<double>(
      context: context,
      isScrollControlled: true,
      showDragHandle: true,
      backgroundColor: Colors.white,
      builder: (_) => _PaymentAmountSheet(
        title: title,
        minimum: minimum,
        description: description,
        maximum: maximum,
      ),
    );

class _PaymentAmountSheet extends StatefulWidget {
  const _PaymentAmountSheet({
    required this.title,
    required this.minimum,
    required this.description,
    this.maximum,
  });

  final String title;
  final double minimum;
  final String description;
  final double? maximum;

  @override
  State<_PaymentAmountSheet> createState() => _PaymentAmountSheetState();
}

class _PaymentAmountSheetState extends State<_PaymentAmountSheet> {
  final _formKey = GlobalKey<FormState>();
  late final TextEditingController _controller = TextEditingController(
    text: widget.minimum.toStringAsFixed(
        widget.minimum.truncateToDouble() == widget.minimum ? 0 : 2),
  );

  @override
  void dispose() {
    _controller.dispose();
    super.dispose();
  }

  void _submit() {
    if (!(_formKey.currentState?.validate() ?? false)) return;
    Navigator.pop(
      context,
      double.parse(_controller.text.replaceAll(',', '')),
    );
  }

  @override
  Widget build(BuildContext context) => Padding(
        padding: EdgeInsets.fromLTRB(
          22,
          4,
          22,
          MediaQuery.viewInsetsOf(context).bottom + 24,
        ),
        child: SingleChildScrollView(
          child: Form(
            key: _formKey,
            child: Column(
              mainAxisSize: MainAxisSize.min,
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Row(children: [
                  Container(
                    width: 44,
                    height: 44,
                    decoration: BoxDecoration(
                      color: AppColors.mint,
                      borderRadius: BorderRadius.circular(14),
                    ),
                    child: const Icon(
                      Icons.payments_outlined,
                      color: AppColors.navy,
                    ),
                  ),
                  const SizedBox(width: 12),
                  Expanded(
                    child: Text(
                      widget.title,
                      style: const TextStyle(
                        color: AppColors.navy,
                        fontSize: 20,
                        fontWeight: FontWeight.w900,
                      ),
                    ),
                  ),
                ]),
                const SizedBox(height: 13),
                Text(
                  widget.description,
                  style: const TextStyle(
                    color: AppColors.muted,
                    fontSize: 12,
                    height: 1.5,
                  ),
                ),
                const SizedBox(height: 18),
                Container(
                  width: double.infinity,
                  padding: const EdgeInsets.all(13),
                  decoration: BoxDecoration(
                    color: const Color(0xFFFFF7E8),
                    borderRadius: BorderRadius.circular(14),
                  ),
                  child: Row(children: [
                    const Icon(
                      Icons.info_outline_rounded,
                      color: Color(0xFFD68A16),
                      size: 18,
                    ),
                    const SizedBox(width: 9),
                    Expanded(
                      child: Text(
                        'Minimum allowed: ${money(widget.minimum)}'
                        '${widget.maximum != null ? '  •  Maximum: ${money(widget.maximum!)}' : ''}',
                        style: const TextStyle(
                          color: Color(0xFF8B5A0E),
                          fontSize: 11,
                          fontWeight: FontWeight.w700,
                        ),
                      ),
                    ),
                  ]),
                ),
                const SizedBox(height: 15),
                TextFormField(
                  controller: _controller,
                  autofocus: true,
                  keyboardType:
                      const TextInputType.numberWithOptions(decimal: true),
                  decoration: const InputDecoration(
                    labelText: 'Amount to pay',
                    prefixText: '₦ ',
                    prefixIcon: Icon(Icons.payments_outlined),
                  ),
                  validator: (value) {
                    final amount =
                        double.tryParse((value ?? '').replaceAll(',', ''));
                    if (amount == null) return 'Enter a valid amount';
                    if (amount < widget.minimum) {
                      return 'Amount cannot be below ${money(widget.minimum)}';
                    }
                    if (widget.maximum != null && amount > widget.maximum!) {
                      return 'Amount cannot exceed ${money(widget.maximum!)}';
                    }
                    return null;
                  },
                  onFieldSubmitted: (_) => _submit(),
                ),
                const SizedBox(height: 16),
                SizedBox(
                  width: double.infinity,
                  child: FilledButton.icon(
                    onPressed: _submit,
                    icon: const Icon(Icons.lock_rounded, size: 17),
                    label: const Text('Continue securely with OPay'),
                  ),
                ),
              ],
            ),
          ),
        ),
      );
}
