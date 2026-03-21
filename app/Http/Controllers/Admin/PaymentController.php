<?php

namespace App\Http\Controllers\Admin;

use App\Events\PaymentApproved;
use App\Http\Controllers\Controller;
use App\Models\CompanyAccountTransaction;
use App\Models\Driver;
use App\Models\HirePurchaseContract;
use App\Models\HirePurchasePayment;
use App\Models\Payment;
use App\Models\SystemSetting;
use App\Models\Transaction;
use App\Traits\HasDateFilters;
use App\Services\BranchAccessService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class PaymentController extends Controller
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
        $type = $request->get('type');
        $driverId = $request->get('driver_id');
        $chargingStatus = $request->get('charging_status');
        $branchId = $request->get('branch_id');
        $reference = $request->get('reference');

        // Get date range
        [$start, $end] = $this->getDateRange($timeFilter, $startDate, $endDate);

        // Build base query for summaries and transactions
$baseQuery = Transaction::with(['driver', 'approver'])
            ->when($user->hasRole(['Super Admin', 'Accountant']), function ($query) use ($branchId) {
                // Super Admin and Accountant can see all transactions (with optional branch filtering)
                if ($branchId) {
                    $query->whereHas('driver', function ($q) use ($branchId) {
                        $q->where('branch_id', $branchId);
                    });
                }
            }, function ($query) use ($user) {
                // Other admins see their assigned branches
                BranchAccessService::applyBranchFilterThroughRelation($query, $user, 'driver');
            })
            ->when($start && $end, function ($query) use ($start, $end) {
                $query->whereBetween('created_at', [$start, $end]);
            })
            ->when($status, function ($query) use ($status) {
                $query->where('status', $status);
            })
            ->when($type && $type !== 'charging_payment', function ($query) use ($type) {
                $query->where('type', $type);
            })
            ->when($driverId, function ($query) use ($driverId) {
                $query->where('driver_id', $driverId);
            })
            ->when($reference, function ($query) use ($reference) {
                $query->where('reference', 'like', $reference . '%');
            });

        // Calculate income summaries based on filters
        $incomeSummary = [
            'daily_remittance' => 0,
            'charging' => 0,
            'maintenance' => 0,
            'total' => 0
        ];

        // Always calculate summaries - show only selected type value when filtering
        $summaryQuery = clone $baseQuery;
        
        if (!$type) {
            // No type filter - show all values
            // Daily Remittance from transactions
            $incomeSummary['daily_remittance'] = $summaryQuery->clone()
                ->where('type', Transaction::TYPE_DAILY_REMITTANCE)
                ->where('status', 'successful')
                ->sum('amount');
                
            // Charging from charging requests (not transactions)
            $chargingQuery = \App\Models\ChargingRequest::with(['driver'])
                ->when($user->hasRole(['Super Admin', 'Accountant']), function ($query) use ($branchId) {
                    // Super Admin can filter by branch
                    if ($branchId) {
                        $query->whereHas('driver', function ($q) use ($branchId) {
                            $q->where('branch_id', $branchId);
                        });
                    }
                }, function ($query) use ($user) {
                    BranchAccessService::applyBranchFilterThroughRelation($query, $user, 'driver');
                })
                ->when($start && $end, function ($query) use ($start, $end) {
                    $query->whereBetween('created_at', [$start, $end]);
                })
                ->when($driverId, function ($query) use ($driverId) {
                    $query->where('driver_id', $driverId);
                })
                ->when($chargingStatus, function ($query) use ($chargingStatus) {
                    $query->where('status', $chargingStatus);
                })
                ->whereIn('status', ['approved', 'completed']);
                
            $incomeSummary['charging'] = $chargingQuery->sum('charging_cost');
                
            // Maintenance from transactions
            $incomeSummary['maintenance'] = $summaryQuery->clone()
                ->where('type', Transaction::TYPE_MAINTENANCE_DEBIT)
                ->where('status', 'successful')
                ->sum('amount');
                
            $incomeSummary['total'] = $incomeSummary['daily_remittance'] + $incomeSummary['charging'] + $incomeSummary['maintenance'];
        } else {
            // Type filter selected - show only that type's value
            if ($type === 'daily_remittance') {
                $incomeSummary['daily_remittance'] = $summaryQuery->clone()
                    ->where('type', Transaction::TYPE_DAILY_REMITTANCE)
                    ->where('status', 'successful')
                    ->sum('amount');
                $incomeSummary['charging'] = 0;
                $incomeSummary['maintenance'] = 0;
                $incomeSummary['total'] = $incomeSummary['daily_remittance'];
            } elseif ($type === 'charging_payment') {
                $chargingQuery = \App\Models\ChargingRequest::with(['driver'])
                    ->when($user->hasRole(['Super Admin', 'Accountant']), function ($query) use ($branchId) {
                        // Super Admin can filter by branch
                        if ($branchId) {
                            $query->whereHas('driver', function ($q) use ($branchId) {
                                $q->where('branch_id', $branchId);
                            });
                        }
                    }, function ($query) use ($user) {
                        BranchAccessService::applyBranchFilterThroughRelation($query, $user, 'driver');
                    })
                    ->when($start && $end, function ($query) use ($start, $end) {
                        $query->whereBetween('created_at', [$start, $end]);
                    })
                    ->when($driverId, function ($query) use ($driverId) {
                        $query->where('driver_id', $driverId);
                    })
                    ->when($chargingStatus, function ($query) use ($chargingStatus) {
                        $query->where('status', $chargingStatus);
                    })
                    ->whereIn('status', ['approved', 'completed']);
                    
                $incomeSummary['charging'] = $chargingQuery->sum('charging_cost');
                $incomeSummary['daily_remittance'] = 0;
                $incomeSummary['maintenance'] = 0;
                $incomeSummary['total'] = $incomeSummary['charging'];
            } elseif ($type === 'maintenance_debit') {
                $incomeSummary['maintenance'] = $summaryQuery->clone()
                    ->where('type', Transaction::TYPE_MAINTENANCE_DEBIT)
                    ->where('status', 'successful')
                    ->sum('amount');
                $incomeSummary['daily_remittance'] = 0;
                $incomeSummary['charging'] = 0;
                $incomeSummary['total'] = $incomeSummary['maintenance'];
            } elseif ($type === 'wallet_funding') {
                $incomeSummary['daily_remittance'] = 0;
                $incomeSummary['charging'] = 0;
                $incomeSummary['maintenance'] = 0;
                $incomeSummary['total'] = 0; // Wallet funding not part of income calculation
            }
        }

        // Handle charging_payment type filter by combining charging requests with transactions
        if ($type === 'charging_payment' || ($reference && substr($reference, 0, 6) === 'CHARGE')) {
            // Get charging requests and convert them to transaction-like objects
            $chargingRequestsQuery = \App\Models\ChargingRequest::with(['driver'])
                ->when($user->hasRole(['Super Admin', 'Accountant']), function ($query) use ($branchId) {
                    // Super Admin can filter by branch
                    if ($branchId) {
                        $query->whereHas('driver', function ($q) use ($branchId) {
                            $q->where('branch_id', $branchId);
                        });
                    }
                }, function ($query) use ($user) {
                    BranchAccessService::applyBranchFilterThroughRelation($query, $user, 'driver');
                })
                ->when($start && $end, function ($query) use ($start, $end) {
                    $query->whereBetween('created_at', [$start, $end]);
                })
                ->when($status, function ($query) use ($status) {
                    // Map transaction status to charging status
                    if ($status === 'successful') {
                        $query->whereIn('status', ['approved', 'completed']);
                    } elseif ($status === 'pending') {
                        $query->where('status', 'pending');
                    } elseif ($status === 'rejected') {
                        $query->where('status', 'cancelled');
                    }
                })
                ->when($driverId, function ($query) use ($driverId) {
                    $query->where('driver_id', $driverId);
                })
                ->when($chargingStatus, function ($query) use ($chargingStatus) {
                    $query->where('status', $chargingStatus);
                })
                ->latest();
                
            $chargingRequests = $chargingRequestsQuery->paginate(20)->withQueryString();
            
            // Transform charging requests to look like transactions for the view
            $transactions = $chargingRequests->getCollection()->map(function ($request) {
                return (object) [
                    'id' => $request->id,
                    'driver' => (object) [
                        'full_name' => $request->driver->full_name ?? 'Unknown Driver',
                        'phone_number' => $request->driver->phone_number ?? 'N/A',
                    ],
                    'type' => 'charging_payment',
                    'amount' => $request->charging_cost ?? 0,
                    'status' => $request->status === 'approved' || $request->status === 'completed' ? 'successful' : 
                              ($request->status === 'pending' ? 'pending' : 'rejected'),
                    'payment_proof' => $request->payment_receipt,
                    'created_at' => $request->created_at,
                    'approver' => $request->approvedBy,
                    'description' => null,
                ];
            });
            
            // Rebuild the paginator with transformed data
            $transactions = new \Illuminate\Pagination\LengthAwarePaginator(
                $transactions,
                $chargingRequests->total(),
                $chargingRequests->perPage(),
                $chargingRequests->currentPage(),
                ['path' => request()->url(), 'query' => request()->query()]
            );
        } else {
            $transactions = $baseQuery->latest()
                ->paginate(20)
                ->withQueryString();
        }

        // Get drivers for filter
        $drivers = Driver::when(!$user->hasRole(['Super Admin', 'Accountant']), function ($query) use ($user) {
            BranchAccessService::applyBranchFilter($query, $user);
        })->get();

        // Get branches for filter (for Super Admin and multi-branch managers)
        $branches = BranchAccessService::getAvailableBranchesForUser($user);

        return view('admin.payments.index', compact(
            'transactions',
            'drivers',
            'branches',
            'driverId',
            'branchId',
            'status',
            'type',
            'timeFilter',
            'startDate',
            'endDate',
            'chargingStatus',
            'incomeSummary'
        ));
    }

    public function approve(Request $request, Transaction $transaction)
    {
        $user = auth()->user();
        
        // Check if user can approve this transaction
        if (!BranchAccessService::canAccessBranch($user, $transaction->driver->branch_id)) {
            abort(403, 'You do not have permission to approve this transaction.');
        }
        
        if ($transaction->status !== 'pending') {
            return back()->withErrors(['error' => 'This transaction has already been processed.']);
        }

        // Check if payment proof is uploaded for daily remittances
        if ($transaction->type === Transaction::TYPE_DAILY_REMITTANCE && !$transaction->payment_proof) {
            return back()->withErrors(['error' => 'Cannot approve. Driver must upload payment receipt first.']);
        }

        // Validate amount change for Super Admin
        $amount = $transaction->amount;
        if ($user->hasRole('Super Admin') && $request->has('amount')) {
            $request->validate([
                'amount' => 'required|numeric|min:0.01'
            ]);
            $amount = $request->amount;
        }

        DB::transaction(function () use ($transaction, $amount) {
            $transaction->update([
                'status' => 'successful',
                'approved_by' => auth()->id(),
                'processed_at' => now(),
                'amount' => $amount,
            ]);

            // Fire event to update daily ledger
            event(new PaymentApproved($transaction));

            // Record daily remittance in company account as income
            if ($transaction->type === Transaction::TYPE_DAILY_REMITTANCE) {
                CompanyAccountTransaction::create([
                    'branch_id' => $transaction->driver->branch_id,
                    'type' => 'income',
                    'amount' => $amount,
                    'category' => $transaction->is_hire_purchase_payment ? 'hire_purchase_payment' : 'daily_remittance',
                    'reference' => ($transaction->is_hire_purchase_payment ? 'HP-' : 'REMIT-') . $transaction->id,
                    'description' => ($transaction->is_hire_purchase_payment ? 'Hire purchase payment' : 'Daily remittance') . 
                        ' from ' . $transaction->driver->full_name . ' - Transaction #' . $transaction->id,
                    'transaction_date' => $transaction->created_at->toDateString(),
                    'recorded_by' => auth()->id(),
                ]);
                
                // If this is a hire purchase payment, update the hire purchase records
                if ($transaction->is_hire_purchase_payment && $transaction->hire_purchase_contract_id) {
                    $this->processHirePurchasePayment($transaction, $amount);
                }
            }
        });

        return redirect()->route('admin.payments.index')
            ->with('success', 'Payment approved successfully!');
    }

    public function reject(Request $request, Transaction $transaction)
    {
        $user = auth()->user();
        
        // Check if user can reject this transaction
        if (!BranchAccessService::canAccessBranch($user, $transaction->driver->branch_id)) {
            abort(403, 'You do not have permission to reject this transaction.');
        }
        
        if ($transaction->status !== 'pending') {
            return back()->withErrors(['error' => 'This transaction has already been processed.']);
        }

        $request->validate([
            'rejection_comment' => 'required|string|max:500'
        ]);

        $transaction->update([
            'status' => 'rejected',
            'approved_by' => auth()->id(),
            'description' => $request->rejection_comment,
        ]);

        return redirect()->route('admin.payments.index')
            ->with('success', 'Payment rejected successfully!');
    }

    public function restore(Transaction $transaction)
    {
        $user = auth()->user();
        
        // Check if user can restore this transaction
        if (!BranchAccessService::canAccessBranch($user, $transaction->driver->branch_id)) {
            abort(403, 'You do not have permission to restore this transaction.');
        }
        
        if ($transaction->status !== 'rejected') {
            return back()->withErrors(['error' => 'Only rejected transactions can be restored.']);
        }

        // Create a new identical transaction with all original data
        $newTransaction = Transaction::create([
            'driver_id' => $transaction->driver_id,
            'type' => $transaction->type,
            'amount' => $transaction->amount,
            'reference' => 'RESTORED-' . $transaction->reference . '-' . time(),
            'description' => 'Restored from rejected transaction #' . $transaction->id,
            'payment_proof' => null,
            'paid_at' => $transaction->paid_at, // Copy original payment date
            'status' => 'pending',
            'approved_by' => null,
            'processed_by' => null,
            'processed_at' => null,
        ]);

        // Update created_at to match original transaction
        $newTransaction->created_at = $transaction->created_at;
        $newTransaction->save();

        return redirect()->route('admin.payments.index')
            ->with('success', 'Payment restored successfully! New transaction created with pending status.');
    }

    /**
     * Manually generate daily remittances for all active drivers with assigned vehicles
     */
    public function generateDailyRemittances(Request $request)
    {
        try {
            $user = auth()->user();
            $date = $request->input('date', now()->toDateString());
            $branchId = $request->input('branch_id');
            
            Log::info('Manual daily remittance generation initiated (only drivers with active vehicle assignments)', [
                'user_id' => auth()->id(),
                'user_email' => auth()->user()->email,
                'date' => $date,
                'branch_id' => $branchId,
                'timestamp' => now(),
            ]);

            DB::beginTransaction();
            
            // Get default daily remittance amount from settings
            $defaultAmount = SystemSetting::get('daily_remittance_amount', 5000.00);

            // Get all drivers based on user role and branch filter (with valid user accounts and active vehicle assignments)
            $drivers = Driver::with(['user', 'branch', 'vehicleAssignments'])
                ->when(!$user->hasRole(['Super Admin', 'Accountant']), function ($query) use ($user) {
                    BranchAccessService::applyBranchFilter($query, $user);
                })
                ->when($user->hasRole(['Super Admin', 'Accountant']) && $branchId, function ($query) use ($branchId) {
                    $query->where('branch_id', $branchId);
                })
                ->whereHas('user') // Only drivers with user accounts
                ->whereHas('vehicleAssignments', function ($query) {
                    $query->active(); // Only drivers with active vehicle assignments
                })
                ->get();

            $generated = 0;
            $skippedDuplicate = 0;
            $skippedNoCharging = 0;
            $skippedNoAssignment = 0;
            $errors = [];

            foreach ($drivers as $driver) {
                try {
                    // Check if remittance already exists for the selected date
                    $existingRemittance = Transaction::where('driver_id', $driver->id)
                        ->where('type', Transaction::TYPE_DAILY_REMITTANCE)
                        ->whereDate('created_at', $date)
                        ->first();

                    // Debug logging to check what's happening
                    $allDriverTransactions = Transaction::where('driver_id', $driver->id)
                        ->where('type', Transaction::TYPE_DAILY_REMITTANCE)
                        ->get();
                    
                    Log::info('Checking for existing remittance', [
                        'driver_id' => $driver->id,
                        'driver_name' => $driver->first_name . ' ' . $driver->last_name,
                        'selected_date' => $date,
                        'query_sql' => Transaction::where('driver_id', $driver->id)
                            ->where('type', Transaction::TYPE_DAILY_REMITTANCE)
                            ->whereDate('created_at', $date)
                            ->toSql(),
                        'existing_found' => $existingRemittance ? 'YES' : 'NO',
                        'existing_id' => $existingRemittance?->id,
                        'existing_created_at' => $existingRemittance?->created_at,
                        'all_driver_transactions' => $allDriverTransactions->map(function($t) {
                            return [
                                'id' => $t->id,
                                'type' => $t->type,
                                'created_at' => $t->created_at,
                                'created_at_date' => $t->created_at->format('Y-m-d'),
                                'amount' => $t->amount,
                            ];
                        })->toArray(),
                    ]);

                    if ($existingRemittance) {
                        $skippedDuplicate++;
                        Log::info('Skipped duplicate remittance', [
                            'driver_id' => $driver->id,
                            'driver_name' => $driver->first_name . ' ' . $driver->last_name,
                            'existing_transaction_id' => $existingRemittance->id,
                            'date' => $date,
                        ]);
                        continue;
                    }

                    // Check if skip charging check is enabled with selected drivers
                    $skipChargingCheck = $request->input('skip_charging_check');
                    $selectedDrivers = $request->input('selected_drivers', []);
                    
                    // Check if driver has completed/approved charging request for the selected date (for older dates)
                    if ($date < now()->toDateString()) {
                        $hasCompletedCharging = \App\Models\ChargingRequest::where('driver_id', $driver->id)
                            ->whereDate('created_at', $date)
                            ->whereIn('status', ['approved', 'completed'])
                            ->exists();

                        if (!$hasCompletedCharging) {
                            // If skip charging check is enabled and driver is in selected list, allow
                            if ($skipChargingCheck && !empty($selectedDrivers) && in_array($driver->id, $selectedDrivers)) {
                                Log::info('Driver included despite no charging (manually selected)', [
                                    'driver_id' => $driver->id,
                                    'driver_name' => $driver->first_name . ' ' . $driver->last_name,
                                    'date' => $date,
                                ]);
                            } else {
                                $skippedNoCharging++;
                                Log::info('Skipped driver - no completed charging activity found for date', [
                                    'driver_id' => $driver->id,
                                    'driver_name' => $driver->first_name . ' ' . $driver->last_name,
                                    'date' => $date,
                                ]);
                                continue;
                            }
                        }
                    }

                    // Create new daily remittance transaction
                    $selectedDate = \Carbon\Carbon::parse($date);
                    
                    // Determine amount and hire purchase details
                    $amount = $defaultAmount;
                    $isHirePurchase = false;
                    $hirePurchaseContractId = null;
                    $description = 'Daily remittance - ' . $selectedDate->format('F d, Y') . ' (Manual generation)';
                    
                    // Check if driver is hire purchase and has active contract
                    if ($driver->is_hire_purchase) {
                        $activeContract = HirePurchaseContract::where('driver_id', $driver->id)
                            ->where('status', 'active')
                            ->first();
                        
                        if ($activeContract) {
                            // Skip Sundays for hire purchase drivers (rest day)
                            // if ($selectedDate->isSunday()) {
                            //     Log::info('Skipped hire purchase driver on Sunday (rest day)', [
                            //         'driver_id' => $driver->id,
                            //         'driver_name' => $driver->first_name . ' ' . $driver->last_name,
                            //         'date' => $date,
                            //     ]);
                            //     continue;
                            // }
                            
                            $isHirePurchase = true;
                            $hirePurchaseContractId = $activeContract->id;
                            $amount = $activeContract->daily_payment;
                            $description = 'Hire Purchase Payment - ' . $selectedDate->format('F d, Y') . ' (Contract: ' . $activeContract->contract_number . ')';
                        }
                    }
                    
                    // Create transaction with custom timestamps
                    $transaction = Transaction::withoutTimestamps(function() use ($driver, $amount, $selectedDate, $isHirePurchase, $hirePurchaseContractId, $description) {
                        $newTransaction = new Transaction();
                        $newTransaction->driver_id = $driver->id;
                        $newTransaction->type = Transaction::TYPE_DAILY_REMITTANCE;
                        $newTransaction->amount = $amount;
                        $newTransaction->reference = ($isHirePurchase ? 'HP-' : 'REMIT-') . strtoupper(uniqid()) . '-' . $driver->id;
                        $newTransaction->description = $description;
                        $newTransaction->status = Transaction::STATUS_PENDING;
                        $newTransaction->processed_by = auth()->id();
                        $newTransaction->is_hire_purchase_payment = $isHirePurchase;
                        $newTransaction->hire_purchase_contract_id = $hirePurchaseContractId;
                        $newTransaction->created_at = $selectedDate;
                        $newTransaction->updated_at = $selectedDate;
                        $newTransaction->save();
                        return $newTransaction;
                    });

                    $generated++;

                    Log::info('Daily remittance generated', [
                        'transaction_id' => $transaction->id,
                        'driver_id' => $driver->id,
                        'driver_name' => $driver->first_name . ' ' . $driver->last_name,
                        'amount' => $defaultAmount,
                        'date' => $date,
                    ]);

                } catch (\Exception $e) {
                    $errors[] = [
                        'driver_id' => $driver->id,
                        'driver_name' => $driver->first_name . ' ' . $driver->last_name,
                        'error' => $e->getMessage(),
                    ];

                    Log::error('Error generating remittance for driver', [
                        'driver_id' => $driver->id,
                        'error' => $e->getMessage(),
                        'trace' => $e->getTraceAsString(),
                    ]);
                }
            }

            DB::commit();

            $totalSkipped = $skippedDuplicate + $skippedNoCharging + $skippedNoAssignment;
            
            Log::info('Manual daily remittance generation completed', [
                'total_drivers' => $drivers->count(),
                'generated' => $generated,
                'skipped_duplicate' => $skippedDuplicate,
                'skipped_no_charging' => $skippedNoCharging,
                'skipped_no_assignment' => $skippedNoAssignment,
                'total_skipped' => $totalSkipped,
                'errors' => count($errors),
                'user_id' => auth()->id(),
            ]);

            // Prepare detailed success message
            $messageParts = [];
            $messageParts[] = "{$generated} created";
            
            if ($skippedDuplicate > 0) {
                $messageParts[] = "{$skippedDuplicate} skipped (already exists)";
            }
            
            if ($skippedNoCharging > 0) {
                $messageParts[] = "{$skippedNoCharging} skipped (no completed charging activity on {$date})";
            }
            
            if ($skippedNoAssignment > 0) {
                $messageParts[] = "{$skippedNoAssignment} skipped (no vehicle assignment)";
            }
            
            $message = "Daily remittances generation completed for " . date('F d, Y', strtotime($date)) . ": " . implode(', ', $messageParts) . ".";
            
            if (count($errors) > 0) {
                $message .= " {count($errors)} failed - check logs for details.";
                return redirect()->route('admin.payments.index')
                    ->with('warning', $message);
            }

            return redirect()->route('admin.payments.index')
                ->with('success', $message);

        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Fatal error in daily remittance generation', [
                'user_id' => auth()->id(),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return redirect()->route('admin.payments.index')
                ->with('error', 'Failed to generate daily remittances: ' . $e->getMessage());
        }
    }

    /**
     * AJAX endpoint to get drivers without remittance for a specific date
     */
    public function getDriversWithoutRemittance(Request $request)
    {
        $user = auth()->user();
        $date = $request->input('date', now()->toDateString());
        $branchId = $request->input('branch_id');

        // Get all drivers with active vehicle assignments who don't have remittance for this date
        $drivers = Driver::with(['user', 'branch', 'vehicleAssignments'])
            ->when(!$user->hasRole(['Super Admin', 'Accountant']), function ($query) use ($user) {
                BranchAccessService::applyBranchFilter($query, $user);
            })
            ->when($user->hasRole(['Super Admin', 'Accountant']) && $branchId, function ($query) use ($branchId) {
                $query->where('branch_id', $branchId);
            })
            ->whereHas('user')
            ->whereHas('vehicleAssignments', function ($query) {
                $query->active();
            })
            ->whereDoesntHave('transactions', function ($query) use ($date) {
                $query->where('type', Transaction::TYPE_DAILY_REMITTANCE)
                    ->whereDate('created_at', $date);
            })
            ->get();

        // Check charging activity for each driver
        $driverData = $drivers->map(function ($driver) use ($date) {
            $hasCharging = \App\Models\ChargingRequest::where('driver_id', $driver->id)
                ->whereDate('created_at', $date)
                ->whereIn('status', ['approved', 'completed'])
                ->exists();

            return [
                'id' => $driver->id,
                'name' => $driver->first_name . ' ' . $driver->last_name,
                'phone' => $driver->phone_number ?? '-',
                'branch' => $driver->branch->name ?? '-',
                'is_hire_purchase' => (bool) $driver->is_hire_purchase,
                'has_charging' => $hasCharging,
            ];
        });

        return response()->json([
            'drivers' => $driverData,
            'date' => $date,
            'total' => $driverData->count(),
        ]);
    }

    public function generatePdf(Request $request)
    {
        $user = auth()->user();
        
        // Get the EXACT same filter parameters as index method
        $timeFilter = $request->get('time_filter', 'monthly');
        $startDate = $request->get('start_date');
        $endDate = $request->get('end_date');
        $status = $request->get('status');
        $type = $request->get('type');
        $driverId = $request->get('driver_id');
        $chargingStatus = $request->get('charging_status');
        $branchId = $request->get('branch_id');
        $reference = $request->get('reference');

        // Get date range
        [$start, $end] = $this->getDateRange($timeFilter, $startDate, $endDate);

        // Build the EXACT same base query as index method
        $baseQuery = Transaction::with(['driver', 'approver'])
            ->when($user->hasRole(['Super Admin', 'Accountant']), function ($query) use ($branchId) {
                // Super Admin can filter by branch
                if ($branchId) {
                    $query->whereHas('driver', function ($q) use ($branchId) {
                        $q->where('branch_id', $branchId);
                    });
                }
            }, function ($query) use ($user) {
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
            ->when($type && $type !== 'charging_payment', function ($query) use ($type) {
                $query->where('type', $type);
            })
            ->when($reference, function ($query) use ($reference) {
                $query->where('reference', 'like', $reference . '%');
            });

        // Handle charging payment type EXACTLY like index method
        if ($type === 'charging_payment' || ($reference && substr($reference, 0, 6) === 'CHARGE')) {
            // Get charging requests and convert them to transaction-like objects
            $chargingRequestsQuery = \App\Models\ChargingRequest::with(['driver'])
                ->when($user->hasRole(['Super Admin', 'Accountant']), function ($query) use ($branchId) {
                    // Super Admin can filter by branch
                    if ($branchId) {
                        $query->whereHas('driver', function ($q) use ($branchId) {
                            $q->where('branch_id', $branchId);
                        });
                    }
                }, function ($query) use ($user) {
                    BranchAccessService::applyBranchFilterThroughRelation($query, $user, 'driver');
                })
                ->when($start && $end, function ($query) use ($start, $end) {
                    $query->whereBetween('created_at', [$start, $end]);
                })
                ->when($driverId, function ($query) use ($driverId) {
                    $query->where('driver_id', $driverId);
                })
                ->when($status, function ($query) use ($status) {
                    // Map transaction status to charging status
                    if ($status === 'successful') {
                        $query->whereIn('status', ['approved', 'completed']);
                    } elseif ($status === 'pending') {
                        $query->where('status', 'pending');
                    } elseif ($status === 'rejected') {
                        $query->where('status', 'cancelled');
                    }
                })
                ->when($chargingStatus, function ($query) use ($chargingStatus) {
                    $query->where('status', $chargingStatus);
                })
                ->latest();
                
            $chargingRequests = $chargingRequestsQuery->get();
            
            // Transform charging requests to look like transactions for the view
            $transactions = $chargingRequests->map(function ($request) {
                return (object) [
                    'id' => $request->id,
                    'driver' => (object) [
                        'full_name' => $request->driver->full_name ?? 'Unknown Driver',
                        'phone_number' => $request->driver->phone_number ?? 'N/A',
                        'branch' => (object) [
                            'name' => $request->driver->branch->name ?? 'N/A'
                        ]
                    ],
                    'type' => 'charging_payment',
                    'amount' => $request->charging_cost ?? 0,
                    'status' => $request->status === 'approved' || $request->status === 'completed' ? 'successful' : 
                              ($request->status === 'pending' ? 'pending' : 'rejected'),
                    'payment_proof' => $request->payment_receipt,
                    'created_at' => $request->created_at,
                    'approver' => $request->approvedBy,
                    'description' => null,
                ];
            });
        } else {
            $transactions = $baseQuery->latest()->get();
        }
        
        // Calculate summary statistics
        $summary = [
            'total_transactions' => $transactions->count(),
            'total_amount' => $transactions->sum('amount'),
            'pending_count' => $transactions->where('status', 'pending')->count(),
            'pending_amount' => $transactions->where('status', 'pending')->sum('amount'),
            'successful_count' => $transactions->where('status', 'successful')->count(),
            'successful_amount' => $transactions->where('status', 'successful')->sum('amount'),
            'rejected_count' => $transactions->where('status', 'rejected')->count(),
            'rejected_amount' => $transactions->where('status', 'rejected')->sum('amount'),
        ];
        
        // Group by type for breakdown
        $typeBreakdown = $transactions->groupBy('type')->map(function ($group) {
            return [
                'count' => $group->count(),
                'amount' => $group->sum('amount'),
                'pending_count' => $group->where('status', 'pending')->count(),
                'successful_count' => $group->where('status', 'successful')->count(),
                'rejected_count' => $group->where('status', 'rejected')->count(),
            ];
        });
        
        // Prepare filter descriptions for PDF
        $filterDescription = [];
        if ($request->filled('status')) {
            $filterDescription[] = 'Status: ' . ucfirst($request->status);
        }
        if ($request->filled('type')) {
            $filterDescription[] = 'Type: ' . str_replace('_', ' ', ucfirst($request->type));
        }
        if ($request->filled('driver_id')) {
            $driver = Driver::find($request->driver_id);
            $filterDescription[] = 'Driver: ' . ($driver ? $driver->full_name : 'Unknown');
        }
        if ($request->filled('branch_id')) {
            $branch = \App\Models\Branch::find($request->branch_id);
            $filterDescription[] = 'Branch: ' . ($branch ? $branch->name : 'Unknown');
        }
        if ($request->filled('timeFilter') && $request->timeFilter !== 'all') {
            $filterDescription[] = 'Period: ' . $this->getDateFilterDescription($request->timeFilter, $request->startDate, $request->endDate);
        }
        
        $data = [
            'transactions' => $transactions,
            'summary' => $summary,
            'typeBreakdown' => $typeBreakdown,
            'filterDescription' => $filterDescription,
            'user' => $user,
            'generatedAt' => now()->format('M d, Y H:i'),
        ];
        
        $pdf = app('dompdf.wrapper');
        $pdf->loadView('admin.payments.pdf', $data);
        
        // Set paper size and orientation
        $pdf->setPaper('A4', 'landscape');
        
        // Set filename
        $filename = 'payments_report_' . now()->format('Y-m-d_H-i-s') . '.pdf';
        
        return $pdf->stream($filename);
    }

    private function getDateFilterDescription($timeFilter, $startDate = null, $endDate = null)
    {
        switch ($timeFilter) {
            case 'today':
                return 'Today';
            case 'yesterday':
                return 'Yesterday';
            case 'this_week':
                return 'This Week';
            case 'last_week':
                return 'Last Week';
            case 'this_month':
                return 'This Month';
            case 'last_month':
                return 'Last Month';
            case 'custom':
                if ($startDate && $endDate) {
                    return date('M d, Y', strtotime($startDate)) . ' - ' . date('M d, Y', strtotime($endDate));
                }
                return 'Custom Range';
            default:
                return 'All Time';
        }
    }

    /**
     * Process hire purchase payment - update HP contract and payment records
     */
    private function processHirePurchasePayment(Transaction $transaction, float $amount)
    {
        $contract = HirePurchaseContract::find($transaction->hire_purchase_contract_id);
        
        if (!$contract) {
            Log::warning('Hire purchase contract not found for payment processing', [
                'transaction_id' => $transaction->id,
                'contract_id' => $transaction->hire_purchase_contract_id,
            ]);
            return;
        }

        // Find or create the hire purchase payment record for this date
        $paymentDate = $transaction->created_at->toDateString();
        $hpPayment = HirePurchasePayment::where('hire_purchase_contract_id', $contract->id)
            ->whereDate('due_date', $paymentDate)
            ->first();

        if ($hpPayment) {
            // Update existing payment record
            $hpPayment->update([
                'amount_paid' => $amount,
                'actual_amount' => $amount,
                'paid_at' => now(),
                'status' => 'paid',
                'transaction_id' => $transaction->id,
                'payment_proof' => $transaction->payment_proof,
            ]);
        } else {
            // Create new payment record (manual payment outside schedule)
            $balanceBefore = $contract->total_balance;
            $balanceAfter = max(0, $balanceBefore - $amount);
            
            HirePurchasePayment::create([
                'hire_purchase_contract_id' => $contract->id,
                'driver_id' => $transaction->driver_id,
                'payment_number' => $contract->payments_made + 1,
                'expected_amount' => $contract->daily_payment,
                'amount_paid' => $amount,
                'actual_amount' => $amount,
                'balance_before' => $balanceBefore,
                'balance_after' => $balanceAfter,
                'due_date' => $paymentDate,
                'paid_at' => now(),
                'status' => 'paid',
                'transaction_id' => $transaction->id,
                'payment_proof' => $transaction->payment_proof,
            ]);
        }

        // Update contract totals
        $newTotalPaid = $contract->total_paid + $amount;
        $newBalance = max(0, $contract->total_amount - $newTotalPaid);
        $newPaymentsMade = $contract->payments_made + 1;
        $newPaymentsRemaining = max(0, $contract->total_payment_days - $newPaymentsMade);

        $updateData = [
            'total_paid' => $newTotalPaid,
            'total_balance' => $newBalance,
            'payments_made' => $newPaymentsMade,
            'payments_remaining' => $newPaymentsRemaining,
            'last_payment_date' => now(),
        ];

        // Check if contract is completed
        if ($newBalance <= 0) {
            $updateData['status'] = 'completed';
            $updateData['actual_end_date'] = now();
            
            // Update driver hire purchase status
            $transaction->driver->update([
                'hire_purchase_status' => 'completed',
            ]);
            
            Log::info('Hire purchase contract completed', [
                'contract_id' => $contract->id,
                'driver_id' => $transaction->driver_id,
                'total_paid' => $newTotalPaid,
            ]);
        } else {
            // Calculate next payment due date (skip Sundays)
            $nextDue = Carbon::parse($paymentDate)->addDay();
            while ($nextDue->isSunday()) {
                $nextDue->addDay();
            }
            $updateData['next_payment_due'] = $nextDue;
        }

        $contract->update($updateData);

        Log::info('Hire purchase payment processed', [
            'contract_id' => $contract->id,
            'transaction_id' => $transaction->id,
            'amount' => $amount,
            'new_total_paid' => $newTotalPaid,
            'new_balance' => $newBalance,
        ]);
    }

    /**
     * Skip payment for a driver (sick, maintenance, etc.)
     */
    public function skipPayment(Request $request, Transaction $transaction)
    {
        $user = auth()->user();
        
        // Check permission
        if (!BranchAccessService::canAccessBranch($user, $transaction->driver->branch_id)) {
            abort(403, 'You do not have permission to skip this payment.');
        }
        
        if ($transaction->status !== 'pending') {
            return back()->withErrors(['error' => 'Only pending payments can be skipped.']);
        }

        $request->validate([
            'skip_reason' => 'required|string|in:sick,vehicle_maintenance,personal_emergency,public_holiday,other',
            'skip_notes' => 'nullable|string|max:500',
        ]);

        $reasonLabels = [
            'sick' => 'Driver Sick',
            'vehicle_maintenance' => 'Vehicle Under Maintenance',
            'personal_emergency' => 'Personal Emergency',
            'public_holiday' => 'Public Holiday',
            'other' => 'Other Reason',
        ];

        $skipReason = $reasonLabels[$request->skip_reason] ?? $request->skip_reason;
        $skipNotes = $request->skip_notes ? " - {$request->skip_notes}" : '';

        DB::transaction(function () use ($transaction, $skipReason, $skipNotes) {
            // Mark transaction as skipped (using rejected status with special description)
            $transaction->update([
                'status' => 'rejected',
                'approved_by' => auth()->id(),
                'description' => "SKIPPED: {$skipReason}{$skipNotes}",
                'processed_at' => now(),
            ]);

            // If this is a hire purchase payment, mark the HP payment record as skipped
            if ($transaction->is_hire_purchase_payment && $transaction->hire_purchase_contract_id) {
                $paymentDate = $transaction->created_at->toDateString();
                $hpPayment = HirePurchasePayment::where('hire_purchase_contract_id', $transaction->hire_purchase_contract_id)
                    ->whereDate('due_date', $paymentDate)
                    ->first();

                if ($hpPayment) {
                    $hpPayment->update([
                        'status' => 'skipped',
                        'notes' => "Skipped: {$skipReason}{$skipNotes}",
                    ]);
                }
            }

            Log::info('Payment skipped by admin', [
                'transaction_id' => $transaction->id,
                'driver_id' => $transaction->driver_id,
                'reason' => $skipReason,
                'skipped_by' => auth()->id(),
            ]);
        });

        return redirect()->route('admin.payments.index')
            ->with('success', "Payment skipped successfully. Reason: {$skipReason}");
    }
}
