<?php

namespace App\Http\Controllers;

use App\Models\AccountDebitRequest;
use App\Models\CompanyAccountTransaction;
use App\Models\SystemSetting;
use App\Services\BranchAccessService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class AccountController extends Controller
{
    /**
     * Display company account dashboard
     */
    public function index()
    {
        // Calculate company balance from all transactions
        $totalIncome = CompanyAccountTransaction::where('type', 'income')->sum('amount');
        $totalExpense = CompanyAccountTransaction::where('type', 'expense')->sum('amount');
        $balance = $totalIncome - $totalExpense;
        
        // Update the stored balance setting
        SystemSetting::updateOrCreate(
            ['key' => 'company_account_balance'],
            ['value' => $balance]
        );
        
        // Get recent transactions
        $transactions = CompanyAccountTransaction::with(['branch', 'recordedBy'])
            ->orderBy('transaction_date', 'desc')
            ->orderBy('created_at', 'desc')
            ->limit(20)
            ->get();
        
        // Get pending debit requests
        $pendingRequests = AccountDebitRequest::with(['branch', 'requester'])
            ->pending()
            ->orderBy('created_at', 'desc')
            ->get();
        
        // Calculate monthly statistics
        $currentMonth = now()->format('Y-m');
        $monthlyIncome = CompanyAccountTransaction::where('type', 'income')
            ->whereRaw('DATE_FORMAT(transaction_date, "%Y-%m") = ?', [$currentMonth])
            ->sum('amount');
        
        $monthlyExpense = CompanyAccountTransaction::where('type', 'expense')
            ->whereRaw('DATE_FORMAT(transaction_date, "%Y-%m") = ?', [$currentMonth])
            ->sum('amount');
        
        return view('accounts.index', compact(
            'balance',
            'transactions',
            'pendingRequests',
            'monthlyIncome',
            'monthlyExpense'
        ));
    }

    /**
     * Show debit request form
     */
    public function createDebitRequest()
    {
        $threshold = SystemSetting::where('key', 'debit_approval_threshold')->first()?->value ?? 100000;
        $branches = \App\Models\Branch::all();
        $userBranch = Auth::user()->branch_id;
        
        return view('accounts.create-debit-request', compact('threshold', 'branches', 'userBranch'));
    }

    /**
     * Store a new debit request
     */
    public function storeDebitRequest(Request $request)
    {
        $validationRules = [
            'amount' => 'required|numeric|min:1',
            'description' => 'required|string|max:1000',
            'receipt_document' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:2048',
        ];

        // Accountants must select a branch; others use their assigned branch
        if (!Auth::user()->branch_id) {
            $validationRules['branch_id'] = 'required|exists:branches,id';
        }

        $request->validate($validationRules);

        try {
            DB::beginTransaction();

            $receiptPath = null;
            if ($request->hasFile('receipt_document')) {
                $receiptPath = $request->file('receipt_document')->store('debit_receipts', 'public');
            }

            // Use provided branch_id for Accountants, or user's branch_id for others
            $branchId = Auth::user()->branch_id ?? $request->branch_id;

            // Get threshold from settings
            $threshold = SystemSetting::where('key', 'debit_approval_threshold')->first()?->value ?? 100000;
            
            // Determine if auto-approval is needed
            $isAutoApproved = $request->amount < $threshold;
            $status = $isAutoApproved ? 'approved' : 'pending';

            $debitRequest = AccountDebitRequest::create([
                'branch_id' => $branchId,
                'requested_by' => Auth::id(),
                'amount' => $request->amount,
                'description' => $request->description,
                'status' => $status,
                'receipt_document' => $receiptPath,
                'approved_by' => $isAutoApproved ? Auth::id() : null,
                'approved_at' => $isAutoApproved ? now() : null,
                'approval_notes' => $isAutoApproved ? 'Auto-approved (below threshold)' : null,
            ]);

            // If auto-approved, create the transaction immediately
            if ($isAutoApproved) {
                CompanyAccountTransaction::create([
                    'branch_id' => $branchId,
                    'type' => 'expense',
                    'category' => 'debit_request',
                    'amount' => $request->amount,
                    'description' => $request->description,
                    'transaction_date' => now(),
                    'recorded_by' => Auth::id(),
                    'reference' => 'DR-' . $debitRequest->id,
                ]);
            }

            DB::commit();

            Log::info('Debit request created', [
                'request_id' => $debitRequest->id,
                'amount' => $request->amount,
                'requested_by' => Auth::id(),
                'auto_approved' => $isAutoApproved,
            ]);

            $message = $isAutoApproved 
                ? 'Debit request auto-approved and processed successfully (below threshold).'
                : 'Debit request submitted successfully. Awaiting approval.';

            return redirect()->route('accounts.index')
                ->with('success', $message);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to create debit request', [
                'error' => $e->getMessage(),
                'user_id' => Auth::id(),
            ]);

            return back()->with('error', 'Failed to submit debit request. Please try again.')
                ->withInput();
        }
    }

    /**
     * Show debit requests list
     */
    public function debitRequests(Request $request)
    {
        $query = AccountDebitRequest::with(['branch', 'requester', 'approver']);

        // Filter by status
        if ($request->has('status') && in_array($request->status, ['pending', 'approved', 'rejected'])) {
            $query->where('status', $request->status);
        }

        // Filter by branch
        if ($request->has('branch_id')) {
            $query->where('branch_id', $request->branch_id);
        } else if (!Auth::user()->can('approve debit requests') && Auth::user()->branch_id) {
            // Branch users can only see their branch requests
            // Accountants (no branch_id) and approvers can see all
            $query->where('branch_id', Auth::user()->branch_id);
        }

        $requests = $query->orderBy('created_at', 'desc')->paginate(20);
        $threshold = SystemSetting::where('key', 'debit_approval_threshold')->first()?->value ?? 100000;

        return view('accounts.debit-requests', compact('requests', 'threshold'));
    }

    /**
     * Show debit request details
     */
    public function showDebitRequest(AccountDebitRequest $debitRequest)
    {
        $debitRequest->load(['branch', 'requester', 'approver']);
        
        // Check authorization
        $user = Auth::user();
        // Users with branch_id can only see their branch requests
        // Accountants (no branch_id) and approvers can see all
        if (!$user->can('approve debit requests') && !BranchAccessService::canAccessBranch($user, $debitRequest->branch_id)) {
            abort(403, 'Unauthorized access');
        }

        $threshold = SystemSetting::where('key', 'debit_approval_threshold')->first()?->value ?? 100000;

        return view('accounts.show-debit-request', compact('debitRequest', 'threshold'));
    }

    /**
     * Approve or reject a debit request
     */
    public function reviewDebitRequest(Request $request, AccountDebitRequest $debitRequest)
    {
        // Permission check is handled by middleware
        // Additional check: cannot approve own request
        if ($debitRequest->requested_by === Auth::id()) {
            return back()->with('error', 'You cannot approve your own debit request.');
        }

        $request->validate([
            'action' => 'required|in:approve,reject',
            'notes' => 'nullable|string|max:500',
        ]);

        try {
            DB::beginTransaction();

            if ($debitRequest->status !== 'pending') {
                return back()->with('error', 'This request has already been processed.');
            }

            $action = $request->action;
            $debitRequest->update([
                'status' => $action === 'approve' ? 'approved' : 'rejected',
                'approved_by' => Auth::id(),
                'approval_notes' => $request->notes,
                'approved_at' => now(),
            ]);

            // If approved, debit from company account and create transaction
            if ($action === 'approve') {
                $this->debitCompanyAccount($debitRequest);
            }

            DB::commit();

            Log::info('Debit request reviewed', [
                'request_id' => $debitRequest->id,
                'action' => $action,
                'reviewed_by' => Auth::id(),
            ]);

            return redirect()->route('accounts.debit-requests')
                ->with('success', "Debit request {$action}d successfully.");

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to review debit request', [
                'error' => $e->getMessage(),
                'request_id' => $debitRequest->id,
            ]);

            return back()->with('error', 'Failed to process request. Please try again.');
        }
    }

    /**
     * Debit company account and create transaction record
     */
    private function debitCompanyAccount(AccountDebitRequest $debitRequest)
    {
        // Get current balance
        $balanceSetting = SystemSetting::where('key', 'company_account_balance')->first();
        $currentBalance = $balanceSetting ? (float)$balanceSetting->value : 0;

        // Check if sufficient balance
        if ($currentBalance < $debitRequest->amount) {
            throw new \Exception('Insufficient company account balance');
        }

        // Update balance
        $newBalance = $currentBalance - $debitRequest->amount;
        $balanceSetting->update(['value' => $newBalance]);

        // Create transaction record
        CompanyAccountTransaction::create([
            'branch_id' => $debitRequest->branch_id,
            'type' => 'expense',
            'amount' => $debitRequest->amount,
            'category' => 'debit_request',
            'reference' => 'DR-' . $debitRequest->id,
            'description' => $debitRequest->description,
            'transaction_date' => now()->toDateString(),
            'recorded_by' => Auth::id(),
            'receipt_document' => $debitRequest->receipt_document,
        ]);

        Log::info('Company account debited', [
            'request_id' => $debitRequest->id,
            'amount' => $debitRequest->amount,
            'old_balance' => $currentBalance,
            'new_balance' => $newBalance,
        ]);
    }

    /**
     * Show transactions list
     */
    public function transactions(Request $request)
    {
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

        $transactions = $query->orderBy('transaction_date', 'desc')
            ->orderBy('created_at', 'desc')
            ->paginate(30);

        // Calculate company balance from all transactions
        $totalIncome = CompanyAccountTransaction::where('type', 'income')->sum('amount');
        $totalExpense = CompanyAccountTransaction::where('type', 'expense')->sum('amount');
        $balance = $totalIncome - $totalExpense;

        return view('accounts.transactions', compact('transactions', 'balance'));
    }
}
