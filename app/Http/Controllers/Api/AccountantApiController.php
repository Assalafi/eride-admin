<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\CompanyAccountTransaction;
use App\Models\AccountDebitRequest;
use App\Models\Branch;
use App\Models\SystemSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class AccountantApiController extends Controller
{
    /**
     * Login
     */
    public function login(Request $request)
    {
        Log::info('🔐 Accountant Login Attempt', [
            'email' => $request->email,
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent()
        ]);

        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'password' => 'required',
        ]);

        if ($validator->fails()) {
            Log::warning('❌ Login Validation Failed', [
                'email' => $request->email,
                'errors' => $validator->errors()->toArray()
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        $user = User::where('email', $request->email)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            Log::warning('❌ Invalid Credentials', ['email' => $request->email]);
            return response()->json([
                'success' => false,
                'message' => 'Invalid credentials'
            ], 401);
        }

        // Check if user has Accountant role
        if (!$user->hasRole('Accountant')) {
            Log::warning('🚫 Unauthorized Login Attempt - Not Accountant', [
                'user_id' => $user->id,
                'email' => $user->email,
                'roles' => $user->roles->pluck('name')
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized. Only accountants can access this app.'
            ], 403);
        }

        // Create token
        $token = $user->createToken('accountant-app')->plainTextToken;

        Log::info('✅ Login Successful', [
            'user_id' => $user->id,
            'email' => $user->email,
            'branch_id' => $user->branch_id
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Login successful',
            'data' => [
                'token' => $token,
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'phone' => $user->phone,
                    'role' => 'Accountant',
                    'branch_id' => $user->branch_id,
                ]
            ]
        ], 200);
    }

    /**
     * Logout
     */
    public function logout(Request $request)
    {
        Log::info('👋 Accountant Logout', [
            'user_id' => $request->user()->id,
            'email' => $request->user()->email
        ]);

        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'success' => true,
            'message' => 'Logged out successfully'
        ], 200);
    }

    /**
     * Get Dashboard Data
     */
    public function dashboard(Request $request)
    {
        Log::info('📊 Dashboard Request', [
            'user_id' => $request->user()->id,
            'email' => $request->user()->email
        ]);

        // Calculate company balance from all transactions
        $totalIncome = CompanyAccountTransaction::where('type', 'income')->sum('amount');
        $totalExpense = CompanyAccountTransaction::where('type', 'expense')->sum('amount');
        $balance = $totalIncome - $totalExpense;

        Log::debug('💰 Balance Calculated', [
            'total_income' => $totalIncome,
            'total_expense' => $totalExpense,
            'balance' => $balance
        ]);

        // Calculate monthly statistics
        $currentMonth = now()->format('Y-m');
        $monthlyIncome = CompanyAccountTransaction::where('type', 'income')
            ->whereRaw('DATE_FORMAT(transaction_date, "%Y-%m") = ?', [$currentMonth])
            ->sum('amount');
        
        $monthlyExpense = CompanyAccountTransaction::where('type', 'expense')
            ->whereRaw('DATE_FORMAT(transaction_date, "%Y-%m") = ?', [$currentMonth])
            ->sum('amount');

        // Get pending debit requests count
        $pendingRequestsCount = AccountDebitRequest::where('status', 'pending')->count();

        // Get recent transactions
        $recentTransactions = CompanyAccountTransaction::with(['branch', 'recordedBy'])
            ->orderBy('transaction_date', 'desc')
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get()
            ->map(function ($transaction) {
                return [
                    'id' => $transaction->id,
                    'type' => $transaction->type,
                    'category' => $transaction->category,
                    'amount' => (float) $transaction->amount,
                    'description' => $transaction->description,
                    'transaction_date' => $transaction->transaction_date->format('Y-m-d'),
                    'branch' => $transaction->branch->name,
                    'recorded_by' => $transaction->recordedBy->name,
                ];
            });

        return response()->json([
            'success' => true,
            'data' => [
                'balance' => (float) $balance,
                'monthly_income' => (float) $monthlyIncome,
                'monthly_expense' => (float) $monthlyExpense,
                'pending_requests' => $pendingRequestsCount,
                'recent_transactions' => $recentTransactions,
                'current_month' => now()->format('F Y'),
            ]
        ], 200);
    }

    /**
     * Get All Transactions
     */
    public function transactions(Request $request)
    {
        Log::info('📋 Transactions Request', [
            'user_id' => $request->user()->id,
            'filters' => [
                'type' => $request->type,
                'from_date' => $request->from_date,
                'to_date' => $request->to_date,
                'branch_id' => $request->branch_id,
                'page' => $request->page
            ]
        ]);

        $query = CompanyAccountTransaction::with(['branch', 'recordedBy']);

        // Filter by type
        if ($request->has('type') && in_array($request->type, ['income', 'expense'])) {
            $query->where('type', $request->type);
        }

        // Filter by date range
        if ($request->has('from_date')) {
            $query->where('transaction_date', '>=', $request->from_date);
        }
        if ($request->has('to_date')) {
            $query->where('transaction_date', '<=', $request->to_date);
        }

        // Filter by branch
        if ($request->has('branch_id')) {
            $query->where('branch_id', $request->branch_id);
        }

        $perPage = $request->get('per_page', 20);
        $transactions = $query->orderBy('transaction_date', 'desc')
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);

        Log::debug('📋 Transactions Retrieved', [
            'total' => $transactions->total(),
            'per_page' => $perPage,
            'current_page' => $transactions->currentPage()
        ]);

        // Format transactions to return only necessary data
        $formattedTransactions = $transactions->map(function ($transaction) {
            return [
                'id' => $transaction->id,
                'type' => $transaction->type,
                'category' => $transaction->category,
                'amount' => (float) $transaction->amount,
                'description' => $transaction->description,
                'transaction_date' => $transaction->transaction_date->format('Y-m-d'),
                'branch' => $transaction->branch->name,
                'recorded_by' => $transaction->recordedBy->name,
                'reference' => $transaction->reference,
                'receipt_document' => $transaction->receipt_document,
                'payment_file' => $transaction->payment_file, 
            ];
        });

        return response()->json([
            'success' => true,
            'data' => [
                'transactions' => $formattedTransactions,
                'pagination' => [
                    'total' => $transactions->total(),
                    'per_page' => $transactions->perPage(),
                    'current_page' => $transactions->currentPage(),
                    'last_page' => $transactions->lastPage(),
                ]
            ]
        ], 200);
    }

    /**
     * Get All Debit Requests
     */
    public function debitRequests(Request $request)
    {
        Log::info('📝 Debit Requests Request', [
            'user_id' => $request->user()->id,
            'filters' => [
                'status' => $request->status,
                'branch_id' => $request->branch_id,
                'page' => $request->page
            ]
        ]);

        $query = AccountDebitRequest::with(['branch', 'requester', 'approver']);

        // Filter by status
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        // Filter by branch
        if ($request->has('branch_id')) {
            $query->where('branch_id', $request->branch_id);
        }

        $perPage = $request->get('per_page', 20);
        $requests = $query->orderBy('created_at', 'desc')->paginate($perPage);

        $threshold = SystemSetting::where('key', 'debit_approval_threshold')->first()?->value ?? 100000;

        return response()->json([
            'success' => true,
            'data' => [
                'requests' => $requests->items(),
                'threshold' => (float) $threshold,
                'pagination' => [
                    'total' => $requests->total(),
                    'per_page' => $requests->perPage(),
                    'current_page' => $requests->currentPage(),
                    'last_page' => $requests->lastPage(),
                ]
            ]
        ], 200);
    }

    /**
     * Get Single Debit Request
     */
    public function showDebitRequest($id)
    {
        Log::info('🔍 View Debit Request', ['request_id' => $id]);

        $debitRequest = AccountDebitRequest::with(['branch', 'requester', 'approver'])->find($id);

        if (!$debitRequest) {
            Log::warning('❌ Debit Request Not Found', ['request_id' => $id]);
            return response()->json([
                'success' => false,
                'message' => 'Debit request not found'
            ], 404);
        }

        $threshold = SystemSetting::where('key', 'debit_approval_threshold')->first()?->value ?? 100000;

        return response()->json([
            'success' => true,
            'data' => [
                'request' => $debitRequest,
                'threshold' => (float) $threshold,
            ]
        ], 200);
    }

    /**
     * Create Debit Request
     */
    public function createDebitRequest(Request $request)
    {
        Log::info('➕ Create Debit Request Attempt', [
            'user_id' => $request->user()->id,
            'branch_id' => $request->branch_id,
            'amount' => $request->amount
        ]);

        $validator = Validator::make($request->all(), [
            'branch_id' => 'required|exists:branches,id',
            'amount' => 'required|numeric|min:1',
            'description' => 'required|string|max:1000',
            'receipt_document' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:2048',
        ]);

        if ($validator->fails()) {
            Log::warning('❌ Debit Request Validation Failed', [
                'user_id' => $request->user()->id,
                'errors' => $validator->errors()->toArray()
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            DB::beginTransaction();

            Log::debug('📄 Processing Receipt Upload', [
                'has_file' => $request->hasFile('receipt_document')
            ]);

            $receiptPath = null;
            if ($request->hasFile('receipt_document')) {
                $receiptPath = $request->file('receipt_document')->store('debit_receipts', 'public');
            }

            // Get threshold from settings
            $threshold = SystemSetting::where('key', 'debit_approval_threshold')->first()?->value ?? 100000;
            
            // Determine if auto-approval is needed
            $isAutoApproved = $request->amount < $threshold;
            $status = $isAutoApproved ? 'approved' : 'pending';

            Log::debug('💡 Auto-approval Check', [
                'amount' => $request->amount,
                'threshold' => $threshold,
                'auto_approved' => $isAutoApproved
            ]);

            $debitRequest = AccountDebitRequest::create([
                'branch_id' => $request->branch_id,
                'requested_by' => $request->user()->id,
                'amount' => $request->amount,
                'description' => $request->description,
                'status' => $status,
                'receipt_document' => $receiptPath,
                'approved_by' => $isAutoApproved ? $request->user()->id : null,
                'approved_at' => $isAutoApproved ? now() : null,
                'approval_notes' => $isAutoApproved ? 'Auto-approved (below threshold)' : null,
            ]);

            // If auto-approved, create the transaction immediately
            if ($isAutoApproved) {
                CompanyAccountTransaction::create([
                    'branch_id' => $request->branch_id,
                    'type' => 'expense',
                    'category' => 'debit_request',
                    'amount' => $request->amount,
                    'description' => $request->description,
                    'transaction_date' => now(),
                    'recorded_by' => $request->user()->id,
                    'reference' => 'DR-' . $debitRequest->id,
                ]);

                Log::info('💰 Transaction Created Automatically', [
                    'request_id' => $debitRequest->id,
                    'amount' => $request->amount
                ]);
            }

            DB::commit();

            Log::info('✅ Debit Request Created Successfully', [
                'request_id' => $debitRequest->id,
                'user_id' => $request->user()->id,
                'amount' => $debitRequest->amount,
                'branch_id' => $debitRequest->branch_id,
                'auto_approved' => $isAutoApproved
            ]);

            $message = $isAutoApproved 
                ? 'Debit request auto-approved and processed successfully (below threshold)'
                : 'Debit request created successfully';

            return response()->json([
                'success' => true,
                'message' => $message,
                'data' => $debitRequest
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('❌ Failed to Create Debit Request', [
                'user_id' => $request->user()->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Failed to create debit request: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get Branches
     */
    public function branches()
    {
        Log::debug('🏢 Branches Request');
        
        $branches = Branch::select('id', 'name', 'location')->get();
        
        Log::debug('🏢 Branches Retrieved', ['count' => $branches->count()]);

        return response()->json([
            'success' => true,
            'data' => $branches
        ], 200);
    }

    /**
     * Get User Profile
     */
    public function profile(Request $request)
    {
        Log::info('👤 Profile Request', ['user_id' => $request->user()->id]);
        
        $user = $request->user();

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'phone' => $user->phone,
                'role' => 'Accountant',
                'branch_id' => $user->branch_id,
                'created_at' => $user->created_at->format('Y-m-d'),
            ]
        ], 200);
    }

    /**
     * Update Profile
     */
    public function updateProfile(Request $request)
    {
        Log::info('✏️ Update Profile Attempt', [
            'user_id' => $request->user()->id,
            'updating' => array_keys($request->only(['name', 'phone', 'new_password']))
        ]);

        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|required|string|max:255',
            'phone' => 'sometimes|required|string|max:20',
            'current_password' => 'required_with:new_password',
            'new_password' => 'sometimes|required|string|min:8|confirmed',
        ]);

        if ($validator->fails()) {
            Log::warning('❌ Profile Update Validation Failed', [
                'user_id' => $request->user()->id,
                'errors' => $validator->errors()->toArray()
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $user = $request->user();

            if ($request->filled('name')) {
                $user->name = $request->name;
            }

            if ($request->filled('phone')) {
                $user->phone = $request->phone;
            }

            if ($request->filled('new_password')) {
                if (!Hash::check($request->current_password, $user->password)) {
                    Log::warning('❌ Incorrect Current Password', ['user_id' => $user->id]);
                    return response()->json([
                        'success' => false,
                        'message' => 'Current password is incorrect'
                    ], 422);
                }
                $user->password = Hash::make($request->new_password);
            }

            $user->save();

            Log::info('✅ Profile Updated Successfully', [
                'user_id' => $user->id,
                'updated_fields' => array_keys($request->only(['name', 'phone', 'new_password']))
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Profile updated successfully',
                'data' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'phone' => $user->phone,
                ]
            ], 200);

        } catch (\Exception $e) {
            Log::error('❌ Failed to Update Profile', [
                'user_id' => $request->user()->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Failed to update profile: ' . $e->getMessage()
            ], 500);
        }
    }
}
