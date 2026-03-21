<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\AccountDebitRequest;
use App\Models\CompanyAccountTransaction;
use App\Models\Branch;
use App\Models\SystemSetting;
use App\Services\BranchAccessService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class AdminApiController extends Controller
{
    /**
     * Admin Login
     */
    public function login(Request $request)
    {
        Log::info('Admin Login Attempt', [
            'email' => $request->email,
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'timestamp' => now()
        ]);

        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        $user = User::where('email', $request->email)->first();

        if (!$user) {
            Log::warning('Login Failed - User Not Found', [
                'email' => $request->email,
                'ip' => $request->ip()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Invalid credentials'
            ], 401);
        }

        if (!Hash::check($request->password, $user->password)) {
            Log::warning('Login Failed - Invalid Password', [
                'email' => $request->email,
                'user_id' => $user->id,
                'ip' => $request->ip()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Invalid credentials'
            ], 401);
        }

        // Check if user is Super Admin or CEO
        if (!in_array($user->role, ['Super Admin', 'CEO'])) {
            Log::warning('Login Failed - Insufficient Role', [
                'email' => $request->email,
                'user_id' => $user->id,
                'role' => $user->role,
                'ip' => $request->ip()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Access denied. Only Super Admins and CEOs can access this app.'
            ], 403);
        }

        // Create token
        $token = $user->createToken('eride_admin_app')->plainTextToken;

        Log::info('Login Successful', [
            'user_id' => $user->id,
            'email' => $user->email,
            'name' => $user->name,
            'role' => $user->role,
            'ip' => $request->ip()
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Login successful',
            'token' => $token,
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role,
                'phone' => $user->phone,
                'branch_id' => $user->branch_id,
            ]
        ]);
    }

    /**
     * Dashboard Data with Financial Analytics
     */
    public function dashboard(Request $request)
    {
        try {
            // Get filter parameters
            $filterType = $request->query('filter_type', 'all');
            $startDate = $request->query('start_date');
            $endDate = $request->query('end_date');
            $selectedMonth = $request->query('month');
            $selectedYear = $request->query('year');
            
            // Calculate date range based on filter type
            $dateFilter = $this->calculateDateFilter($filterType, $startDate, $endDate, $selectedMonth, $selectedYear);
            
            Log::info('Fetching Dashboard Data', [
                'user_id' => auth()->id(),
                'filter_type' => $filterType,
                'date_range' => [
                    'start' => $dateFilter['start'] ? $dateFilter['start']->toDateTimeString() : null,
                    'end' => $dateFilter['end'] ? $dateFilter['end']->toDateTimeString() : null,
                ]
            ]);
            
            // Company Account Balance - with date filter
            $incomeQuery = CompanyAccountTransaction::where('type', 'income');
            $this->applyDateFilter($incomeQuery, $dateFilter, 'transaction_date');
            $totalIncome = $incomeQuery->sum('amount');
            
            $expenseQuery = CompanyAccountTransaction::where('type', 'expense');
            $this->applyDateFilter($expenseQuery, $dateFilter, 'transaction_date');
            $totalExpense = $expenseQuery->sum('amount');
            
            $balance = $totalIncome - $totalExpense;
            
            Log::info('Company Balance Calculated', [
                'total_income' => $totalIncome,
                'total_expense' => $totalExpense,
                'balance' => $balance,
                'filter_applied' => $dateFilter['start'] !== null
            ]);
            
            // Income Breakdown by Type (Remittance, Charging, Maintenance) - with date filter
            $remittanceQuery = \App\Models\Transaction::where('type', \App\Models\Transaction::TYPE_DAILY_REMITTANCE)
                ->where('status', \App\Models\Transaction::STATUS_SUCCESSFUL);
            $this->applyDateFilter($remittanceQuery, $dateFilter);
            $remittanceIncome = $remittanceQuery->sum('amount');
            
            $chargingQuery = \App\Models\ChargingRequest::where('status', \App\Models\ChargingRequest::STATUS_COMPLETED);
            $this->applyDateFilter($chargingQuery, $dateFilter);
            $chargingIncome = $chargingQuery->sum('charging_cost');
            
            // Maintenance cost is calculated from parts relationship - with date filter
            $maintenanceQuery = \App\Models\MaintenanceRequest::where('status', 'completed');
            $this->applyDateFilter($maintenanceQuery, $dateFilter);
            $maintenanceRequests = $maintenanceQuery->with('parts')->get();
            
            $maintenanceIncome = 0;
            foreach ($maintenanceRequests as $request) {
                $maintenanceIncome += $request->total_cost;
            }
            
            // Total income from all sources
            $totalServiceIncome = $remittanceIncome + $chargingIncome + $maintenanceIncome;
            
            // Branch Balances - Get all branches with their income
            $branches = Branch::all();
            $branchBalances = [];
            $totalBranchIncome = 0;
            
            foreach ($branches as $branch) {
                // Get branch income from transactions - with date filter
                $branchIncomeQuery = CompanyAccountTransaction::where('type', 'income')
                    ->where('branch_id', $branch->id);
                $this->applyDateFilter($branchIncomeQuery, $dateFilter, 'transaction_date');
                $branchIncome = $branchIncomeQuery->sum('amount');
                
                // Get branch expenses - with date filter
                $branchExpenseQuery = CompanyAccountTransaction::where('type', 'expense')
                    ->where('branch_id', $branch->id);
                $this->applyDateFilter($branchExpenseQuery, $dateFilter, 'transaction_date');
                $branchExpense = $branchExpenseQuery->sum('amount');
                
                // Get branch-specific income by type - with date filter
                // Remittance from drivers in this branch
                $branchRemittanceQuery = \App\Models\Transaction::where('type', \App\Models\Transaction::TYPE_DAILY_REMITTANCE)
                    ->where('status', \App\Models\Transaction::STATUS_SUCCESSFUL)
                    ->whereHas('driver', function($q) use ($branch) {
                        $q->where('branch_id', $branch->id);
                    });
                $this->applyDateFilter($branchRemittanceQuery, $dateFilter);
                $branchRemittance = $branchRemittanceQuery->sum('amount');
                
                // Charging from drivers in this branch
                $branchChargingQuery = \App\Models\ChargingRequest::where('status', \App\Models\ChargingRequest::STATUS_COMPLETED)
                    ->whereHas('driver', function($q) use ($branch) {
                        $q->where('branch_id', $branch->id);
                    });
                $this->applyDateFilter($branchChargingQuery, $dateFilter);
                $branchCharging = $branchChargingQuery->sum('charging_cost');
                
                // Maintenance from drivers in this branch
                $branchMaintenanceQuery = \App\Models\MaintenanceRequest::where('status', 'completed')
                    ->whereHas('driver', function($q) use ($branch) {
                        $q->where('branch_id', $branch->id);
                    });
                $this->applyDateFilter($branchMaintenanceQuery, $dateFilter);
                $branchMaintenanceRequests = $branchMaintenanceQuery->with('parts')->get();
                
                $branchMaintenance = 0;
                foreach ($branchMaintenanceRequests as $request) {
                    $branchMaintenance += $request->total_cost;
                }
                
                $branchBalance = $branchIncome - $branchExpense;
                $totalBranchIncome += $branchIncome;
                
                Log::info('Branch Balance Calculated', [
                    'branch_name' => $branch->name,
                    'income' => $branchIncome,
                    'expense' => $branchExpense,
                    'balance' => $branchBalance,
                    'remittance' => $branchRemittance,
                    'charging' => $branchCharging,
                    'maintenance' => $branchMaintenance,
                ]);
                
                $branchBalances[] = [
                    'branch_id' => $branch->id,
                    'branch_name' => $branch->name,
                    'income' => (float)$branchIncome,
                    'expense' => (float)$branchExpense,
                    'balance' => (float)$branchBalance,
                    'income_breakdown' => [
                        'remittance' => (float)$branchRemittance,
                        'charging' => (float)$branchCharging,
                        'maintenance' => (float)$branchMaintenance,
                    ]
                ];
            }
            
            // Sort by income descending
            usort($branchBalances, function($a, $b) {
                return $b['income'] <=> $a['income'];
            });
            
            // Pending Debit Requests Count
            $pendingRequests = AccountDebitRequest::pending()->count();
            
            // Total Approved Debit Requests (All Time)
            $totalApproved = AccountDebitRequest::approved()->count();
            
            // Total Rejected Debit Requests (All Time)
            $totalRejected = AccountDebitRequest::rejected()->count();
            
            // Filtered Period Income & Expense (respects date filter)
            // If no filter, show current month; otherwise show filtered period
            if ($dateFilter['start'] === null) {
                $currentMonth = now()->format('Y-m');
                $monthlyIncome = CompanyAccountTransaction::where('type', 'income')
                    ->whereRaw('DATE_FORMAT(transaction_date, "%Y-%m") = ?', [$currentMonth])
                    ->sum('amount');
                
                $monthlyExpense = CompanyAccountTransaction::where('type', 'expense')
                    ->whereRaw('DATE_FORMAT(transaction_date, "%Y-%m") = ?', [$currentMonth])
                    ->sum('amount');
            } else {
                // Use the same filtered period as the main balance
                $monthlyIncome = $totalIncome;
                $monthlyExpense = $totalExpense;
            }
            
            // Monthly income breakdown by type
            $monthlyRemittance = \App\Models\Transaction::where('type', \App\Models\Transaction::TYPE_DAILY_REMITTANCE)
                ->where('status', \App\Models\Transaction::STATUS_SUCCESSFUL)
                ->whereMonth('created_at', now()->month)
                ->whereYear('created_at', now()->year)
                ->sum('amount');
            
            $monthlyCharging = \App\Models\ChargingRequest::where('status', \App\Models\ChargingRequest::STATUS_COMPLETED)
                ->whereMonth('created_at', now()->month)
                ->whereYear('created_at', now()->year)
                ->sum('charging_cost');
            
            // Monthly maintenance cost from parts
            $monthlyMaintenanceRequests = \App\Models\MaintenanceRequest::where('status', 'completed')
                ->whereMonth('created_at', now()->month)
                ->whereYear('created_at', now()->year)
                ->with('parts')
                ->get();
            
            $monthlyMaintenance = 0;
            foreach ($monthlyMaintenanceRequests as $request) {
                $monthlyMaintenance += $request->total_cost;
            }
            
            // Last 6 Months Income Trend
            $monthlyTrend = [];
            for ($i = 5; $i >= 0; $i--) {
                $month = now()->subMonths($i)->format('Y-m');
                $monthName = now()->subMonths($i)->format('M Y');
                
                $income = CompanyAccountTransaction::where('type', 'income')
                    ->whereRaw('DATE_FORMAT(transaction_date, "%Y-%m") = ?', [$month])
                    ->sum('amount');
                
                $expense = CompanyAccountTransaction::where('type', 'expense')
                    ->whereRaw('DATE_FORMAT(transaction_date, "%Y-%m") = ?', [$month])
                    ->sum('amount');
                
                $monthlyTrend[] = [
                    'month' => $monthName,
                    'income' => $income,
                    'expense' => $expense,
                    'net' => $income - $expense
                ];
            }
            
            // Branch Income Breakdown (Top 10)
            $branchIncome = CompanyAccountTransaction::select('branch_id', DB::raw('SUM(amount) as total_income'))
                ->where('type', 'income')
                ->whereNotNull('branch_id')
                ->groupBy('branch_id')
                ->orderBy('total_income', 'desc')
                ->limit(10)
                ->with('branch:id,name')
                ->get()
                ->map(function($item) {
                    return [
                        'branch_name' => $item->branch->name ?? 'Unknown',
                        'total_income' => $item->total_income
                    ];
                });
            
            // Recent Transactions (Last 10)
            $recentTransactions = CompanyAccountTransaction::with(['branch:id,name', 'recordedBy:id,name'])
                ->orderBy('transaction_date', 'desc')
                ->orderBy('created_at', 'desc')
                ->limit(10)
                ->get()
                ->map(function($transaction) {
                    return [
                        'id' => $transaction->id,
                        'type' => $transaction->type,
                        'amount' => $transaction->amount,
                        'description' => $transaction->description,
                        'branch_name' => $transaction->branch->name ?? 'N/A',
                        'recorded_by' => $transaction->recordedBy->name ?? 'System',
                        'transaction_date' => $transaction->transaction_date->format('M d, Y'),
                        'created_at' => $transaction->created_at->format('M d, Y h:i A')
                    ];
                });
            
            Log::info('Dashboard Data Fetched Successfully', [
                'total_balance' => $balance,
                'total_branches' => count($branchBalances),
                'remittance_income' => $remittanceIncome,
                'charging_income' => $chargingIncome,
                'maintenance_income' => $maintenanceIncome,
                'total_branch_income' => $totalBranchIncome,
                'user_id' => auth()->id()
            ]);
            
            return response()->json([
                'success' => true,
                'data' => [
                    'balance' => (float)$balance,
                    'total_branch_income' => (float)$totalBranchIncome,
                    
                    // Income by Type
                    'income_breakdown' => [
                        'remittance' => (float)$remittanceIncome,
                        'charging' => (float)$chargingIncome,
                        'maintenance' => (float)$maintenanceIncome,
                        'total_service' => (float)$totalServiceIncome
                    ],
                    
                    // Monthly Income by Type
                    'monthly_income_breakdown' => [
                        'remittance' => (float)$monthlyRemittance,
                        'charging' => (float)$monthlyCharging,
                        'maintenance' => (float)$monthlyMaintenance,
                        'total' => (float)($monthlyRemittance + $monthlyCharging + $monthlyMaintenance)
                    ],
                    
                    // Branch Balances (All Branches)
                    'branch_balances' => $branchBalances,
                    
                    // Statistics
                    'pending_requests' => $pendingRequests,
                    'total_approved' => $totalApproved,
                    'total_rejected' => $totalRejected,
                    'monthly_income' => (float)$monthlyIncome,
                    'monthly_expense' => (float)$monthlyExpense,
                    'monthly_trend' => $monthlyTrend,
                    'branch_income' => $branchIncome,
                    'recent_transactions' => $recentTransactions
                ]
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to Fetch Dashboard Data', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'user_id' => auth()->id()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch dashboard data: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get Debit Requests with Filters
     */
    public function debitRequests(Request $request)
    {
        try {
            $status = $request->query('status'); // pending, approved, rejected
            $page = $request->query('page', 1);
            $perPage = $request->query('per_page', 20);
            
            Log::info('Fetching Debit Requests', [
                'status' => $status,
                'page' => $page,
                'per_page' => $perPage,
                'user_id' => auth()->id()
            ]);
            
            $query = AccountDebitRequest::with(['branch:id,name', 'requester:id,name', 'reviewer:id,name'])
                ->orderBy('created_at', 'desc');
            
            if ($status && in_array($status, ['pending', 'approved', 'rejected'])) {
                $query->where('status', $status);
            }
            
            $debitRequests = $query->paginate($perPage, ['*'], 'page', $page);
            
            Log::info('Debit Requests Fetched Successfully', [
                'count' => $debitRequests->total(),
                'page' => $debitRequests->currentPage(),
                'user_id' => auth()->id()
            ]);
            
            return response()->json([
                'success' => true,
                'data' => $debitRequests->map(function($request) {
                    return [
                        'id' => $request->id,
                        'amount' => $request->amount,
                        'description' => $request->description,
                        'status' => $request->status,
                        'branch_name' => $request->branch->name ?? 'N/A',
                        'requester_name' => $request->requester->name,
                        'reviewer_name' => $request->reviewer->name ?? null,
                        'receipt_path' => $request->receipt_path,
                        'review_notes' => $request->approval_notes,
                        'created_at' => $request->created_at->format('M d, Y h:i A'),
                        'reviewed_at' => $request->approved_at ? $request->approved_at->format('M d, Y h:i A') : null
                    ];
                }),
                'meta' => [
                    'current_page' => $debitRequests->currentPage(),
                    'per_page' => $debitRequests->perPage(),
                    'total' => $debitRequests->total(),
                    'last_page' => $debitRequests->lastPage()
                ]
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to Fetch Debit Requests', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'user_id' => auth()->id()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch debit requests: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get Single Debit Request Details
     */
    public function showDebitRequest($id)
    {
        try {
            Log::info('Fetching Debit Request Details', [
                'request_id' => $id,
                'user_id' => auth()->id()
            ]);
            
            $request = AccountDebitRequest::with(['branch', 'requester', 'reviewer'])
                ->findOrFail($id);
            
            return response()->json([
                'success' => true,
                'data' => [
                    'id' => $request->id,
                    'amount' => $request->amount,
                    'description' => $request->description,
                    'status' => $request->status,
                    'branch' => [
                        'id' => $request->branch->id,
                        'name' => $request->branch->name,
                        'location' => $request->branch->location
                    ],
                    'requester' => [
                        'id' => $request->requester->id,
                        'name' => $request->requester->name,
                        'email' => $request->requester->email,
                        'role' => $request->requester->role
                    ],
                    'reviewer' => $request->reviewer ? [
                        'id' => $request->reviewer->id,
                        'name' => $request->reviewer->name,
                        'role' => $request->reviewer->role
                    ] : null,
                    'receipt_path' => $request->receipt_path,
                    'receipt_url' => $request->receipt_path ? asset('storage/' . $request->receipt_path) : null,
                    'review_notes' => $request->approval_notes,
                    'created_at' => $request->created_at->format('M d, Y h:i A'),
                    'reviewed_at' => $request->approved_at ? $request->approved_at->format('M d, Y h:i A') : null
                ]
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to Fetch Debit Request Details', [
                'request_id' => $id,
                'error' => $e->getMessage(),
                'user_id' => auth()->id()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Debit request not found'
            ], 404);
        }
    }

    /**
     * Approve or Reject Debit Request
     */
    public function reviewDebitRequest(Request $request, $id)
    {
        $request->validate([
            'action' => 'required|in:approve,reject',
            'notes' => 'nullable|string|max:1000'
        ]);

        try {
            Log::info('Reviewing Debit Request', [
                'request_id' => $id,
                'action' => $request->input('action'),
                'user_id' => auth()->id()
            ]);
            
            DB::beginTransaction();

            $debitRequest = AccountDebitRequest::with(['branch', 'requester'])
                ->findOrFail($id);
            
            // Check if already reviewed
            if ($debitRequest->status !== 'pending') {
                Log::warning('Debit Request Already Reviewed', [
                    'request_id' => $id,
                    'current_status' => $debitRequest->status,
                    'user_id' => auth()->id()
                ]);
                
                return response()->json([
                    'success' => false,
                    'message' => 'This request has already been reviewed'
                ], 400);
            }

            $action = $request->input('action');
            $notes = $request->input('notes');

            if ($action === 'approve') {
                // Check if company has enough balance
                $balance = CompanyAccountTransaction::where('type', 'income')->sum('amount') 
                    - CompanyAccountTransaction::where('type', 'expense')->sum('amount');

                if ($balance < $debitRequest->amount) {
                    Log::warning('Insufficient Balance for Debit Request Approval', [
                        'request_id' => $id,
                        'request_amount' => $debitRequest->amount,
                        'company_balance' => $balance,
                        'user_id' => auth()->id()
                    ]);
                    
                    return response()->json([
                        'success' => false,
                        'message' => 'Insufficient company balance to approve this request'
                    ], 400);
                }

                // Approve the request
                $debitRequest->status = 'approved';
                $debitRequest->approved_by = Auth::id();
                $debitRequest->approved_at = now();
                $debitRequest->approval_notes = $notes;
                $debitRequest->save();

                // Create expense transaction
                CompanyAccountTransaction::create([
                    'type' => 'expense',
                    'amount' => $debitRequest->amount,
                    'description' => 'Debit Request #' . $debitRequest->id . ': ' . $debitRequest->description,
                    'branch_id' => $debitRequest->branch_id,
                    'recorded_by' => Auth::id(),
                    'transaction_date' => now(),
                    'reference_type' => 'App\Models\AccountDebitRequest',
                    'reference_id' => $debitRequest->id
                ]);

                $message = 'Debit request approved successfully';
            } else {
                // Reject the request
                $debitRequest->status = 'rejected';
                $debitRequest->approved_by = Auth::id();
                $debitRequest->approved_at = now();
                $debitRequest->approval_notes = $notes ?? 'Request rejected';
                $debitRequest->save();

                $message = 'Debit request rejected';
            }

            DB::commit();
            
            Log::info('Debit Request Reviewed Successfully', [
                'request_id' => $id,
                'action' => $action,
                'new_status' => $debitRequest->status,
                'amount' => $debitRequest->amount,
                'user_id' => auth()->id()
            ]);

            return response()->json([
                'success' => true,
                'message' => $message,
                'data' => [
                    'id' => $debitRequest->id,
                    'status' => $debitRequest->status,
                    'reviewed_at' => $debitRequest->approved_at->format('M d, Y h:i A')
                ]
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            
            Log::error('Failed to Review Debit Request', [
                'request_id' => $id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'user_id' => auth()->id()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to review request: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get Branch Income Breakdown
     */
    public function branchIncome(Request $request)
    {
        try {
            $startDate = $request->query('start_date');
            $endDate = $request->query('end_date');
            
            Log::info('Fetching Branch Income', [
                'start_date' => $startDate,
                'end_date' => $endDate,
                'user_id' => auth()->id()
            ]);
            
            $query = CompanyAccountTransaction::select(
                    'branch_id',
                    DB::raw('SUM(CASE WHEN type = "income" THEN amount ELSE 0 END) as total_income'),
                    DB::raw('SUM(CASE WHEN type = "expense" THEN amount ELSE 0 END) as total_expense'),
                    DB::raw('COUNT(*) as transaction_count')
                )
                ->whereNotNull('branch_id')
                ->groupBy('branch_id');
            
            if ($startDate) {
                $query->whereDate('transaction_date', '>=', $startDate);
            }
            
            if ($endDate) {
                $query->whereDate('transaction_date', '<=', $endDate);
            }
            
            $branchStats = $query->with('branch:id,name,location')
                ->get()
                ->map(function($stat) {
                    $netIncome = $stat->total_income - $stat->total_expense;
                    return [
                        'branch_id' => $stat->branch_id,
                        'branch_name' => $stat->branch->name ?? 'Unknown',
                        'branch_location' => $stat->branch->location ?? 'N/A',
                        'total_income' => $stat->total_income,
                        'total_expense' => $stat->total_expense,
                        'net_income' => $netIncome,
                        'transaction_count' => $stat->transaction_count
                    ];
                })
                ->sortByDesc('total_income')
                ->values();
            
            Log::info('Branch Income Fetched Successfully', [
                'branch_count' => $branchStats->count(),
                'user_id' => auth()->id()
            ]);
            
            return response()->json([
                'success' => true,
                'data' => $branchStats
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to Fetch Branch Income', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'user_id' => auth()->id()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch branch income: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get All Branches
     */
    public function branches()
    {
        try {
            Log::info('Fetching All Branches', [
                'user_id' => auth()->id()
            ]);
            
            $branches = Branch::select('id', 'name', 'location', 'phone', 'email')
                ->orderBy('name')
                ->get();
            
            Log::info('Branches Fetched Successfully', [
                'count' => $branches->count(),
                'user_id' => auth()->id()
            ]);
            
            return response()->json([
                'success' => true,
                'data' => $branches
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to Fetch Branches', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'user_id' => auth()->id()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch branches: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get User Profile
     */
    public function profile()
    {
        $user = Auth::user();
        
        Log::info('Fetching User Profile', [
            'user_id' => $user->id
        ]);
        
        return response()->json([
            'success' => true,
            'data' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'phone' => $user->phone,
                'role' => $user->role,
                'branch_id' => $user->branch_id,
                'branch_name' => $user->branch->name ?? 'N/A'
            ]
        ]);
    }

    /**
     * Get Active Drivers
     */
    public function getActiveDrivers(Request $request)
    {
        try {
            $search = $request->query('search');
            $branchId = $request->query('branch_id');
            
            Log::info('Fetching Active Drivers', [
                'search' => $search,
                'branch_id' => $branchId,
                'user_id' => auth()->id()
            ]);
            
            $query = \App\Models\Driver::with(['user:id,name,email', 'branch:id,name', 'wallet', 'vehicleAssignments' => function($q) {
                $q->whereNull('returned_at')->with('vehicle:id,plate_number');
            }]);
            
            if ($search) {
                $query->where(function($q) use ($search) {
                    $q->where('first_name', 'like', "%{$search}%")
                      ->orWhere('last_name', 'like', "%{$search}%")
                      ->orWhere('phone_number', 'like', "%{$search}%")
                      ->orWhereHas('user', function($q) use ($search) {
                          $q->where('email', 'like', "%{$search}%");
                      });
                });
            }
            
            if ($branchId) {
                $query->where('branch_id', $branchId);
            }
            
            $drivers = $query->orderBy('first_name')->get()->map(function($driver) {
                $currentVehicle = $driver->vehicleAssignments->first();
                
                return [
                    'id' => $driver->id,
                    'name' => $driver->full_name,
                    'email' => $driver->user->email ?? 'N/A',
                    'phone' => $driver->phone_number,
                    'branch_name' => $driver->branch->name ?? 'N/A',
                    'vehicle_plate' => $currentVehicle ? $currentVehicle->vehicle->plate_number : 'No Vehicle',
                    'wallet_balance' => $driver->wallet ? $driver->wallet->balance : 0,
                    'is_active' => true
                ];
            });
            
            Log::info('Active Drivers Fetched Successfully', [
                'count' => $drivers->count(),
                'user_id' => auth()->id()
            ]);
            
            return response()->json([
                'success' => true,
                'data' => $drivers
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to Fetch Active Drivers', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'user_id' => auth()->id()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch active drivers: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get Active Vehicles
     */
    public function getActiveVehicles(Request $request)
    {
        try {
            $branchId = $request->query('branch_id');
            
            Log::info('Fetching Active Vehicles', [
                'branch_id' => $branchId,
                'user_id' => auth()->id()
            ]);
            
            $query = \App\Models\Vehicle::with([
                'branch:id,name',
                'currentAssignment.driver' => function($q) {
                    $q->select('id', 'first_name', 'last_name');
                }
            ]);
            
            if ($branchId) {
                $query->where('branch_id', $branchId);
            }
            
            $vehicles = $query->orderBy('plate_number')->get()->map(function($vehicle) {
                $assignment = $vehicle->currentAssignment;
                $driver = $assignment ? $assignment->driver : null;
                
                return [
                    'id' => (int)$vehicle->id,
                    'plate_number' => (string)$vehicle->plate_number,
                    'model' => $vehicle->make . ' ' . $vehicle->model,
                    'year' => (int)date('Y'), // Return as integer
                    'driver_name' => $driver ? $driver->full_name : 'Unassigned',
                    'branch_name' => $vehicle->branch->name ?? 'N/A',
                    'battery_capacity' => 100.0, // Return as double
                    'current_mileage' => 0.0, // Return as double
                    'status' => $assignment ? 'Active' : 'Available'
                ];
            });
            
            Log::info('Active Vehicles Fetched Successfully', [
                'count' => $vehicles->count(),
                'user_id' => auth()->id()
            ]);
            
            return response()->json([
                'success' => true,
                'data' => $vehicles
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to Fetch Active Vehicles', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'user_id' => auth()->id()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch active vehicles: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get Remittance Overview
     */
    public function getRemittanceOverview(Request $request)
    {
        try {
            $status = $request->query('status'); // pending, paid, overdue
            
            Log::info('Fetching Remittance Overview', [
                'status' => $status,
                'user_id' => auth()->id()
            ]);
            
            // Use Transaction model for daily remittances
            $query = \App\Models\Transaction::with(['driver.user', 'driver.branch'])
                ->where('type', \App\Models\Transaction::TYPE_DAILY_REMITTANCE)
                ->orderBy('created_at', 'desc');
            
            if ($status === 'pending') {
                $query->where('status', \App\Models\Transaction::STATUS_PENDING)
                      ->whereNull('payment_proof');
            } elseif ($status === 'paid') {
                $query->where('status', \App\Models\Transaction::STATUS_SUCCESSFUL);
            } elseif ($status === 'overdue') {
                $query->where('status', \App\Models\Transaction::STATUS_PENDING)
                      ->whereNull('payment_proof')
                      ->where('created_at', '<', now()->subDays(1));
            }
            
            $remittances = $query->limit(100)->get()->map(function($transaction) {
                $driver = $transaction->driver;
                $isPending = $transaction->status === \App\Models\Transaction::STATUS_PENDING && !$transaction->payment_proof;
                $daysOverdue = $isPending ? now()->diffInDays($transaction->created_at) : 0;
                $isOverdue = $daysOverdue > 1;
                
                return [
                    'id' => $transaction->id,
                    'driver_name' => $driver ? $driver->full_name : 'N/A',
                    'driver_phone' => $driver ? $driver->phone_number : 'N/A',
                    'branch_name' => $driver && $driver->branch ? $driver->branch->name : 'N/A',
                    'amount' => (float)$transaction->amount,
                    'date' => $transaction->created_at->format('M d, Y'),
                    'due_date' => $transaction->created_at->addDay()->format('M d, Y'),
                    'status' => $transaction->payment_proof ? ($transaction->status === \App\Models\Transaction::STATUS_SUCCESSFUL ? 'paid' : 'submitted') : 'pending',
                    'is_overdue' => $isOverdue,
                    'days_overdue' => $isOverdue ? $daysOverdue : 0
                ];
            });
            
            Log::info('Remittance Overview Fetched Successfully', [
                'count' => $remittances->count(),
                'user_id' => auth()->id()
            ]);
            
            return response()->json([
                'success' => true,
                'data' => $remittances
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to Fetch Remittance Overview', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'user_id' => auth()->id()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch remittance data: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get Drivers with Overdue Remittance
     */
    public function getOverdueDrivers()
    {
        try {
            Log::info('Fetching Overdue Drivers', [
                'user_id' => auth()->id()
            ]);
            
            // Get overdue remittances grouped by driver
            $overdueTransactions = \App\Models\Transaction::with(['driver.user', 'driver.branch'])
                ->where('type', \App\Models\Transaction::TYPE_DAILY_REMITTANCE)
                ->where('status', \App\Models\Transaction::STATUS_PENDING)
                ->whereNull('payment_proof')
                ->where('created_at', '<', now()->subDays(1))
                ->get()
                ->groupBy('driver_id');
            
            $overdueDrivers = $overdueTransactions->map(function($transactions, $driverId) {
                $driver = $transactions->first()->driver;
                
                return [
                    'driver_id' => $driverId,
                    'driver_name' => $driver ? $driver->full_name : 'N/A',
                    'driver_phone' => $driver ? $driver->phone_number : 'N/A',
                    'driver_email' => $driver && $driver->user ? $driver->user->email : 'N/A',
                    'branch_name' => $driver && $driver->branch ? $driver->branch->name : 'N/A',
                    'overdue_count' => $transactions->count(),
                    'total_overdue' => $transactions->sum('amount')
                ];
            })->values();
            
            Log::info('Overdue Drivers Fetched Successfully', [
                'count' => $overdueDrivers->count(),
                'user_id' => auth()->id()
            ]);
            
            return response()->json([
                'success' => true,
                'data' => $overdueDrivers
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to Fetch Overdue Drivers', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'user_id' => auth()->id()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch overdue drivers: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get Charging History
     */
    public function getChargingHistory(Request $request)
    {
        try {
            $status = $request->query('status');
            $driverId = $request->query('driver_id');
            $startDate = $request->query('start_date');
            $endDate = $request->query('end_date');
            
            Log::info('Fetching Charging History', [
                'status' => $status,
                'driver_id' => $driverId,
                'start_date' => $startDate,
                'end_date' => $endDate,
                'user_id' => auth()->id()
            ]);
            
            $query = \App\Models\ChargingRequest::with(['driver:id,first_name,last_name,branch_id', 
                                                          'driver.branch:id,name',
                                                          'vehicle:id,plate_number', 
                                                          'approver:id,name'])
                ->orderBy('created_at', 'desc');
            
            if ($status) {
                $query->where('status', $status);
            }
            
            if ($driverId) {
                $query->where('driver_id', $driverId);
            }
            
            if ($startDate) {
                $query->whereDate('created_at', '>=', $startDate);
            }
            
            if ($endDate) {
                $query->whereDate('created_at', '<=', $endDate);
            }
            
            $history = $query->limit(100)->get()->map(function($charge) {
                return [
                    'id' => $charge->id,
                    'driver_name' => $charge->driver ? $charge->driver->full_name : 'N/A',
                    'vehicle_plate' => $charge->vehicle->plate_number,
                    'branch_name' => $charge->driver && $charge->driver->branch ? $charge->driver->branch->name : 'N/A',
                    'battery_level_before' => $charge->battery_level_before,
                    'battery_level_after' => $charge->battery_level_after ?? null,
                    'energy_consumed' => $charge->energy_consumed ?? null,
                    'cost' => $charge->charging_cost,
                    'status' => $charge->status,
                    'approved_by' => $charge->approver->name ?? null,
                    'created_at' => $charge->created_at->format('M d, Y h:i A'),
                    'completed_at' => $charge->charging_end ? $charge->charging_end->format('M d, Y h:i A') : null
                ];
            });
            
            Log::info('Charging History Fetched Successfully', [
                'count' => $history->count(),
                'user_id' => auth()->id()
            ]);
            
            return response()->json([
                'success' => true,
                'data' => $history
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to Fetch Charging History', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'user_id' => auth()->id()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch charging history: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get Maintenance History
     */
    public function getMaintenanceHistory(Request $request)
    {
        try {
            $status = $request->query('status');
            $vehicleId = $request->query('vehicle_id');
            $startDate = $request->query('start_date');
            $endDate = $request->query('end_date');
            
            Log::info('Fetching Maintenance History', [
                'status' => $status,
                'vehicle_id' => $vehicleId,
                'start_date' => $startDate,
                'end_date' => $endDate,
                'user_id' => auth()->id()
            ]);
            
            $query = \App\Models\MaintenanceRequest::with(['parts',
                                                            'driver:id,first_name,last_name,branch_id',
                                                            'driver.branch:id,name',
                                                            'mechanic:id,name'])
                ->orderBy('created_at', 'desc');
            
            if ($status) {
                $query->where('status', $status);
            }
            
            if ($vehicleId) {
                $query->where('vehicle_id', $vehicleId);
            }
            
            if ($startDate) {
                $query->whereDate('created_at', '>=', $startDate);
            }
            
            if ($endDate) {
                $query->whereDate('created_at', '<=', $endDate);
            }
            
            $history = $query->limit(100)->get()->map(function($maintenance) {
                // Get vehicle through driver relationship
                $vehicle = $maintenance->driver?->vehicleAssignments()->with('vehicle')->latest()->first()?->vehicle;
                
                // Get parts with details
                $parts = $maintenance->parts->map(function($part) {
                    return [
                        'id' => $part->id,
                        'name' => $part->name,
                        'sku' => $part->sku,
                        'quantity' => $part->pivot->quantity,
                        'unit_cost' => $part->pivot->unit_cost,
                        'total_cost' => $part->pivot->total_cost,
                    ];
                });
                
                return [
                    'id' => $maintenance->id,
                    'vehicle_plate' => $vehicle?->plate_number ?? 'N/A',
                    'driver_name' => $maintenance->driver ? $maintenance->driver->full_name : 'N/A',
                    'mechanic_name' => $maintenance->mechanic->name ?? 'Not Assigned',
                    'branch_name' => $maintenance->driver && $maintenance->driver->branch ? $maintenance->driver->branch->name : 'N/A',
                    'description' => $maintenance->issue_description ?? 'No description',
                    'cost' => $maintenance->total_cost, // Uses accessor that sums parts
                    'parts' => $parts,
                    'parts_count' => $parts->count(),
                    'status' => $maintenance->status,
                    'urgency' => 'normal', // Default urgency since it's not in the schema
                    'created_at' => $maintenance->created_at->format('M d, Y h:i A'),
                    'completed_at' => $maintenance->completed_at ? $maintenance->completed_at->format('M d, Y h:i A') : null
                ];
            });
            
            Log::info('Maintenance History Fetched Successfully', [
                'count' => $history->count(),
                'user_id' => auth()->id()
            ]);
            
            return response()->json([
                'success' => true,
                'data' => $history
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to Fetch Maintenance History', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'user_id' => auth()->id()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch maintenance history: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get Statistics Summary for Dashboard
     */
    public function getStatistics()
    {
        try {
            Log::info('Fetching Statistics Summary', [
                'user_id' => auth()->id()
            ]);
            
            $stats = [
                'total_drivers' => \App\Models\Driver::where('is_active', true)->count(),
                'total_vehicles' => \App\Models\Vehicle::where('is_active', true)->count(),
                'pending_remittance' => \App\Models\DailyRemittance::where('status', 'pending')->count(),
                'overdue_remittance' => \App\Models\DailyRemittance::where('status', 'pending')
                    ->where('due_date', '<', now())->count(),
                'pending_maintenance' => \App\Models\MaintenanceRequest::where('status', 'pending')->count(),
                'pending_charging' => \App\Models\ChargingRequest::where('status', 'pending')->count(),
                'total_branches' => \App\Models\Branch::count(),
            ];
            
            Log::info('Statistics Fetched Successfully', [
                'total_drivers' => $stats['total_drivers'],
                'total_vehicles' => $stats['total_vehicles'],
                'user_id' => auth()->id()
            ]);
            
            return response()->json([
                'success' => true,
                'data' => $stats
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to Fetch Statistics', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'user_id' => auth()->id()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch statistics: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Calculate date filter based on filter type
     */
    private function calculateDateFilter($filterType, $startDate, $endDate, $selectedMonth, $selectedYear)
    {
        $filter = ['start' => null, 'end' => null];
        
        switch ($filterType) {
            case 'daily':
                $filter['start'] = now()->startOfDay();
                $filter['end'] = now()->endOfDay();
                break;
                
            case 'weekly':
                $filter['start'] = now()->startOfWeek();
                $filter['end'] = now()->endOfWeek();
                break;
                
            case 'last_week':
                $filter['start'] = now()->subWeek()->startOfWeek();
                $filter['end'] = now()->subWeek()->endOfWeek();
                break;
                
            case 'monthly':
                $filter['start'] = now()->startOfMonth();
                $filter['end'] = now()->endOfMonth();
                break;
                
            case 'last_month':
                $filter['start'] = now()->subMonth()->startOfMonth();
                $filter['end'] = now()->subMonth()->endOfMonth();
                break;
                
            case 'yearly':
                $filter['start'] = now()->startOfYear();
                $filter['end'] = now()->endOfYear();
                break;
                
            case 'last_year':
                $filter['start'] = now()->subYear()->startOfYear();
                $filter['end'] = now()->subYear()->endOfYear();
                break;
                
            case 'month_year':
                if ($selectedMonth && $selectedYear) {
                    $filter['start'] = now()->setYear($selectedYear)->setMonth($selectedMonth)->startOfMonth();
                    $filter['end'] = now()->setYear($selectedYear)->setMonth($selectedMonth)->endOfMonth();
                }
                break;
                
            case 'custom':
                if ($startDate && $endDate) {
                    $filter['start'] = \Carbon\Carbon::parse($startDate)->startOfDay();
                    $filter['end'] = \Carbon\Carbon::parse($endDate)->endOfDay();
                }
                break;
                
            default: // 'all'
                $filter['start'] = null;
                $filter['end'] = null;
                break;
        }
        
        return $filter;
    }

    /**
     * Apply date filter to query
     */
    private function applyDateFilter($query, $dateFilter, $column = 'created_at')
    {
        if ($dateFilter['start'] && $dateFilter['end']) {
            $query->whereBetween($column, [$dateFilter['start'], $dateFilter['end']]);
        }
        return $query;
    }

    /**
     * Get driver activity summary
     */
    public function getDriverActivitySummary($driverId)
    {
        try {
            Log::info('Fetching Driver Activity Summary', [
                'driver_id' => $driverId,
                'user_id' => auth()->id()
            ]);
            
            $driver = \App\Models\Driver::findOrFail($driverId);
            
            // Get total remittance
            $totalRemittance = \App\Models\Transaction::where('driver_id', $driverId)
                ->where('type', \App\Models\Transaction::TYPE_DAILY_REMITTANCE)
                ->where('status', \App\Models\Transaction::STATUS_SUCCESSFUL)
                ->sum('amount');
            
            $remittanceCount = \App\Models\Transaction::where('driver_id', $driverId)
                ->where('type', \App\Models\Transaction::TYPE_DAILY_REMITTANCE)
                ->where('status', \App\Models\Transaction::STATUS_SUCCESSFUL)
                ->count();
            
            // Get total charging
            $totalCharging = \App\Models\ChargingRequest::where('driver_id', $driverId)
                ->where('status', \App\Models\ChargingRequest::STATUS_COMPLETED)
                ->sum('charging_cost');
            
            $chargingCount = \App\Models\ChargingRequest::where('driver_id', $driverId)
                ->where('status', \App\Models\ChargingRequest::STATUS_COMPLETED)
                ->count();
            
            // Get total maintenance
            $maintenanceRequests = \App\Models\MaintenanceRequest::where('driver_id', $driverId)
                ->where('status', 'completed')
                ->with('parts')
                ->get();
            
            $totalMaintenance = 0;
            foreach ($maintenanceRequests as $request) {
                $totalMaintenance += $request->total_cost;
            }
            
            $maintenanceCount = $maintenanceRequests->count();
            
            Log::info('Driver Activity Summary Fetched Successfully', [
                'driver_id' => $driverId,
                'total_remittance' => $totalRemittance,
                'total_charging' => $totalCharging,
                'total_maintenance' => $totalMaintenance,
                'user_id' => auth()->id()
            ]);
            
            return response()->json([
                'success' => true,
                'data' => [
                    'total_remittance' => (float)$totalRemittance,
                    'remittance_count' => $remittanceCount,
                    'total_charging' => (float)$totalCharging,
                    'charging_count' => $chargingCount,
                    'total_maintenance' => (float)$totalMaintenance,
                    'maintenance_count' => $maintenanceCount,
                ]
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to Fetch Driver Activity Summary', [
                'driver_id' => $driverId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'user_id' => auth()->id()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch driver activity summary',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get driver activities by type
     */
    public function getDriverActivities(Request $request, $driverId)
    {
        try {
            $type = $request->query('type', 'remittance');
            
            Log::info('Fetching Driver Activities', [
                'driver_id' => $driverId,
                'type' => $type,
                'user_id' => auth()->id()
            ]);
            
            $driver = \App\Models\Driver::findOrFail($driverId);
            
            $activities = [];
            
            switch ($type) {
                case 'remittance':
                    $transactions = \App\Models\Transaction::where('driver_id', $driverId)
                        ->where('type', \App\Models\Transaction::TYPE_DAILY_REMITTANCE)
                        ->orderBy('created_at', 'desc')
                        ->get();
                    
                    foreach ($transactions as $transaction) {
                        $activities[] = [
                            'id' => $transaction->id,
                            'amount' => (float)$transaction->amount,
                            'status' => ucfirst($transaction->status),
                            'date' => $transaction->created_at->format('M d, Y h:i A'),
                            'created_at' => $transaction->created_at->format('M d, Y'),
                            'description' => $transaction->description ?? 'Daily remittance payment',
                        ];
                    }
                    break;
                    
                case 'charging':
                    $chargingRequests = \App\Models\ChargingRequest::where('driver_id', $driverId)
                        ->orderBy('created_at', 'desc')
                        ->get();
                    
                    foreach ($chargingRequests as $request) {
                        $activities[] = [
                            'id' => $request->id,
                            'amount' => (float)$request->charging_cost,
                            'status' => ucfirst($request->status),
                            'date' => $request->created_at->format('M d, Y h:i A'),
                            'created_at' => $request->created_at->format('M d, Y'),
                            'description' => "Battery charged: {$request->percentage_charged}%",
                        ];
                    }
                    break;
                    
                case 'maintenance':
                    $maintenanceRequests = \App\Models\MaintenanceRequest::where('driver_id', $driverId)
                        ->with('parts')
                        ->orderBy('created_at', 'desc')
                        ->get();
                    
                    foreach ($maintenanceRequests as $request) {
                        $parts = [];
                        foreach ($request->parts as $part) {
                            $parts[] = [
                                'name' => $part->name,
                                'quantity' => $part->pivot->quantity,
                                'cost' => (float)$part->pivot->cost,
                            ];
                        }
                        
                        $activities[] = [
                            'id' => $request->id,
                            'amount' => (float)$request->total_cost,
                            'status' => ucfirst($request->status),
                            'date' => $request->created_at->format('M d, Y h:i A'),
                            'created_at' => $request->created_at->format('M d, Y'),
                            'description' => $request->issue_description ?? 'Maintenance service',
                            'parts' => $parts,
                        ];
                    }
                    break;
            }
            
            Log::info('Driver Activities Fetched Successfully', [
                'driver_id' => $driverId,
                'type' => $type,
                'count' => count($activities),
                'user_id' => auth()->id()
            ]);
            
            return response()->json([
                'success' => true,
                'data' => $activities
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to Fetch Driver Activities', [
                'driver_id' => $driverId,
                'type' => $request->query('type', 'remittance'),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'user_id' => auth()->id()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch driver activities',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Change Password
     */
    public function changePassword(Request $request)
    {
        try {
            Log::info('Password Change Request', [
                'user_id' => auth()->id()
            ]);

            $request->validate([
                'current_password' => 'required',
                'new_password' => 'required|min:6',
                'new_password_confirmation' => 'required|same:new_password',
            ]);

            $user = auth()->user();

            // Verify current password
            if (!Hash::check($request->current_password, $user->password)) {
                Log::warning('Password Change Failed - Invalid Current Password', [
                    'user_id' => $user->id
                ]);

                return response()->json([
                    'success' => false,
                    'message' => 'Current password is incorrect'
                ], 400);
            }

            // Update password
            $user->password = Hash::make($request->new_password);
            $user->save();

            Log::info('Password Changed Successfully', [
                'user_id' => $user->id
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Password changed successfully'
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to Change Password', [
                'error' => $e->getMessage(),
                'user_id' => auth()->id()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to change password',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get Store Inventory (Real Parts from Database)
     */
    public function getStoreInventory()
    {
        try {
            Log::info('Store Inventory Request', [
                'user_id' => auth()->id(),
                'branch_id' => auth()->user()->branch_id
            ]);

            $user = auth()->user();
            $lowStockThreshold = 10; // Parts with stock < 10 are considered low

            // Get all parts - filter by user's accessible branches
            $partsQuery = \App\Models\Part::with(['stock', 'branch']);
            
            if (!$user->hasRole(['Super Admin', 'Accountant'])) {
                BranchAccessService::applyBranchFilter($partsQuery, $user);
            }
            
            $parts = $partsQuery->get();

            // Group parts by category (based on part name patterns)
            $categorizedParts = [
                'Batteries' => ['icon' => 'battery_charging_full', 'color' => '#4C6EF5', 'items' => []],
                'Tires' => ['icon' => 'trip_origin', 'color' => '#38D9A9', 'items' => []],
                'Chargers' => ['icon' => 'power', 'color' => '#FF8C42', 'items' => []],
                'Tools & Parts' => ['icon' => 'build_circle', 'color' => '#9775FA', 'items' => []],
                'Accessories' => ['icon' => 'auto_awesome', 'color' => '#EC4899', 'items' => []],
            ];

            $totalItems = 0;
            $lowStockCount = 0;

            foreach ($parts as $part) {
                // Get stock quantity based on accessible branches
                $branchIds = BranchAccessService::getUserBranchIds($user);
                if (!$user->hasRole(['Super Admin', 'Accountant']) && !empty($branchIds)) {
                    $stockQuantity = $part->stock->whereIn('branch_id', $branchIds)->sum('quantity');
                } else {
                    $stockQuantity = $part->getTotalStock();
                }
                
                $isLowStock = $stockQuantity < $lowStockThreshold;
                
                if ($isLowStock) {
                    $lowStockCount++;
                }

                $partData = [
                    'id' => $part->id,
                    'name' => $part->name,
                    'sku' => $part->sku,
                    'description' => $part->description,
                    'stock' => $stockQuantity,
                    'price' => (float) $part->cost,
                    'low_stock' => $isLowStock,
                    'picture' => $part->picture ? asset('storage/' . $part->picture) : null,
                    'branch_name' => $part->branch ? $part->branch->name : 'N/A',
                ];

                // Categorize based on part name
                $partName = strtolower($part->name);
                if (str_contains($partName, 'battery') || str_contains($partName, 'batt')) {
                    $categorizedParts['Batteries']['items'][] = $partData;
                } elseif (str_contains($partName, 'tire') || str_contains($partName, 'wheel')) {
                    $categorizedParts['Tires']['items'][] = $partData;
                } elseif (str_contains($partName, 'charger') || str_contains($partName, 'charging')) {
                    $categorizedParts['Chargers']['items'][] = $partData;
                } elseif (str_contains($partName, 'light') || str_contains($partName, 'mirror') || 
                          str_contains($partName, 'seat') || str_contains($partName, 'cover')) {
                    $categorizedParts['Accessories']['items'][] = $partData;
                } else {
                    $categorizedParts['Tools & Parts']['items'][] = $partData;
                }

                $totalItems++;
            }

            // Build categories array (only include categories with items)
            $categories = [];
            foreach ($categorizedParts as $categoryName => $categoryData) {
                if (!empty($categoryData['items'])) {
                    $categories[] = [
                        'name' => $categoryName,
                        'icon' => $categoryData['icon'],
                        'color' => $categoryData['color'],
                        'items' => $categoryData['items'],
                    ];
                }
            }

            $inventory = [
                'total_items' => $totalItems,
                'low_stock_count' => $lowStockCount,
                'categories' => $categories,
            ];

            Log::info('Store Inventory Retrieved Successfully', [
                'total_items' => $totalItems,
                'low_stock_count' => $lowStockCount,
                'categories' => count($categories),
                'user_id' => auth()->id()
            ]);

            return response()->json([
                'success' => true,
                'data' => $inventory
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to Retrieve Store Inventory', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'user_id' => auth()->id()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve store inventory',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
