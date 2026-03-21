@extends('layouts.app')

@section('title', 'Company Account')

@section('content')
<div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-4">
    <h3 class="mb-0">Company Account</h3>

    <div>
        <a href="{{ route('admin.company-account.create') }}" class="btn btn-primary">
            <i class="ri-add-line me-2"></i> Record Transaction
        </a>
    </div>
</div>

<!-- Statistics Cards -->
<div class="row mb-4">
    <div class="col-lg-3 col-sm-6">
        <div class="card bg-white border-0 rounded-3 mb-4">
            <div class="card-body p-4">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <span class="text-secondary d-block mb-1">Total Income</span>
                        <h3 class="mb-0 text-success">₦{{ number_format($stats['total_income'], 2) }}</h3>
                    </div>
                    <div class="rounded-circle wh-60 bg-success-div d-flex align-items-center justify-content-center">
                        <span class="material-symbols-outlined text-success fs-32">trending_up</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-lg-3 col-sm-6">
        <div class="card bg-white border-0 rounded-3 mb-4">
            <div class="card-body p-4">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <span class="text-secondary d-block mb-1">Total Expenses</span>
                        <h3 class="mb-0 text-danger">₦{{ number_format($stats['total_expenses'], 2) }}</h3>
                    </div>
                    <div class="rounded-circle wh-60 bg-danger-div d-flex align-items-center justify-content-center">
                        <span class="material-symbols-outlined text-danger fs-32">trending_down</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-lg-3 col-sm-6">
        <div class="card bg-white border-0 rounded-3 mb-4">
            <div class="card-body p-4">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <span class="text-secondary d-block mb-1">Net Balance</span>
                        <h3 class="mb-0 {{ $stats['balance'] >= 0 ? 'text-primary' : 'text-warning' }}">
                            ₦{{ number_format($stats['balance'], 2) }}
                        </h3>
                    </div>
                    <div class="rounded-circle wh-60 {{ $stats['balance'] >= 0 ? 'bg-primary-div' : 'bg-warning-div' }} d-flex align-items-center justify-content-center">
                        <span class="material-symbols-outlined {{ $stats['balance'] >= 0 ? 'text-primary' : 'text-warning' }} fs-32">account_balance</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-lg-3 col-sm-6">
        <div class="card bg-white border-0 rounded-3 mb-4">
            <div class="card-body p-4">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <span class="text-secondary d-block mb-1">Transactions</span>
                        <h3 class="mb-0 text-info">{{ $stats['transaction_count'] }}</h3>
                    </div>
                    <div class="rounded-circle wh-60 bg-info-div d-flex align-items-center justify-content-center">
                        <span class="material-symbols-outlined text-info fs-32">receipt_long</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Branch Summary -->
@if($branchSummary->count() > 0)
<div class="card bg-white border-0 rounded-3 mb-4">
    <div class="card-body p-4">
        <h5 class="mb-4">Branch-Based Summary</h5>
        <div class="table-responsive">
            <table class="table align-middle">
                <thead>
                    <tr>
                        <th>Branch</th>
                        <th>Income</th>
                        <th>Expenses</th>
                        <th>Balance</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($branchSummary as $summary)
                    <tr>
                        <td><strong>{{ $summary->branch->name }}</strong></td>
                        <td class="text-success">₦{{ number_format($summary->total_income, 2) }}</td>
                        <td class="text-danger">₦{{ number_format($summary->total_expenses, 2) }}</td>
                        <td class="fw-bold {{ $summary->balance >= 0 ? 'text-primary' : 'text-warning' }}">
                            ₦{{ number_format($summary->balance, 2) }}
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
@endif

