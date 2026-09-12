<?php

namespace App\Services;

use App\Models\CompanyAccountTransaction;
use App\Models\DailyLedger;
use App\Models\HirePurchaseContract;
use App\Models\HirePurchasePayment;
use App\Models\Transaction;
use Carbon\Carbon;

class RemittanceSettlementService
{
    public function settle(
        Transaction $transaction,
        float $amount,
        float $minimumAmount,
        string $gatewayReference,
    ): Transaction {
        $transaction = Transaction::whereKey($transaction->id)->lockForUpdate()->firstOrFail();

        if ($transaction->status === Transaction::STATUS_SUCCESSFUL) {
            return $transaction;
        }

        $transaction->update([
            'amount' => $amount,
            'status' => Transaction::STATUS_SUCCESSFUL,
            'paid_at' => now(),
            'processed_at' => now(),
        ]);

        $this->updateDailyLedger($transaction, $amount, $minimumAmount);
        $this->recordCompanyIncome($transaction, $amount, $gatewayReference);

        if ($transaction->is_hire_purchase_payment && $transaction->hire_purchase_contract_id) {
            $this->updateHirePurchase($transaction, $amount);
        }

        return $transaction->fresh();
    }

    private function updateDailyLedger(Transaction $transaction, float $amount, float $minimumAmount): void
    {
        $paymentDate = $transaction->created_at->toDateString();
        $ledger = DailyLedger::where('driver_id', $transaction->driver_id)
            ->whereDate('date', $paymentDate)
            ->lockForUpdate()
            ->first();

        if (!$ledger) {
            $ledger = DailyLedger::create([
                'driver_id' => $transaction->driver_id,
                'date' => $paymentDate,
                'required_payment' => $minimumAmount,
                'amount_paid' => 0,
                'balance' => $minimumAmount,
                'status' => 'due',
            ]);
        }

        $ledger->amount_paid = (float) $ledger->amount_paid + $amount;
        $ledger->updateStatus();
    }

    private function recordCompanyIncome(Transaction $transaction, float $amount, string $gatewayReference): void
    {
        CompanyAccountTransaction::firstOrCreate(
            ['reference' => 'OPAY-' . $gatewayReference],
            [
                'branch_id' => $transaction->driver->branch_id,
                'type' => CompanyAccountTransaction::TYPE_INCOME,
                'amount' => $amount,
                'category' => $transaction->is_hire_purchase_payment
                    ? 'hire_purchase_payment'
                    : CompanyAccountTransaction::CATEGORY_DAILY_REMITTANCE,
                'description' => ($transaction->is_hire_purchase_payment
                    ? 'Hire purchase payment'
                    : 'Daily remittance') . ' from ' . $transaction->driver->full_name . ' via OPay',
                'transaction_date' => now()->toDateString(),
                'recorded_by' => $transaction->driver->user_id,
            ],
        );
    }

    private function updateHirePurchase(Transaction $transaction, float $amount): void
    {
        $contract = HirePurchaseContract::whereKey($transaction->hire_purchase_contract_id)
            ->lockForUpdate()
            ->first();

        if (!$contract || $contract->status !== HirePurchaseContract::STATUS_ACTIVE) {
            return;
        }

        $paymentDate = $transaction->created_at->toDateString();
        $balanceBefore = (float) $contract->total_balance;
        $balanceAfter = max(0, $balanceBefore - $amount);

        $payment = HirePurchasePayment::where('hire_purchase_contract_id', $contract->id)
            ->where(function ($query) use ($transaction, $paymentDate) {
                $query->where('transaction_id', $transaction->id)
                    ->orWhereDate('due_date', $paymentDate);
            })
            ->lockForUpdate()
            ->first();

        $paymentData = [
            'transaction_id' => $transaction->id,
            'amount_paid' => $amount,
            'total_amount' => $amount + (float) ($payment?->penalty_amount ?? 0),
            'balance_before' => $balanceBefore,
            'balance_after' => $balanceAfter,
            'paid_date' => now()->toDateString(),
            'status' => HirePurchasePayment::STATUS_PAID,
            'payment_method' => 'opay',
            'processed_at' => now(),
        ];

        if ($payment) {
            $payment->update($paymentData);
        } else {
            HirePurchasePayment::create(array_merge($paymentData, [
                'hire_purchase_contract_id' => $contract->id,
                'driver_id' => $transaction->driver_id,
                'payment_number' => $contract->payments_made + 1,
                'expected_amount' => $contract->getCurrentPaymentAmount(),
                'penalty_amount' => 0,
                'due_date' => $paymentDate,
            ]));
        }

        $newTotalPaid = (float) $contract->total_paid + $amount;
        $paymentsMade = $contract->payments_made + 1;
        $paymentAmount = max(0.01, $contract->getCurrentPaymentAmount());
        $paymentsRemaining = (int) ceil($balanceAfter / $paymentAmount);

        $updates = [
            'total_paid' => $newTotalPaid,
            'total_balance' => $balanceAfter,
            'payments_made' => $paymentsMade,
            'payments_remaining' => $paymentsRemaining,
            'last_payment_date' => now()->toDateString(),
        ];

        if ($balanceAfter <= 0) {
            $updates['status'] = HirePurchaseContract::STATUS_COMPLETED;
            $updates['actual_end_date'] = now()->toDateString();
            $transaction->driver->update(['hire_purchase_status' => 'completed']);
        } else {
            $nextDue = match ($contract->payment_frequency) {
                HirePurchaseContract::PAYMENT_FREQUENCY_WEEKLY => Carbon::parse($paymentDate)->addWeek(),
                HirePurchaseContract::PAYMENT_FREQUENCY_MONTHLY => Carbon::parse($paymentDate)->addMonth(),
                default => Carbon::parse($paymentDate)->addDay(),
            };

            while ($nextDue->isSunday()) {
                $nextDue->addDay();
            }

            $updates['next_payment_due'] = $nextDue->toDateString();
        }

        $contract->update($updates);
    }
}
