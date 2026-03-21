<?php

namespace App\Http\Controllers\Admin;

use App\Events\MaintenanceCompleted;
use App\Http\Controllers\Controller;
use App\Models\CompanyAccountTransaction;
use App\Models\Driver;
use App\Models\MaintenanceRequest;
use App\Models\Part;
use App\Models\Transaction;
use App\Services\BranchAccessService;
use App\Traits\HasDateFilters;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class MaintenanceRequestController extends Controller
{
    use HasDateFilters;

    public function index(Request $request)
    {
        $user = auth()->user();
        
        // Get filter parameters
        $timeFilter = $request->get('time_filter', 'monthly');
        $startDate = $request->get('start_date');
        $endDate = $request->get('end_date');
        $status = $request->get('status');
        $driverId = $request->get('driver_id');

        // Get date range
        [$start, $end] = $this->getDateRange($timeFilter, $startDate, $endDate);

        $requests = MaintenanceRequest::with(['driver', 'mechanic', 'approver', 'parts'])
            ->when(!$user->hasRole('Super Admin'), function ($query) use ($user) {
                BranchAccessService::applyBranchFilterThroughRelation($query, $user, 'driver');
            })
            ->when($start && $end, function ($query) use ($start, $end) {
                $query->whereBetween('created_at', [$start, $end]);
            })
            ->when($status, function ($query) use ($status) {
                $query->where('status', $status);
            })
            ->when($driverId, function ($query) use ($driverId) {
                $query->where('driver_id', $driverId);
            })
            ->latest()
            ->paginate(20)
            ->withQueryString();

        // Get drivers for filter
        $drivers = Driver::when(!$user->hasRole('Super Admin'), function ($query) use ($user) {
            BranchAccessService::applyBranchFilter($query, $user);
        })->get();

        return view('admin.maintenance.index', compact(
            'requests',
            'drivers',
            'driverId',
            'status',
            'timeFilter',
            'startDate',
            'endDate'
        ));
    }

    public function create()
    {
        $user = auth()->user();
        
        $drivers = Driver::when(!$user->hasRole(['Super Admin', 'Accountant']), function ($query) use ($user) {
            BranchAccessService::applyBranchFilter($query, $user);
        })->get();

        $parts = Part::all();

        return view('admin.maintenance.create', compact('drivers', 'parts'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'driver_id' => 'required|exists:drivers,id',
            'parts' => 'required|array|min:1',
            'parts.*.part_id' => 'required|exists:parts,id',
            'parts.*.quantity' => 'required|integer|min:1',
        ]);

        $maintenanceRequest = MaintenanceRequest::create([
            'driver_id' => $request->driver_id,
            'mechanic_id' => auth()->id(),
            'status' => 'pending_manager_approval',
        ]);

        // Attach parts with costs
        foreach ($request->parts as $partData) {
            $part = \App\Models\Part::find($partData['part_id']);
            $quantity = (int)$partData['quantity'];
            $unitCost = (float)($part->cost ?? 0);
            $totalCost = $unitCost * $quantity;
            
            $maintenanceRequest->parts()->attach($partData['part_id'], [
                'quantity' => $quantity,
                'unit_cost' => $unitCost,
                'total_cost' => $totalCost,
            ]);
            
            \Log::info('Part attached to maintenance request', [
                'part_id' => $partData['part_id'],
                'part_name' => $part->name ?? 'Unknown',
                'quantity' => $quantity,
                'unit_cost' => $unitCost,
                'total_cost' => $totalCost,
            ]);
        }

        return redirect()->route('admin.maintenance.index')
            ->with('success', 'Maintenance request created successfully!');
    }

    public function show(MaintenanceRequest $maintenanceRequest)
    {
        $user = auth()->user();
        
        // Check if user can access this maintenance request
        if (!BranchAccessService::canAccessBranch($user, $maintenanceRequest->driver->branch_id)) {
            abort(403, 'You do not have permission to access this maintenance request.');
        }
        
        $maintenanceRequest->load(['driver.wallet', 'mechanic', 'approver', 'parts']);
        
        return view('admin.maintenance.show', compact('maintenanceRequest'));
    }

    public function approve(MaintenanceRequest $maintenanceRequest)
    {
        $user = auth()->user();
        
        // Check if user can approve this maintenance request
        if (!BranchAccessService::canAccessBranch($user, $maintenanceRequest->driver->branch_id)) {
            abort(403, 'You do not have permission to approve this maintenance request.');
        }
        
        if ($maintenanceRequest->status !== 'pending_manager_approval') {
            return back()->withErrors(['error' => 'This request cannot be approved at this stage.']);
        }

        $driver = $maintenanceRequest->driver;
        $totalCost = $maintenanceRequest->total_cost;

        // Check wallet balance
        if ($driver->wallet->balance < $totalCost) {
            return back()->withErrors([
                'error' => 'Insufficient wallet balance. Driver balance: ₦' . number_format($driver->wallet->balance, 2) . 
                          ', Required: ₦' . number_format($totalCost, 2)
            ]);
        }

        // Use database transaction to ensure atomicity
        DB::transaction(function () use ($maintenanceRequest, $driver, $totalCost, $user) {
            // Deduct from driver's wallet immediately upon approval
            $wallet = $driver->wallet;
            $wallet->balance -= $totalCost;
            $wallet->save();

            Log::info('Wallet deducted for maintenance approval', [
                'request_id' => $maintenanceRequest->id,
                'driver_id' => $driver->id,
                'total_cost' => $totalCost,
                'previous_balance' => $wallet->balance + $totalCost,
                'new_balance' => $wallet->balance
            ]);

            // Create maintenance debit transaction
            Transaction::create([
                'driver_id' => $driver->id,
                'approved_by' => $user->id,
                'type' => 'maintenance_debit',
                'amount' => $totalCost,
                'status' => 'successful',
                'balance_after' => $wallet->balance,
                'description' => "Maintenance request #{$maintenanceRequest->id} approved",
                'processed_by' => $user->id,
            ]);

            // Record as INCOME in company account
            $partsDescription = $maintenanceRequest->parts->map(function($part) {
                return $part->name . ' (x' . $part->pivot->quantity . ')';
            })->implode(', ');

            CompanyAccountTransaction::create([
                'branch_id' => $driver->branch_id,
                'type' => 'income',
                'amount' => $totalCost,
                'category' => 'maintenance_income',
                'reference' => 'MAINT-' . $maintenanceRequest->id,
                'description' => 'Vehicle maintenance payment from driver: ' . $driver->full_name . 
                    ' | Parts: ' . $partsDescription,
                'transaction_date' => now()->toDateString(),
                'recorded_by' => $user->id,
            ]);

            // Update maintenance request status
            $maintenanceRequest->update([
                'status' => 'pending_store_approval',
                'approved_by_id' => $user->id,
            ]);
        });

        return redirect()->route('admin.maintenance.index')
            ->with('success', "Maintenance request approved! ₦" . number_format($totalCost, 2) . " deducted from driver's wallet. Waiting for store confirmation.");
    }

    public function deny(MaintenanceRequest $maintenanceRequest)
    {
        $user = auth()->user();
        
        // Check if user can deny this maintenance request
        if (!BranchAccessService::canAccessBranch($user, $maintenanceRequest->driver->branch_id)) {
            abort(403, 'You do not have permission to deny this maintenance request.');
        }
        
        if ($maintenanceRequest->status !== 'pending_manager_approval') {
            return back()->withErrors(['error' => 'This request cannot be denied at this stage.']);
        }

        $maintenanceRequest->update([
            'status' => 'manager_denied',
            'approved_by_id' => auth()->id(),
        ]);

        return redirect()->route('admin.maintenance.index')
            ->with('success', 'Maintenance request denied.');
    }

    public function complete(MaintenanceRequest $maintenanceRequest)
    {
        $user = auth()->user();
        
        // Check if user can complete this maintenance request
        if (!BranchAccessService::canAccessBranch($user, $maintenanceRequest->driver->branch_id)) {
            abort(403, 'You do not have permission to complete this maintenance request.');
        }
        
        if ($maintenanceRequest->status !== 'pending_store_approval') {
            return back()->withErrors(['error' => 'This request cannot be completed at this stage.']);
        }

        $maintenanceRequest->update([
            'status' => 'completed',
        ]);

        // Fire event to process completion (deduct wallet, update inventory, create transaction)
        event(new MaintenanceCompleted($maintenanceRequest));

        return redirect()->route('admin.maintenance.index')
            ->with('success', 'Parts dispensed and maintenance completed successfully!');
    }
}
