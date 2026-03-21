<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\Driver;
use App\Models\HirePurchaseContract;
use App\Models\HirePurchasePayment;
use App\Models\Transaction;
use App\Models\Vehicle;
use App\Services\BranchAccessService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class HirePurchaseController extends Controller
{
    /**
     * Display hire purchase dashboard/analytics
     */
    public function index(Request $request)
    {
        $user = auth()->user();
        
        // Build query with filters
        $query = HirePurchaseContract::with(['driver', 'vehicle', 'branch'])
            ->when(!$user->hasRole(['Super Admin', 'Accountant']), function ($q) use ($user) {
                BranchAccessService::applyBranchFilter($q, $user);
            });

        // Apply filters
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('branch_id') && ($user->hasRole('Super Admin') || $user->hasRole('Accountant'))) {
            $query->where('branch_id', $request->branch_id);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->whereHas('driver', function ($q) use ($search) {
                $q->where('first_name', 'like', "%{$search}%")
                  ->orWhere('last_name', 'like', "%{$search}%")
                  ->orWhere('phone_number', 'like', "%{$search}%");
            })->orWhereHas('vehicle', function ($q) use ($search) {
                $q->where('plate_number', 'like', "%{$search}%");
            })->orWhere('contract_number', 'like', "%{$search}%");
        }

        // Date range filter
        if ($request->filled('start_date')) {
            $query->whereDate('start_date', '>=', $request->start_date);
        }
        if ($request->filled('end_date')) {
            $query->whereDate('start_date', '<=', $request->end_date);
        }

        // Get contracts with pagination
        $contracts = $query->latest()->paginate(20)->withQueryString();

        // Get summary statistics
        $statsQuery = HirePurchaseContract::query()
            ->when(!$user->hasRole(['Super Admin', 'Accountant']), function ($q) use ($user) {
                BranchAccessService::applyBranchFilter($q, $user);
            });

        $summary = [
            'total_contracts' => (clone $statsQuery)->count(),
            'active_contracts' => (clone $statsQuery)->where('status', 'active')->count(),
            'completed_contracts' => (clone $statsQuery)->where('status', 'completed')->count(),
            'defaulted_contracts' => (clone $statsQuery)->where('status', 'defaulted')->count(),
            'total_vehicle_value' => (clone $statsQuery)->sum('vehicle_price'),
            'total_amount_due' => (clone $statsQuery)->where('status', 'active')->sum('total_amount'),
            'total_collected' => (clone $statsQuery)->sum('total_paid'),
            'total_outstanding' => (clone $statsQuery)->where('status', 'active')->sum('total_balance'),
            'overdue_contracts' => (clone $statsQuery)->where('status', 'active')
                ->where('next_payment_due', '<', now()->toDateString())->count(),
            'total_penalties' => (clone $statsQuery)->sum('total_penalties'),
        ];

        // Get payments due today
        $paymentsDueToday = HirePurchasePayment::with(['contract.driver', 'contract.vehicle'])
            ->whereDate('due_date', today())
            ->whereIn('status', ['pending', 'overdue'])
            ->when(!$user->hasRole(['Super Admin', 'Accountant']), function ($q) use ($user) {
                $q->whereHas('contract', function ($q2) use ($user) {
                    BranchAccessService::applyBranchFilter($q2, $user);
                });
            })
            ->get();

        // Get overdue payments
        $overduePayments = HirePurchasePayment::with(['contract.driver', 'contract.vehicle'])
            ->where('due_date', '<', today())
            ->whereIn('status', ['pending', 'overdue'])
            ->when(!$user->hasRole(['Super Admin', 'Accountant']), function ($q) use ($user) {
                $q->whereHas('contract', function ($q2) use ($user) {
                    BranchAccessService::applyBranchFilter($q2, $user);
                });
            })
            ->orderBy('due_date')
            ->limit(20)
            ->get();

        // Get branches for filter (show for multi-branch managers too)
        $branches = BranchAccessService::getAvailableBranchesForUser($user);

        return view('admin.hire-purchase.index', compact(
            'contracts',
            'summary',
            'paymentsDueToday',
            'overduePayments',
            'branches'
        ));
    }

    /**
     * Show form to create new hire purchase contract
     */
    public function create()
    {
        $user = auth()->user();
        
        // Get drivers who are marked for hire purchase but don't have active contract
        $drivers = Driver::where('is_hire_purchase', true)
            ->whereDoesntHave('hirePurchaseContracts', function ($q) {
                $q->whereIn('status', ['pending', 'active']);
            })
            ->when(!$user->hasRole(['Super Admin', 'Accountant']), function ($q) use ($user) {
                BranchAccessService::applyBranchFilter($q, $user);
            })
            ->get();

        // Get available vehicles
        $vehicles = Vehicle::whereDoesntHave('hirePurchaseContracts', function ($q) {
                $q->whereIn('status', ['pending', 'active']);
            })
            ->when(!$user->hasRole(['Super Admin', 'Accountant']), function ($q) use ($user) {
                BranchAccessService::applyBranchFilter($q, $user);
            })
            ->get();

        $branches = BranchAccessService::getAvailableBranchesForUser($user);

        return view('admin.hire-purchase.create', compact('drivers', 'vehicles', 'branches'));
    }

    /**
     * Store new hire purchase contract
     */
    public function store(Request $request)
    {
        $request->validate([
            'driver_id' => 'required|exists:drivers,id',
            'vehicle_id' => 'required|exists:vehicles,id',
            'vehicle_price' => 'required|numeric|min:1',
            'down_payment' => 'nullable|numeric|min:0',
            'daily_payment' => 'required|numeric|min:1',
            'total_payment_days' => 'required|integer|min:1',
            'start_date' => 'required|date',
            'grace_period_days' => 'nullable|integer|min:0',
            'late_fee_percentage' => 'nullable|numeric|min:0|max:100',
            'late_fee_fixed' => 'nullable|numeric|min:0',
            'notes' => 'nullable|string|max:1000',
        ]);

        $user = auth()->user();
        $driver = Driver::findOrFail($request->driver_id);
        $vehicle = Vehicle::findOrFail($request->vehicle_id);

        // Validate branch access
        if (!BranchAccessService::canAccessBranch($user, $driver->branch_id)) {
            abort(403, 'You do not have permission to create contract for this driver.');
        }

        DB::beginTransaction();
        try {
            $downPayment = floatval($request->down_payment ?? 0);
            $totalAmount = floatval($request->vehicle_price) - $downPayment;
            $startDate = Carbon::parse($request->start_date);
            $expectedEndDate = $startDate->copy()->addDays(intval($request->total_payment_days));

            $contract = HirePurchaseContract::create([
                'driver_id' => $driver->id,
                'vehicle_id' => $vehicle->id,
                'branch_id' => $driver->branch_id,
                'contract_number' => HirePurchaseContract::generateContractNumber(),
                'vehicle_price' => floatval($request->vehicle_price),
                'down_payment' => $downPayment,
                'total_amount' => $totalAmount,
                'daily_payment' => floatval($request->daily_payment),
                'payment_frequency' => 'daily',
                'total_payment_days' => intval($request->total_payment_days),
                'grace_period_days' => intval($request->grace_period_days ?? 0),
                'total_paid' => 0,
                'total_balance' => $totalAmount,
                'payments_made' => 0,
                'payments_remaining' => intval($request->total_payment_days),
                'late_fee_percentage' => floatval($request->late_fee_percentage ?? 0),
                'late_fee_fixed' => floatval($request->late_fee_fixed ?? 0),
                'start_date' => $startDate,
                'expected_end_date' => $expectedEndDate,
                'next_payment_due' => $startDate,
                'status' => HirePurchaseContract::STATUS_ACTIVE,
                'notes' => $request->notes,
                'created_by' => $user->id,
                'approved_by' => $user->id,
                'approved_at' => now(),
            ]);

            // Update driver status
            $driver->update([
                'is_hire_purchase' => true,
                'hire_purchase_status' => 'active',
            ]);

            // Generate payment schedule
            $this->generatePaymentSchedule($contract);

            DB::commit();

            Log::info('Hire purchase contract created', [
                'contract_id' => $contract->id,
                'contract_number' => $contract->contract_number,
                'driver_id' => $driver->id,
                'vehicle_id' => $vehicle->id,
                'created_by' => $user->id,
            ]);

            return redirect()->route('admin.hire-purchase.show', $contract)
                ->with('success', 'Hire purchase contract created successfully!');

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to create hire purchase contract', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return back()->withInput()
                ->with('error', 'Failed to create contract. Please try again.');
        }
    }

    /**
     * Show contract details
     */
    public function show(HirePurchaseContract $hirePurchase)
    {
        $user = auth()->user();

        // Check access
        if (!BranchAccessService::canAccessBranch($user, $hirePurchase->branch_id)) {
            abort(403, 'You do not have permission to view this contract.');
        }

        $hirePurchase->load(['driver.user', 'vehicle', 'branch', 'payments', 'creator', 'approver']);

        // Get payment statistics
        $paymentStats = [
            'total_payments' => $hirePurchase->payments->count(),
            'paid_payments' => $hirePurchase->payments->where('status', 'paid')->count(),
            'pending_payments' => $hirePurchase->payments->where('status', 'pending')->count(),
            'overdue_payments' => $hirePurchase->payments->where('status', 'overdue')->count(),
            'total_penalties_paid' => $hirePurchase->payments->sum('penalty_amount'),
        ];

        // Get recent transactions
        $transactions = Transaction::where('hire_purchase_contract_id', $hirePurchase->id)
            ->with('driver')
            ->latest()
            ->limit(20)
            ->get();

        // Calculate days analysis
        $daysAnalysis = [
            'total_days' => $hirePurchase->total_payment_days,
            'days_elapsed' => $hirePurchase->start_date->diffInDays(now()),
            'days_remaining' => max(0, $hirePurchase->expected_end_date->diffInDays(now(), false)),
            'on_track' => $hirePurchase->payments_made >= $hirePurchase->start_date->diffInDays(now()),
        ];

        return view('admin.hire-purchase.show', compact(
            'hirePurchase',
            'paymentStats',
            'transactions',
            'daysAnalysis'
        ));
    }

    /**
     * Show form to edit hire purchase contract
     */
    public function edit(HirePurchaseContract $hirePurchase)
    {
        $user = auth()->user();

        // Check access
        if (!BranchAccessService::canAccessBranch($user, $hirePurchase->branch_id)) {
            abort(403, 'You do not have permission to edit this contract.');
        }

        $hirePurchase->load(['driver', 'vehicle', 'branch']);

        // Get all drivers for potential reassignment (only those without active contracts or current driver)
        $drivers = Driver::where('is_hire_purchase', true)
            ->where(function ($q) use ($hirePurchase) {
                $q->whereDoesntHave('hirePurchaseContracts', function ($q2) use ($hirePurchase) {
                    $q2->whereIn('status', ['pending', 'active'])
                       ->where('id', '!=', $hirePurchase->id);
                })
                ->orWhere('id', $hirePurchase->driver_id);
            })
            ->when(!$user->hasRole(['Super Admin', 'Accountant']), function ($q) use ($user) {
                BranchAccessService::applyBranchFilter($q, $user);
            })
            ->get();

        // Get available vehicles (without active contracts or current vehicle)
        $vehicles = Vehicle::where(function ($q) use ($hirePurchase) {
                $q->whereDoesntHave('hirePurchaseContracts', function ($q2) use ($hirePurchase) {
                    $q2->whereIn('status', ['pending', 'active'])
                       ->where('id', '!=', $hirePurchase->id);
                })
                ->orWhere('id', $hirePurchase->vehicle_id);
            })
            ->when(!$user->hasRole(['Super Admin', 'Accountant']), function ($q) use ($user) {
                BranchAccessService::applyBranchFilter($q, $user);
            })
            ->get();

        $branches = BranchAccessService::getAvailableBranchesForUser($user);

        return view('admin.hire-purchase.edit', compact('hirePurchase', 'drivers', 'vehicles', 'branches'));
    }

    /**
     * Update hire purchase contract
     */
    public function update(Request $request, HirePurchaseContract $hirePurchase)
    {
        $user = auth()->user();

        // Check access
        if (!BranchAccessService::canAccessBranch($user, $hirePurchase->branch_id)) {
            abort(403, 'You do not have permission to update this contract.');
        }

        $request->validate([
            'vehicle_price' => 'required|numeric|min:1',
            'down_payment' => 'nullable|numeric|min:0',
            'daily_payment' => 'required|numeric|min:1',
            'total_payment_days' => 'required|integer|min:1',
            'start_date' => 'required|date',
            'grace_period_days' => 'nullable|integer|min:0',
            'late_fee_percentage' => 'nullable|numeric|min:0|max:100',
            'late_fee_fixed' => 'nullable|numeric|min:0',
            'notes' => 'nullable|string|max:1000',
            'status' => 'required|in:active,suspended,terminated,completed,defaulted',
        ]);

        DB::beginTransaction();
        try {
            $downPayment = floatval($request->down_payment ?? 0);
            $vehiclePrice = floatval($request->vehicle_price);
            $totalAmount = $vehiclePrice - $downPayment;
            $startDate = Carbon::parse($request->start_date);
            $totalPaymentDays = intval($request->total_payment_days);
            $expectedEndDate = $startDate->copy()->addDays($totalPaymentDays);

            // Calculate new balance based on payments already made
            $newBalance = max(0, $totalAmount - $hirePurchase->total_paid);
            $paymentsRemaining = max(0, $totalPaymentDays - $hirePurchase->payments_made);

            $oldStatus = $hirePurchase->status;

            $hirePurchase->update([
                'vehicle_price' => $vehiclePrice,
                'down_payment' => $downPayment,
                'total_amount' => $totalAmount,
                'daily_payment' => floatval($request->daily_payment),
                'total_payment_days' => $totalPaymentDays,
                'grace_period_days' => intval($request->grace_period_days ?? 0),
                'total_balance' => $newBalance,
                'payments_remaining' => $paymentsRemaining,
                'late_fee_percentage' => floatval($request->late_fee_percentage ?? 0),
                'late_fee_fixed' => floatval($request->late_fee_fixed ?? 0),
                'start_date' => $startDate,
                'expected_end_date' => $expectedEndDate,
                'status' => $request->status,
                'notes' => $request->notes,
                'updated_by' => $user->id,
            ]);

            // Update driver hire purchase status if contract status changed
            if ($oldStatus !== $request->status) {
                $driverStatus = match($request->status) {
                    'active' => 'active',
                    'completed' => 'completed',
                    'defaulted' => 'defaulted',
                    'terminated' => 'terminated',
                    'suspended' => 'suspended',
                    default => 'active',
                };
                
                $hirePurchase->driver->update([
                    'hire_purchase_status' => $driverStatus,
                ]);
            }

            // Regenerate payment schedule if key parameters changed
            if ($request->has('regenerate_schedule') && $request->regenerate_schedule) {
                // Delete unpaid payments
                $hirePurchase->payments()->whereIn('status', ['pending', 'overdue'])->delete();
                
                // Regenerate from current position
                $this->regeneratePaymentSchedule($hirePurchase);
            }

            DB::commit();

            Log::info('Hire purchase contract updated', [
                'contract_id' => $hirePurchase->id,
                'contract_number' => $hirePurchase->contract_number,
                'updated_by' => $user->id,
            ]);

            return redirect()->route('admin.hire-purchase.show', $hirePurchase)
                ->with('success', 'Hire purchase contract updated successfully!');

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to update hire purchase contract', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return back()->withInput()
                ->with('error', 'Failed to update contract. Please try again.');
        }
    }

    /**
     * Regenerate payment schedule for remaining balance
     */
    private function regeneratePaymentSchedule(HirePurchaseContract $contract)
    {
        $currentDate = Carbon::today();
        $balanceRemaining = $contract->total_balance;
        $paymentNumber = $contract->payments_made + 1;
        $daysToGenerate = $contract->payments_remaining;

        for ($i = 0; $i < $daysToGenerate; $i++) {
            $expectedAmount = min($contract->daily_payment, $balanceRemaining);
            $balanceAfterPayment = $balanceRemaining - $expectedAmount;

            HirePurchasePayment::create([
                'hire_purchase_contract_id' => $contract->id,
                'driver_id' => $contract->driver_id,
                'payment_number' => $paymentNumber + $i,
                'expected_amount' => $expectedAmount,
                'balance_before' => $balanceRemaining,
                'balance_after' => max(0, $balanceAfterPayment),
                'due_date' => $currentDate->copy()->addDays($i),
                'status' => HirePurchasePayment::STATUS_PENDING,
            ]);

            $balanceRemaining = $balanceAfterPayment;
            if ($balanceRemaining <= 0) break;
        }

        // Update next payment due
        $contract->update([
            'next_payment_due' => $currentDate,
        ]);
    }

    /**
     * Show payment calendar for a contract
     */
    public function calendar(HirePurchaseContract $hirePurchase)
    {
        $user = auth()->user();

        // Check access
        if (!BranchAccessService::canAccessBranch($user, $hirePurchase->branch_id)) {
            abort(403, 'You do not have permission to view this contract.');
        }

        $hirePurchase->load(['driver', 'vehicle', 'branch', 'payments']);

        return view('admin.hire-purchase.calendar', [
            'contract' => $hirePurchase,
        ]);
    }

    /**
     * Record a payment for hire purchase
     */
    public function recordPayment(Request $request, HirePurchaseContract $hirePurchase)
    {
        $request->validate([
            'amount' => 'required|numeric|min:1',
            'payment_date' => 'required|date',
            'payment_method' => 'required|string',
            'notes' => 'nullable|string|max:500',
        ]);

        $user = auth()->user();

        // Check access
        if (!BranchAccessService::canAccessBranch($user, $hirePurchase->branch_id)) {
            abort(403, 'You do not have permission to record payment for this contract.');
        }

        if ($hirePurchase->status !== HirePurchaseContract::STATUS_ACTIVE) {
            return back()->with('error', 'Cannot record payment for inactive contract.');
        }

        DB::beginTransaction();
        try {
            $amount = $request->amount;
            $paymentDate = Carbon::parse($request->payment_date);

            // Find the next pending payment
            $pendingPayment = $hirePurchase->payments()
                ->whereIn('status', ['pending', 'overdue'])
                ->orderBy('due_date')
                ->first();

            // Calculate penalty if late
            $penalty = 0;
            $daysLate = 0;
            if ($pendingPayment && $paymentDate->gt($pendingPayment->due_date)) {
                $daysLate = $pendingPayment->due_date->diffInDays($paymentDate);
                if ($daysLate > $hirePurchase->grace_period_days) {
                    if ($hirePurchase->late_fee_percentage > 0) {
                        $penalty = ($hirePurchase->daily_payment * $hirePurchase->late_fee_percentage) / 100;
                    }
                    $penalty += $hirePurchase->late_fee_fixed;
                }
            }

            // Create transaction record
            $transaction = Transaction::create([
                'driver_id' => $hirePurchase->driver_id,
                'type' => Transaction::TYPE_DAILY_REMITTANCE,
                'amount' => $amount,
                'reference' => 'HP-' . strtoupper(uniqid()),
                'description' => "Hire Purchase Payment - {$hirePurchase->contract_number}",
                'status' => Transaction::STATUS_SUCCESSFUL,
                'paid_at' => $paymentDate,
                'processed_by' => $user->id,
                'processed_at' => now(),
                'is_hire_purchase_payment' => true,
                'hire_purchase_contract_id' => $hirePurchase->id,
            ]);

            // Update payment record if exists
            if ($pendingPayment) {
                $pendingPayment->update([
                    'transaction_id' => $transaction->id,
                    'amount_paid' => $amount,
                    'penalty_amount' => $penalty,
                    'total_amount' => $amount + $penalty,
                    'balance_before' => $hirePurchase->total_balance,
                    'balance_after' => $hirePurchase->total_balance - $amount,
                    'paid_date' => $paymentDate,
                    'days_late' => $daysLate,
                    'status' => HirePurchasePayment::STATUS_PAID,
                    'payment_method' => $request->payment_method,
                    'notes' => $request->notes,
                    'processed_by' => $user->id,
                    'processed_at' => now(),
                ]);

                if ($daysLate > $hirePurchase->grace_period_days) {
                    $hirePurchase->increment('late_payments');
                }
            }

            // Update contract
            $newTotalPaid = $hirePurchase->total_paid + $amount;
            $newBalance = $hirePurchase->total_balance - $amount;
            $newPaymentsMade = $hirePurchase->payments_made + 1;

            $updateData = [
                'total_paid' => $newTotalPaid,
                'total_balance' => max(0, $newBalance),
                'payments_made' => $newPaymentsMade,
                'payments_remaining' => max(0, $hirePurchase->payments_remaining - 1),
                'last_payment_date' => $paymentDate,
                'total_penalties' => $hirePurchase->total_penalties + $penalty,
            ];

            // Check if contract is completed
            if ($newBalance <= 0) {
                $updateData['status'] = HirePurchaseContract::STATUS_COMPLETED;
                $updateData['actual_end_date'] = $paymentDate;

                // Update driver status
                $hirePurchase->driver->update([
                    'hire_purchase_status' => 'completed',
                ]);
            } else {
                // Calculate next payment due date
                $nextPayment = $hirePurchase->payments()
                    ->where('status', 'pending')
                    ->orderBy('due_date')
                    ->first();
                
                if ($nextPayment) {
                    $updateData['next_payment_due'] = $nextPayment->due_date;
                }
            }

            $hirePurchase->update($updateData);

            DB::commit();

            Log::info('Hire purchase payment recorded', [
                'contract_id' => $hirePurchase->id,
                'amount' => $amount,
                'penalty' => $penalty,
                'recorded_by' => $user->id,
            ]);

            return back()->with('success', 'Payment recorded successfully! Amount: ₦' . number_format($amount, 2));

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to record hire purchase payment', [
                'error' => $e->getMessage(),
            ]);

            return back()->with('error', 'Failed to record payment. Please try again.');
        }
    }

    /**
     * Generate payment schedule for a contract
     */
    private function generatePaymentSchedule(HirePurchaseContract $contract)
    {
        $currentDate = $contract->start_date->copy();
        $balanceRemaining = $contract->total_amount;

        for ($i = 1; $i <= $contract->total_payment_days; $i++) {
            $expectedAmount = min($contract->daily_payment, $balanceRemaining);
            $balanceAfterPayment = $balanceRemaining - $expectedAmount;

            HirePurchasePayment::create([
                'hire_purchase_contract_id' => $contract->id,
                'driver_id' => $contract->driver_id,
                'payment_number' => $i,
                'expected_amount' => $expectedAmount,
                'balance_before' => $balanceRemaining,
                'balance_after' => max(0, $balanceAfterPayment),
                'due_date' => $currentDate->copy(),
                'status' => HirePurchasePayment::STATUS_PENDING,
            ]);

            $balanceRemaining = $balanceAfterPayment;
            $currentDate->addDay();
        }
    }

    /**
     * Mark overdue payments
     */
    public function markOverduePayments()
    {
        $updated = HirePurchasePayment::where('status', 'pending')
            ->where('due_date', '<', today())
            ->update(['status' => 'overdue']);

        // Update contracts with missed payments
        $overdueContracts = HirePurchaseContract::whereHas('payments', function ($q) {
            $q->where('status', 'overdue');
        })->where('status', 'active')->get();

        foreach ($overdueContracts as $contract) {
            $missedCount = $contract->payments()->where('status', 'overdue')->count();
            $contract->update(['missed_payments' => $missedCount]);
        }

        return response()->json([
            'success' => true,
            'updated' => $updated,
        ]);
    }

    /**
     * Export hire purchase report
     */
    public function export(Request $request)
    {
        $user = auth()->user();

        $query = HirePurchaseContract::with(['driver', 'vehicle', 'branch'])
            ->when(!$user->hasRole(['Super Admin', 'Accountant']), function ($q) use ($user) {
                BranchAccessService::applyBranchFilter($q, $user);
            });

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $contracts = $query->get();

        $data = [
            'contracts' => $contracts,
            'generatedAt' => now()->format('M d, Y H:i'),
            'user' => $user,
        ];

        $pdf = app('dompdf.wrapper');
        $pdf->loadView('admin.hire-purchase.pdf', $data);
        $pdf->setPaper('A4', 'landscape');

        return $pdf->stream('hire_purchase_report_' . now()->format('Y-m-d') . '.pdf');
    }
}
