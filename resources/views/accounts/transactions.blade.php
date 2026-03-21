@extends('layouts.app')

@section('title', 'Account Transactions')

@section('content')
<div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-4">
    <h3 class="mb-0">Account Transactions</h3>

    <nav style="--bs-breadcrumb-divider: '>';" aria-label="breadcrumb">
        <ol class="breadcrumb align-items-center mb-0 lh-1">
            <li class="breadcrumb-item">
                <a href="{{ route('dashboard') }}" class="d-flex align-items-center text-decoration-none">
                    <i class="ri-home-4-line fs-18 text-primary me-1"></i>
                    <span class="text-secondary fw-medium hover">Dashboard</span>
                </a>
            </li>
            <li class="breadcrumb-item">
                <a href="{{ route('accounts.index') }}" class="text-decoration-none">Account Management</a>
            </li>
            <li class="breadcrumb-item active" aria-current="page">
                <span class="fw-medium">Transactions</span>
            </li>
        </ol>
    </nav>
</div>

<!-- Balance Display -->
<div class="row">
    <div class="col-lg-12">
        <div class="card bg-white border-0 rounded-3 mb-4" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
            <div class="card-body p-4">
                <div class="d-flex justify-content-between align-items-center text-white">
                    <div>
                        <p class="mb-2 text-white-50">Current Balance</p>
                        <h2 class="mb-0 text-white">₦{{ number_format($balance, 2) }}</h2>
                    </div>
                    <div>
                        <span class="material-symbols-outlined" style="font-size: 60px; opacity: 0.3;">account_balance_wallet</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-lg-12">
        <div class="card bg-white border-0 rounded-3 mb-4">
            <div class="card-body p-4">
                <h3 class="mb-4">Transaction History</h3>

                <!-- Filters -->
                <form method="GET" action="{{ route('accounts.transactions') }}" class="row g-3 mb-4">
                    <!-- Type Filter -->
                    <div class="col-md-2">
                        <label class="form-label fw-semibold">Type</label>
                        <select name="type" class="form-select" onchange="this.form.submit()">
                            <option value="">All Types</option>
                            <option value="income" {{ request('type') == 'income' ? 'selected' : '' }}>
                                Income
                            </option>
                            <option value="expense" {{ request('type') == 'expense' ? 'selected' : '' }}>
                                Expense
                            </option>
                        </select>
                    </div>

                    <!-- From Date -->
                    <div class="col-md-2">
                        <label class="form-label fw-semibold">From Date</label>
                        <input type="date" 
                               name="from_date" 
                               class="form-control" 
                               value="{{ request('from_date') }}"
                               onchange="this.form.submit()">
                    </div>

                    <!-- To Date -->
                    <div class="col-md-2">
                        <label class="form-label fw-semibold">To Date</label>
                        <input type="date" 
                               name="to_date" 
                               class="form-control" 
                               value="{{ request('to_date') }}"
                               onchange="this.form.submit()">
                    </div>

                    @can('approve debit requests')
                    <!-- Branch Filter -->
                    <div class="col-md-3">
                        <label class="form-label fw-semibold">Branch</label>
                        <select name="branch_id" class="form-select" onchange="this.form.submit()">
                            <option value="">All Branches</option>
                            @foreach(\App\Models\Branch::all() as $branch)
                            <option value="{{ $branch->id }}" {{ request('branch_id') == $branch->id ? 'selected' : '' }}>
                                {{ $branch->name }}
                            </option>
                            @endforeach
                        </select>
                    </div>
                    @endcan

                    <!-- Clear Filters -->
                    <div class="col-md-2 d-flex align-items-end">
                        <a href="{{ route('accounts.transactions') }}" class="btn btn-outline-secondary w-100">
                            <span class="material-symbols-outlined me-1" style="vertical-align: middle; font-size: 18px;">refresh</span>
                            Clear
                        </a>
                    </div>
                </form>

                <div class="default-table-area all-projects">
                    <div class="table-responsive">
                        <table class="table align-middle">
                            <thead>
                                <tr>
                                    <th scope="col">Date</th>
                                    <th scope="col">Branch</th>
                                    <th scope="col">Type</th>
                                    <th scope="col">Category</th>
                                    <th scope="col">Reference</th>
                                    <th scope="col">Description</th>
                                    <th scope="col">Recorded By</th>
                                    <th scope="col" class="text-end">Amount</th>
                                    <th scope="col" class="text-end">Document</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($transactions as $transaction)
                                <tr>
                                    <td class="text-body">
                                        <span class="fw-medium">{{ $transaction->transaction_date->format('M d, Y') }}</span>
                                    </td>
                                    <td>
                                        <span class="badge bg-secondary">
                                            {{ $transaction->branch->name }}
                                        </span>
                                    </td>
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
                                    <td>
                                        @if($transaction->reference)
                                        <code class="bg-light px-2 py-1 rounded small">{{ $transaction->reference }}</code>
                                        @else
                                        <span class="text-secondary">-</span>
                                        @endif
                                    </td>
                                    <td class="text-secondary" style="max-width: 250px;">
                                        {{ Str::limit($transaction->description, 50) }}
                                    </td>
                                    <td class="text-body">{{ $transaction->recordedBy->name }}</td>
                                    <td class="text-end">
                                        <span class="fw-semibold {{ $transaction->type === 'income' ? 'text-success' : 'text-danger' }}">
                                            {{ $transaction->type === 'income' ? '+' : '-' }}₦{{ number_format($transaction->amount, 2) }}
                                        </span>
                                    </td>
                                    <td class="text-end">
                                        @if($transaction->receipt_document)
                                        <a href="{{ asset('storage/' . $transaction->receipt_document) }}" 
                                           target="_blank"
                                           class="btn btn-outline-primary btn-sm">
                                            <span class="material-symbols-outlined" style="font-size: 18px;">description</span>
                                        </a>
                                        @else
                                        <span class="text-secondary">-</span>
                                        @endif
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="9" class="text-center py-5 text-muted">
                                        <span class="material-symbols-outlined d-block mb-2" style="font-size: 48px;">receipt_long</span>
                                        No transactions found
                                        @if(request()->hasAny(['type', 'from_date', 'to_date', 'branch_id']))
                                        <br>
                                        <small>Try adjusting your filters</small>
                                        @endif
                                    </td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Pagination -->
                @if($transactions->hasPages())
                <div class="mt-4">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <p class="text-secondary mb-0">
                                Showing {{ $transactions->firstItem() }} to {{ $transactions->lastItem() }} of {{ $transactions->total() }} transactions
                            </p>
                        </div>
                        <div>
                            {{ $transactions->links() }}
                        </div>
                    </div>
                </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection
