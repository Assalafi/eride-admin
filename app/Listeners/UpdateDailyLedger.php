<?php

namespace App\Listeners;

use App\Events\PaymentApproved;
use App\Models\DailyLedger;
use Carbon\Carbon;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

class UpdateDailyLedger implements ShouldQueue
{
    use InteractsWithQueue;

    /**
     * Handle the event.
     */
    public function handle(PaymentApproved $event): void
    {
        $transaction = $event->transaction;
        
        // Only process daily remittance transactions
        if ($transaction->type !== 'daily_remittance') {
            return;
        }

        // Get today's ledger entry for this driver
        $ledger = DailyLedger::where('driver_id', $transaction->driver_id)
            ->where('date', Carbon::today())
            ->first();

        if ($ledger) {
            // Update the amount paid
            $ledger->amount_paid += $transaction->amount;
            $ledger->updateStatus();
            
            Log::info('Daily ledger updated', [
                'driver_id' => $transaction->driver_id,
                'amount' => $transaction->amount,
                'new_balance' => $ledger->balance
            ]);
        }
    }
}
