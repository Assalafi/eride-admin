<?php

namespace App\Console\Commands;

use App\Models\Driver;
use App\Models\HirePurchaseContract;
use App\Models\HirePurchasePayment;
use App\Models\SystemSetting;
use App\Models\Transaction;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class GenerateDailyRemittances extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'remittances:generate-daily {--date= : The date to generate remittances for (defaults to today)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate daily remittance transactions for all drivers with active vehicle assignments';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $date = $this->option('date') ?? now()->toDateString();
        $selectedDate = Carbon::parse($date);

        $this->info("Generating daily remittances for {$date}...");

        Log::info('Cron: Daily remittance generation started', [
            'date' => $date,
            'timestamp' => now(),
        ]);

        DB::beginTransaction();

        try {
            // Get default daily remittance amount from settings
            $defaultAmount = SystemSetting::get('daily_remittance_amount', 5000.00);

            // Get all drivers with valid user accounts and active vehicle assignments
            $drivers = Driver::with(['user', 'branch', 'vehicleAssignments'])
                ->whereHas('user')
                ->whereHas('vehicleAssignments', function ($query) {
                    $query->active();
                })
                ->get();

            $generated = 0;
            $skippedDuplicate = 0;
            $skippedNoCharging = 0;
            $errors = [];

            foreach ($drivers as $driver) {
                try {
                    // Check if remittance already exists for this date
                    $existingRemittance = Transaction::where('driver_id', $driver->id)
                        ->where('type', Transaction::TYPE_DAILY_REMITTANCE)
                        ->whereDate('created_at', $date)
                        ->first();

                    if ($existingRemittance) {
                        $skippedDuplicate++;
                        continue;
                    }

                    // Determine amount and hire purchase details
                    $amount = $defaultAmount;
                    $isHirePurchase = false;
                    $hirePurchaseContractId = null;
                    $description = 'Daily remittance - ' . $selectedDate->format('F d, Y') . ' (Auto-generated)';

                    // Check if driver is hire purchase and has active contract
                    if ($driver->is_hire_purchase) {
                        $activeContract = HirePurchaseContract::where('driver_id', $driver->id)
                            ->where('status', 'active')
                            ->first();

                        if ($activeContract) {
                            $isHirePurchase = true;
                            $hirePurchaseContractId = $activeContract->id;
                            $amount = $activeContract->daily_payment;
                            $description = 'Hire Purchase Payment - ' . $selectedDate->format('F d, Y') . ' (Contract: ' . $activeContract->contract_number . ')';
                        }
                    }

                    // Create transaction with custom timestamps
                    $transaction = new Transaction();
                    $transaction->driver_id = $driver->id;
                    $transaction->type = Transaction::TYPE_DAILY_REMITTANCE;
                    $transaction->amount = $amount;
                    $transaction->reference = ($isHirePurchase ? 'HP-' : 'REMIT-') . strtoupper(uniqid()) . '-' . $driver->id;
                    $transaction->description = $description;
                    $transaction->status = Transaction::STATUS_PENDING;
                    $transaction->is_hire_purchase_payment = $isHirePurchase;
                    $transaction->hire_purchase_contract_id = $hirePurchaseContractId;
                    $transaction->timestamps = false;
                    $transaction->created_at = $selectedDate->copy()->setTime(11, 0, 0);
                    $transaction->updated_at = $selectedDate->copy()->setTime(11, 0, 0);
                    $transaction->save();

                    // If hire purchase, also create/update the payment schedule entry
                    if ($isHirePurchase && $hirePurchaseContractId) {
                        $hirePurchasePayment = HirePurchasePayment::where('hire_purchase_contract_id', $hirePurchaseContractId)
                            ->whereDate('due_date', $date)
                            ->first();

                        if ($hirePurchasePayment) {
                            $hirePurchasePayment->update([
                                'transaction_id' => $transaction->id,
                            ]);
                        }
                    }

                    $generated++;

                    Log::info('Cron: Daily remittance generated', [
                        'driver_id' => $driver->id,
                        'driver_name' => $driver->first_name . ' ' . $driver->last_name,
                        'amount' => $amount,
                        'is_hire_purchase' => $isHirePurchase,
                        'transaction_id' => $transaction->id,
                    ]);

                } catch (\Exception $e) {
                    $errors[] = [
                        'driver_id' => $driver->id,
                        'error' => $e->getMessage(),
                    ];
                    Log::error('Cron: Failed to generate remittance for driver', [
                        'driver_id' => $driver->id,
                        'error' => $e->getMessage(),
                    ]);
                }
            }

            DB::commit();

            $this->info("Generated: {$generated}");
            $this->info("Skipped (duplicate): {$skippedDuplicate}");
            if (count($errors) > 0) {
                $this->warn("Errors: " . count($errors));
            }

            Log::info('Cron: Daily remittance generation completed', [
                'date' => $date,
                'generated' => $generated,
                'skipped_duplicate' => $skippedDuplicate,
                'errors' => count($errors),
            ]);

            return Command::SUCCESS;

        } catch (\Exception $e) {
            DB::rollBack();

            $this->error("Failed to generate remittances: " . $e->getMessage());

            Log::error('Cron: Daily remittance generation failed', [
                'date' => $date,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return Command::FAILURE;
        }
    }
}
