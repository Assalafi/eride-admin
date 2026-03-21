<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ChargingRequest;
use App\Models\CompanyAccountTransaction;
use App\Models\Driver;
use App\Models\SystemSetting;
use App\Models\Transaction;
use App\Models\Vehicle;
use App\Services\BranchAccessService;
use App\Traits\HasDateFilters;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ChargingRequestController extends Controller
{
    use HasDateFilters;

    public function index(Request $request)
    {
        $user = Auth::user();
        
        // Get filter parameters
        $timeFilter = $request->get('time_filter', 'monthly');
        $startDate = $request->get('start_date');
        $endDate = $request->get('end_date');
        $status = $request->get('status');
        $driverId = $request->get('driver_id');

        // Get date range
        [$start, $end] = $this->getDateRange($timeFilter, $startDate, $endDate);

        $chargingRequests = ChargingRequest::with(['driver', 'vehicle', 'approver'])
            ->when(!$user->hasRole(['Super Admin', 'Accountant']), function ($query) use ($user) {
                BranchAccessService::applyBranchFilterThroughRelation($query, $user, 'driver');
            })
            // Charging Station Operator can only see approved/in_progress requests
            ->when(Auth::user()->hasRole('Charging Station Operator'), function ($query) {
                $query->whereIn('status', [ChargingRequest::STATUS_APPROVED, ChargingRequest::STATUS_IN_PROGRESS, ChargingRequest::STATUS_COMPLETED]);
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

        // Statistics with date filter
        $statsQuery = ChargingRequest::query()
            ->when(!$user->hasRole(['Super Admin', 'Accountant']), function ($query) use ($user) {
                BranchAccessService::applyBranchFilterThroughRelation($query, $user, 'driver');
            })
            // Charging Station Operator can only see approved/in_progress stats
            ->when(Auth::user()->hasRole('Charging Station Operator'), function ($query) {
                $query->whereIn('status', [ChargingRequest::STATUS_APPROVED, ChargingRequest::STATUS_IN_PROGRESS, ChargingRequest::STATUS_COMPLETED]);
            })
            ->when($start && $end, function ($query) use ($start, $end) {
                $query->whereBetween('created_at', [$start, $end]);
            });

        $stats = [
            'pending' => (clone $statsQuery)->where('status', 'pending')->count(),
            'approved' => (clone $statsQuery)->where('status', 'approved')->count(),
            'in_progress' => (clone $statsQuery)->where('status', 'in_progress')->count(),
            'completed' => (clone $statsQuery)->where('status', 'completed')->count(),
            'total_cost' => (clone $statsQuery)->where('status', 'completed')->sum('charging_cost'),
        ];

        // Get drivers for filter
        $drivers = Driver::when(!$user->hasRole(['Super Admin', 'Accountant']), function ($query) use ($user) {
            BranchAccessService::applyBranchFilter($query, $user);
        })->get();

        return view('admin.charging.index', compact(
            'chargingRequests', 
            'stats', 
            'status', 
            'drivers',
            'driverId',
            'timeFilter',
            'startDate',
            'endDate'
        ));
    }

    public function create()
    {
        // Charging Station Operator cannot create charging requests
        if (Auth::user()->hasRole('Charging Station Operator')) {
            abort(403, 'Unauthorized action.');
        }

        $user = Auth::user();
        
        $drivers = Driver::when(!$user->hasRole(['Super Admin', 'Accountant']), function ($query) use ($user) {
            BranchAccessService::applyBranchFilter($query, $user);
        })->get();

        $vehicles = Vehicle::when(!$user->hasRole(['Super Admin', 'Accountant']), function ($query) use ($user) {
            BranchAccessService::applyBranchFilter($query, $user);
        })->get();

        $defaultCost = SystemSetting::get('charging_per_session', 5000);

        return view('admin.charging.create', compact('drivers', 'vehicles', 'defaultCost'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'driver_id' => 'required|exists:drivers,id',
            'vehicle_id' => 'required|exists:vehicles,id',
            'location' => 'nullable|string|max:255',
            'battery_level_before' => 'nullable|numeric|min:0|max:100',
            'charging_cost' => 'required|numeric|min:0',
            'payment_receipt' => 'required|file|mimes:jpg,jpeg,png,pdf|max:5120',
            'notes' => 'nullable|string',
        ]);

        $data = [
            'driver_id' => $request->driver_id,
            'vehicle_id' => $request->vehicle_id,
            'location' => $request->location,
            'battery_level_before' => $request->battery_level_before,
            'charging_cost' => $request->charging_cost,
            'notes' => $request->notes,
            'status' => ChargingRequest::STATUS_PENDING,
        ];

        // Handle payment receipt upload
        if ($request->hasFile('payment_receipt')) {
            $data['payment_receipt'] = $request->file('payment_receipt')
                ->store('charging-receipts', 'public');
        }

        ChargingRequest::create($data);

        return redirect()->route('admin.charging.index')
            ->with('success', 'Charging request created successfully!');
    }

    public function show(ChargingRequest $chargingRequest)
    {
        $user = Auth::user();
        
        // Check if user can access this charging request based on branch
        if (!BranchAccessService::canAccessBranch($user, $chargingRequest->driver->branch_id)) {
            abort(403, 'You can only view charging requests from your branch.');
        }
        
        $chargingRequest->load(['driver', 'vehicle', 'approver']);
        
        return view('admin.charging.show', compact('chargingRequest'));
    }

    public function startCharging(ChargingRequest $chargingRequest)
    {
        $user = Auth::user();
        
        // Only Branch Manager and Super Admin can approve
        if (!$user->can('approve charging requests')) {
            abort(403, 'You do not have permission to approve charging requests.');
        }
        
        // Check branch access
        if (!BranchAccessService::canAccessBranch($user, $chargingRequest->driver->branch_id)) {
            abort(403, 'You can only approve charging requests from your branch.');
        }

        // Can only approve if status is pending
        if ($chargingRequest->status !== ChargingRequest::STATUS_PENDING) {
            return redirect()->back()->with('error', 'Can only approve pending requests.');
        }

        DB::transaction(function () use ($chargingRequest) {
            $chargingRequest->update([
                'status' => ChargingRequest::STATUS_APPROVED,
                'approved_by' => Auth::id(),
                'approved_at' => now(),
            ]);

            $driver = $chargingRequest->driver;

            // NOTE: Driver already paid directly (receipt uploaded) - Record transaction on approval
            
            // Create transaction record for tracking
            Transaction::create([
                'driver_id' => $driver->id,
                'type' => 'credit',
                'amount' => $chargingRequest->charging_cost,
                'reference' => 'CHARGE-' . $chargingRequest->id,
                'description' => 'Charging session fee - ' . $chargingRequest->vehicle->plate_number . ' (Paid directly)',
                'status' => 'successful',
                'processed_by' => Auth::id(),
                'processed_at' => now(),
            ]);

            // Record as INCOME in company account (driver paid directly to company)
            CompanyAccountTransaction::create([
                'branch_id' => $driver->branch_id,
                'type' => 'income',
                'amount' => $chargingRequest->charging_cost,
                'category' => 'charging',
                'reference' => 'CHARGE-' . $chargingRequest->id,
                'description' => 'EV Charging service income (Direct payment) - ' . $chargingRequest->vehicle->plate_number . 
                    ' (Driver: ' . $driver->full_name . ')',
                'transaction_date' => now()->toDateString(),
                'recorded_by' => Auth::id(),
            ]);
        });

        return redirect()->route('admin.charging.show', $chargingRequest)
            ->with('success', 'Charging request approved and payment recorded! Waiting for operator to start charging.');
    }

    public function operatorStart(ChargingRequest $chargingRequest)
    {
        $user = Auth::user();
        
        // Only Charging Station Operator can start charging
        if (!$user->can('complete charging requests')) {
            abort(403, 'You do not have permission to start charging.');
        }
        
        // Check branch access - Operator can only start charging for their branch
        if (!BranchAccessService::canAccessBranch($user, $chargingRequest->driver->branch_id)) {
            abort(403, 'You can only start charging for requests from your branch.');
        }

        // Can only start if status is approved
        if ($chargingRequest->status !== ChargingRequest::STATUS_APPROVED) {
            return redirect()->back()->with('error', 'Can only start charging for approved requests.');
        }

        $chargingRequest->update([
            'status' => ChargingRequest::STATUS_IN_PROGRESS,
            'charging_start' => now(),
        ]);

        return redirect()->route('admin.charging.show', $chargingRequest)
            ->with('success', 'Charging session started!');
    }

    public function completeCharging(Request $request, ChargingRequest $chargingRequest)
    {
        $user = Auth::user();
        
        // Both Charging Station Operator and managers can complete charging
        if (!$user->can('complete charging requests')) {
            abort(403, 'You do not have permission to complete charging requests.');
        }
        
        // Check branch access - Can only complete charging from their branch
        if (!BranchAccessService::canAccessBranch($user, $chargingRequest->driver->branch_id)) {
            abort(403, 'You can only complete charging requests from your branch.');
        }

        // Can only complete if status is in_progress
        if ($chargingRequest->status !== ChargingRequest::STATUS_IN_PROGRESS) {
            return redirect()->back()->with('error', 'Can only complete charging requests that are in progress.');
        }

        $request->validate([
            'battery_level_after' => 'required|numeric|min:0|max:100',
            'energy_consumed' => 'nullable|numeric|min:0',
        ]);

        // Update charging request with completion data
        $chargingRequest->update([
            'status' => ChargingRequest::STATUS_COMPLETED,
            'charging_end' => now(),
            'battery_level_after' => $request->battery_level_after,
            'energy_consumed' => $request->energy_consumed,
            'duration_minutes' => $chargingRequest->calculateDuration() ?? 
                $chargingRequest->charging_start->diffInMinutes(now()),
        ]);

        return redirect()->route('admin.charging.show', $chargingRequest)
            ->with('success', 'Charging session completed successfully!');
    }

    public function cancel(Request $request, ChargingRequest $chargingRequest)
    {
        $user = Auth::user();
        
        // Charging Station Operator cannot cancel requests
        if ($user->hasRole('Charging Station Operator')) {
            abort(403, 'You do not have permission to cancel charging requests.');
        }
        
        // Check branch access - Can only cancel requests from their branch
        if (!BranchAccessService::canAccessBranch($user, $chargingRequest->driver->branch_id)) {
            abort(403, 'You can only cancel charging requests from your branch.');
        }

        $chargingRequest->update([
            'status' => ChargingRequest::STATUS_CANCELLED,
            'notes' => ($chargingRequest->notes ?? '') . "\nCancelled by: " . $user->name . " at " . now(),
        ]);

        return redirect()->route('admin.charging.index')
            ->with('success', 'Charging request cancelled!');
    }
}
