<?php

/**
 * One-time script to update existing maintenance request parts with costs
 * Run this from the command line: php UPDATE_MAINTENANCE_COSTS.php
 */

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;
use App\Models\MaintenanceRequest;
use App\Models\Part;

echo "Starting maintenance costs update...\n\n";

// Get all maintenance request parts with 0 costs
$pivotRecords = DB::table('maintenance_request_parts')
    ->where('unit_cost', 0)
    ->orWhere('total_cost', 0)
    ->get();

echo "Found {$pivotRecords->count()} records to update\n";

$updated = 0;
$errors = 0;

foreach ($pivotRecords as $record) {
    try {
        $part = Part::find($record->part_id);
        
        if (!$part) {
            echo "⚠️  Part ID {$record->part_id} not found\n";
            $errors++;
            continue;
        }
        
        $unitCost = (float)($part->cost ?? 0);
        $totalCost = $unitCost * $record->quantity;
        
        DB::table('maintenance_request_parts')
            ->where('id', $record->id)
            ->update([
                'unit_cost' => $unitCost,
                'total_cost' => $totalCost,
            ]);
        
        echo "✅ Updated: Request #{$record->request_id} - Part: {$part->name} - Qty: {$record->quantity} - Unit: ₦{$unitCost} - Total: ₦{$totalCost}\n";
        $updated++;
        
    } catch (\Exception $e) {
        echo "❌ Error updating record ID {$record->id}: {$e->getMessage()}\n";
        $errors++;
    }
}

echo "\n";
echo "==========================================\n";
echo "Update Complete!\n";
echo "Updated: {$updated} records\n";
echo "Errors: {$errors} records\n";
echo "==========================================\n";
