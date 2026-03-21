<?php

namespace App\Listeners;

use App\Events\MaintenanceCompleted;
use App\Models\CompanyAccountTransaction;
use App\Models\PartStock;
use App\Models\Transaction;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ProcessMaintenanceCompletion implements ShouldQueue
{
    use InteractsWithQueue;

    /**
     * Handle the event.
     */
    public function handle(MaintenanceCompleted $event): void
    {
        $request = $event->maintenanceRequest;
        
        // Use database transaction to ensure data integrity
        DB::transaction(function () use ($request) {
            $driver = $request->driver;

            Log::info('Processing maintenance completion (store dispensing parts)', [
                'request_id' => $request->id,
                'driver_id' => $driver->id,
                'total_cost' => $request->total_cost,
            ]);

            // Update inventory - decrement part stock
            foreach ($request->parts as $part) {
                $quantity = $part->pivot->quantity;
                
                $stock = PartStock::where('part_id', $part->id)
                    ->where('branch_id', $driver->branch_id)
                    ->first();

                if ($stock) {
                    $oldQuantity = $stock->quantity;
                    $stock->quantity -= $quantity;
                    $stock->save();
                    
                    Log::info('Part stock updated', [
                        'part_id' => $part->id,
                        'part_name' => $part->name,
                        'quantity_dispensed' => $quantity,
                        'old_stock' => $oldQuantity,
                        'new_stock' => $stock->quantity
                    ]);
                }
            }

            // NOTE: Wallet deduction and transactions are already handled in MaintenanceRequestController::approve()
            // This listener only handles inventory management when store dispenses parts

            Log::info('Maintenance completion processed - parts dispensed', [
                'request_id' => $request->id,
                'driver_id' => $driver->id,
            ]);
        });
    }
}
