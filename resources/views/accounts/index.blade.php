@extends('layouts.app')

@section('title', 'Account Management')

@section('content')
<div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-4">
    <h3 class="mb-0">Account Management</h3>

    <nav style="--bs-breadcrumb-divider: '>';" aria-label="breadcrumb">
        <ol class="breadcrumb align-items-center mb-0 lh-1">
            <li class="breadcrumb-item">
                <a href="{{ route('dashboard') }}" class="d-flex align-items-center text-decoration-none">
                    <i class="ri-home-4-line fs-18 text-primary me-1"></i>
                    <span class="text-secondary fw-medium hover">Dashboard</span>
                </a>
            </li>
            <li class="breadcrumb-item active" aria-current="page">
                <span class="fw-medium">Account Management</span>
            </li>
        </ol>
    </nav>
</div>

<!-- Summary Cards -->
<div class="row">
    <!-- Company Balance Card -->
    <div class="col-xxl-4 col-sm-6">
        <div class="card bg-white border-0 rounded-3 mb-4">
            <div class="card-body p-4">
                <div class="d-flex align-items-center justify-content-between">
                    <div class="flex-grow-1">
                        <span class="d-block fw-normal text-secondary mb-1">Company Account Balance</span>
                        <h3 class="mb-0 fs-20 text-success">₦{{ number_format($balance, 2) }}</h3>
                        <span class="d-block text-muted fs-12 mt-1">All branches combined</span>
                    </div>
                    <div class="flex-shrink-0">
                        <div class="d-flex align-items-center justify-content-center wh-77 rounded-circle" style="background-color: rgba(76, 175, 80, 0.1);">
                            <span class="material-symbols-outlined fs-32 text-success">account_balance_wallet</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Monthly Income -->
    <div class="col-xxl-4 col-sm-6">
        <div class="card bg-white border-0 rounded-3 mb-4">
            <div class="card-body p-4">
                <div class="d-flex align-items-center justify-content-between">
                    <div class="flex-grow-1">
                        <span class="d-block fw-normal text-secondary mb-1">This Month Income</span>
                        <h3 class="mb-0 fs-20 text-primary">₦{{ number_format($monthlyIncome, 2) }}</h3>
                        <span class="d-block text-muted fs-12 mt-1">{{ now()->format('F Y') }}</span>
                    </div>
                    <div class="flex-shrink-0">
                        <div class="d-flex align-items-center justify-content-center wh-77 rounded-circle" style="background-color: rgba(116, 66, 162, 0.1);">
                            <span class="material-symbols-outlined fs-32 text-primary">trending_up</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Monthly Expenses -->
    <div class="col-xxl-4 col-sm-6">
        <div class="card bg-white border-0 rounded-3 mb-4">
            <div class="card-body p-4">
                <div class="d-flex align-items-center justify-content-between">
                    <div class="flex-grow-1">
                        <span class="d-block fw-normal text-secondary mb-1">This Month Expenses</span>
                        <h3 class="mb-0 fs-20 text-danger">₦{{ number_format($monthlyExpense, 2) }}</h3>
                        <span class="d-block text-muted fs-12 mt-1">{{ now()->format('F Y') }}</span>
                    </div>
                    <div class="flex-shrink-0">
                        <div class="d-flex align-items-center justify-content-center wh-77 rounded-circle" style="background-color: rgba(220, 53, 69, 0.1);">
                            <span class="material-symbols-outlined fs-32 text-danger">trending_down</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Quick Actions -->
<div class="row">
    <div class="col-lg-12">
        <div class="card bg-white border-0 rounded-3 mb-4">
            <div class="card-body p-4">
                <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
                    <h4 class="mb-0">Quick Actions</h4>
                    <div class="d-flex gap-2 flex-wrap">
                        @can('create debit request')
                        <a href="{{ route('accounts.debit-requests.create') }}" class="btn btn-primary btn-sm">
                            <span class="material-symbols-outlined me-1" style="vertical-align: middle; font-size: 18px;">add</span>
                            New Debit Request
                        </a>
                        @endcan
                        <a href="{{ route('accounts.transactions') }}" class="btn btn-outline-secondary btn-sm">
                            <span class="material-symbols-outlined me-1" style="vertical-align: middle; font-size: 18px;">receipt_long</span>
                            View All Transactions
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Pending Requests Alert -->
@if($pendingRequests->count() > 0)
<div class="row">
    <div class="col-lg-12">
        <div class="alert alert-warning mb-4" role="alert">
            <div class="d-flex align-items-center">
                <span class="material-symbols-outlined me-2">warning</span>
                <div>
                    <strong>Pending Approval:</strong> 
                    You have {{ $pendingRequests->count() }} debit request(s) awaiting approval.
                    <a href="{{ route('accounts.debit-requests.index', ['status' => 'pending']) }}" class="alert-link">
                        View Requests →
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>
@endif

<!-- Recent Transactions -->
<div class="row">
    <div class="col-lg-12">
        <div class="card bg-white border-0 rounded-3 mb-4">
            <div class="card-body p-4">
                <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-3">
                    <h3 class="mb-0">Recent Transactions</h3>
                    <a href="{{ route('accounts.transactions') }}" class="text-decoration-none">
                        View All <span class="material-symbols-outlined" style="vertical-align: middle; font-size: 18px;">arrow_forward</span>
                    </a>
                </div>
                
                <div class="default-table-area all-projects">
                    <div class="table-responsive">
                        <table class="table align-middle">
                            <thead>
                                <tr>
                                    <th scope="col">Date</th>
                                    <th scope="col">Branch</th>
                                    <th scope="col">Type</th>
                                    <th scope="col">Category</th>
                                    <th scope="col">Description</th>
                                    <th scope="col" class="text-end">Amount</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($transactions as $transaction)
                                <tr>
                                    <td class="text-body">{{ $transaction->transaction_date->format('M d, Y') }}</td>
                                    <td>{{ $transaction->branch->name }}</td>
                                    <td>
                                        @if($transaction->type === 'income')
                                        <span class="badge bg-success">Income</span>
                                        @else
                                        <span class="badge bg-danger">Expense</span>
                                        @endif
                                    </td>
                                    <td class="text-body">
                                        {{ str_replace('_', ' ', ucwords($transaction->category, '_')) }}
                                    </td>
                                    <td class="text-secondary">{{ Str::limit($transaction->description, 50) }}</td>
                                    <td class="text-end">
                                        <span class="fw-semibold {{ $transaction->type === 'income' ? 'text-success' : 'text-danger' }}">
                                            {{ $transaction->type === 'income' ? '+' : '-' }}₦{{ number_format($transaction->amount, 2) }}
                                        </span>
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="6" class="text-center text-muted py-4">No transactions yet</td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
