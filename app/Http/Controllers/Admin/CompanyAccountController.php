<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\CompanyAccountTransaction;
use App\Services\BranchAccessService;
use App\Traits\HasDateFilters;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class CompanyAccountController extends Controller
{
    use HasDateFilters;
    public function index(Request $request)
    {
        $user = auth()->user();
        
        // Get filter parameters
        $timeFilter = $request->get('time_filter', 'monthly');
        $startDate = $request->get('start_date');
        $endDate = $request->get('end_date');
        $branchId = $request->get('branch_id');
        $type = $request->get('type');
        $category = $request->get('category');
        $search = $request->get('search');

        // Calculate date range based on time filter
        [$start, $end] = $this->getDateRange($timeFilter, $startDate, $endDate);

        // Build query
        $query = CompanyAccountTransaction::with(['branch', 'recordedBy'])
            ->when($start && $end, function ($q) use ($start, $end) {
                $q->dateRange($start, $end);
            })
            ->when($branchId, function ($q) use ($branchId) {
                $q->where('branch_id', $branchId);
            })
            ->when(!$user->hasRole(['Super Admin', 'Accountant']), function ($q) use ($user) {
                BranchAccessService::applyBranchFilter($q, $user);
            })
            ->when($type, function ($q) use ($type) {
                $q->where('type', $type);
            })
            ->when($category, function ($q) use ($category) {
                $q->where('category', $category);
            })
            ->when($search, function ($q) use ($search) {
                $q->where(function ($query) use ($search) {
                    $query->where('description', 'like', "%{$search}%")
                          ->orWhere('reference', 'like', "%{$search}%");
                });
            })
            ->latest('transaction_date');

        $transactions = $query->paginate(20)->withQueryString();

        // Calculate statistics for current filter
        $stats = $this->calculateStats($start, $end, $branchId, $user);

        // Get branch-based summary
        $branchSummary = $this->getBranchSummary($start, $end, $user);

        $branches = Branch::all();
        $categories = CompanyAccountTransaction::getCategories();

        return view('admin.company-account.index', compact(
            'transactions',
            'stats',
            'branchSummary',
            'branches',
            'categories',
            'timeFilter',
            'startDate',
            'endDate',
            'branchId',
            'type',
            'category',
            'search'
        ));
    }

    public function create()
    {
        $branches = Branch::all();
        $categories = CompanyAccountTransaction::getCategories();
        
        return view('admin.company-account.create', compact('branches', 'categories'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'branch_id' => 'required|exists:branches,id',
            'type' => 'required|in:income,expense',
            'amount' => 'required|numeric|min:0.01',
            'category' => 'required|string',
            'description' => 'required|string|max:1000',
            'transaction_date' => 'required|date',
            'receipt_document' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:5120',
        ]);

        $data = $request->except('receipt_document');
        $data['recorded_by'] = auth()->id();

        if ($request->hasFile('receipt_document')) {
            $data['receipt_document'] = $request->file('receipt_document')
                ->store('company-account-receipts', 'public');
        }

        CompanyAccountTransaction::create($data);

        return redirect()->route('admin.company-account.index')
            ->with('success', 'Transaction recorded successfully!');
    }

    public function show(CompanyAccountTransaction $companyAccountTransaction)
    {
        $companyAccountTransaction->load(['branch', 'recordedBy']);
        
        return view('admin.company-account.show', compact('companyAccountTransaction'));
    }

    private function calculateStats($start, $end, $branchId, $user)
    {
        $query = CompanyAccountTransaction::query()
            ->when($start && $end, function ($q) use ($start, $end) {
                $q->dateRange($start, $end);
            })
            ->when($branchId, function ($q) use ($branchId) {
                $q->where('branch_id', $branchId);
            })
            ->when(!$user->hasRole(['Super Admin', 'Accountant']), function ($q) use ($user) {
                BranchAccessService::applyBranchFilter($q, $user);
            });

        $totalIncome = (clone $query)->income()->sum('amount');
        $totalExpenses = (clone $query)->expense()->sum('amount');
        $balance = $totalIncome - $totalExpenses;

        return [
            'total_income' => $totalIncome,
            'total_expenses' => $totalExpenses,
            'balance' => $balance,
            'transaction_count' => (clone $query)->count(),
        ];
    }

    private function getBranchSummary($start, $end, $user)
    {
        $query = CompanyAccountTransaction::select(
            'branch_id',
            DB::raw('SUM(CASE WHEN type = "income" THEN amount ELSE 0 END) as total_income'),
            DB::raw('SUM(CASE WHEN type = "expense" THEN amount ELSE 0 END) as total_expenses'),
            DB::raw('SUM(CASE WHEN type = "income" THEN amount ELSE -amount END) as balance')
        )
        ->with('branch')
        ->when($start && $end, function ($q) use ($start, $end) {
            $q->dateRange($start, $end);
        })
        ->when(!$user->hasRole(['Super Admin', 'Accountant']), function ($q) use ($user) {
            BranchAccessService::applyBranchFilter($q, $user);
        })
        ->groupBy('branch_id')
        ->get();

        return $query;
    }
}
