<?php

namespace App\Console\Commands;

use App\Models\DailyLedger;
use App\Models\VehicleAssignment;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class CreateDailyLedgers extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'ledgers:create-daily';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create daily ledger entries for all drivers with active vehicle assignments';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $today = Carbon::today();
        $requiredPayment = config('eride.daily_required_payment', 5000.00);

        // Get all active vehicle assignments
        $activeAssignments = VehicleAssignment::whereNull('returned_at')
            ->with('driver')
            ->get();

        $created = 0;

        foreach ($activeAssignments as $assignment) {
            // Check if ledger already exists for today
            $exists = DailyLedger::where('driver_id', $assignment->driver_id)
                ->where('date', $today)
                ->exists();

            if (!$exists) {
                DailyLedger::create([
                    'driver_id' => $assignment->driver_id,
                    'date' => $today,
                    'required_payment' => $requiredPayment,
                    'amount_paid' => 0,
                    'balance' => $requiredPayment,
                    'status' => 'due',
                ]);

                $created++;
            }
        }

        $this->info("Created {$created} daily ledger entries for " . $today->toDateString());
        Log::info("Daily ledgers created: {$created} entries");

        return Command::SUCCESS;
    }
}