<!-- Filters -->
<div class="card bg-white border-0 rounded-3 mb-4">
    <div class="card-body p-4">
        <form method="GET" action="{{ route('admin.company-account.index') }}" id="filterForm">
            <div class="row g-3">
                <div class="col-lg-2 col-md-4">
                    <label class="form-label">Time Period</label>
                    <select class="form-select" name="time_filter" id="timeFilter">
                        <option value="daily" {{ $timeFilter == 'daily' ? 'selected' : '' }}>Today</option>
                        <option value="weekly" {{ $timeFilter == 'weekly' ? 'selected' : '' }}>This Week</option>
                        <option value="monthly" {{ $timeFilter == 'monthly' ? 'selected' : '' }}>This Month</option>
                        <option value="yearly" {{ $timeFilter == 'yearly' ? 'selected' : '' }}>This Year</option>
                        <option value="custom" {{ $timeFilter == 'custom' ? 'selected' : '' }}>Custom Range</option>
                    </select>
                </div>

                <div class="col-lg-2 col-md-4" id="startDateDiv" style="display: {{ $timeFilter == 'custom' ? 'block' : 'none' }};">
                    <label class="form-label">Start Date</label>
                    <input type="date" class="form-control" name="start_date" value="{{ $startDate }}">
                </div>

                <div class="col-lg-2 col-md-4" id="endDateDiv" style="display: {{ $timeFilter == 'custom' ? 'block' : 'none' }};">
                    <label class="form-label">End Date</label>
                    <input type="date" class="form-control" name="end_date" value="{{ $endDate }}">
                </div>

                @if(auth()->user()->hasRole('Super Admin'))
                <div class="col-lg-2 col-md-4">
                    <label class="form-label">Branch</label>
                    <select class="form-select" name="branch_id">
                        <option value="">All Branches</option>
                        @foreach($branches as $branch)
                        <option value="{{ $branch->id }}" {{ $branchId == $branch->id ? 'selected' : '' }}>
                            {{ $branch->name }}
                        </option>
                        @endforeach
                    </select>
                </div>
                @endif

                <div class="col-lg-2 col-md-4">
                    <label class="form-label">Type</label>
                    <select class="form-select" name="type">
                        <option value="">All Types</option>
                        <option value="income" {{ $type == 'income' ? 'selected' : '' }}>Income</option>
                        <option value="expense" {{ $type == 'expense' ? 'selected' : '' }}>Expense</option>
                    </select>
                </div>

                <div class="col-lg-2 col-md-4">
                    <label class="form-label">Category</label>
                    <select class="form-select" name="category">
                        <option value="">All Categories</option>
                        @foreach($categories as $key => $label)
                        <option value="{{ $key }}" {{ $category == $key ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="col-lg-3 col-md-4">
                    <label class="form-label">Search</label>
                    <input type="text" class="form-control" name="search" value="{{ $search }}" placeholder="Description or reference...">
                </div>

                <div class="col-lg-3 col-md-4 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary me-2">
                        <i class="ri-filter-line me-1"></i> Apply Filters
                    </button>
                    <a href="{{ route('admin.company-account.index') }}" class="btn btn-outline-secondary">
                        <i class="ri-refresh-line"></i> Reset
                    </a>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Transactions Table -->
<div class="card bg-white border-0 rounded-3">
    <div class="card-body p-4">
        <h5 class="mb-4">Transaction History</h5>
        
        <div class="table-responsive">
            <table class="table align-middle">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Date</th>
                        <th>Branch</th>
                        <th>Type</th>
                        <th>Category</th>
                        <th>Description</th>
                        <th>Amount</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($transactions as $transaction)
                    <tr>
                        <td>{{ ($transactions->currentPage() - 1) * $transactions->perPage() + $loop->iteration }}</td>
                        <td>{{ $transaction->transaction_date->format('M d, Y') }}</td>
                        <td>{{ $transaction->branch->name }}</td>
                        <td>
                            @if($transaction->type === 'income')
                            <span class="badge bg-success">
                                <i class="ri-arrow-up-line"></i> Income
                            </span>
                            @else
                            <span class="badge bg-danger">
                                <i class="ri-arrow-down-line"></i> Expense
                            </span>
                            @endif
                        </td>
                        <td>
                            <span class="badge bg-secondary">{{ $categories[$transaction->category] ?? $transaction->category }}</span>
                        </td>
                        <td>{{ Str::limit($transaction->description, 40) }}</td>
                        <td class="fw-bold {{ $transaction->isIncome() ? 'text-success' : 'text-danger' }}">
                            {{ $transaction->isIncome() ? '+' : '-' }}₦{{ number_format($transaction->amount, 2) }}
                        </td>
                        <td>
                            <a href="{{ route('admin.company-account.show', $transaction) }}" class="btn btn-sm btn-primary">
                                <i class="ri-eye-line"></i>
                            </a>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="8" class="text-center py-4 text-muted">
                            <span class="material-symbols-outlined fs-48 d-block">receipt_long</span>
                            <p class="mt-2">No transactions found for the selected period</p>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($transactions->hasPages())
        <div class="mt-3">
            {{ $transactions->links() }}
        </div>
        @endif
    </div>
</div>
@endsection

@push('scripts')
<script>
// Handle time filter changes
document.getElementById('timeFilter').addEventListener('change', function() {
    const customDivs = ['startDateDiv', 'endDateDiv'];
    if (this.value === 'custom') {
        customDivs.forEach(id => {
            document.getElementById(id).style.display = 'block';
        });
    } else {
        customDivs.forEach(id => {
            document.getElementById(id).style.display = 'none';
        });
    }
});
</script>
@endpush
