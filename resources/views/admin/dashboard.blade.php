@extends('layouts.app')

@section('title', 'Dashboard')

@section('content')
<div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-4">
    <h3 class="mb-0">Dashboard</h3>

    <nav style="--bs-breadcrumb-divider: '>';" aria-label="breadcrumb">
        <ol class="breadcrumb align-items-center mb-0 lh-1">
            <li class="breadcrumb-item">
                <a href="{{ route('dashboard') }}" class="d-flex align-items-center text-decoration-none">
                    <i class="ri-home-4-line fs-18 text-primary me-1"></i>
                    <span class="text-secondary fw-medium hover">Dashboard</span>
                </a>
            </li>
            <li class="breadcrumb-item active" aria-current="page">
                <span class="fw-medium">Overview</span>
            </li>
        </ol>
    </nav>
</div>

<!-- Financial Summary - This Month -->
<div class="row">
    <div class="col-xxl-3 col-sm-6">
        <div class="card bg-white border-0 rounded-3 mb-4">
            <div class="card-body p-4">
                <div class="d-flex align-items-center justify-content-between">
                    <div class="flex-grow-1">
                        <span class="d-block fw-normal text-secondary mb-1">Income (This Month)</span>
                        <h3 class="mb-0 fs-20 text-success">₦{{ number_format($stats['income_this_month'], 2) }}</h3>
                    </div>
                    <div class="flex-shrink-0">
                        <div class="d-flex align-items-center justify-content-center wh-77 rounded-circle" style="background-color: rgba(76, 175, 80, 0.1);">
                            <span class="material-symbols-outlined fs-32 text-success">trending_up</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-xxl-3 col-sm-6">
        <div class="card bg-white border-0 rounded-3 mb-4">
            <div class="card-body p-4">
                <div class="d-flex align-items-center justify-content-between">
                    <div class="flex-grow-1">
                        <span class="d-block fw-normal text-secondary mb-1">Expenses (This Month)</span>
                        <h3 class="mb-0 fs-20 text-danger">₦{{ number_format($stats['expenses_this_month'], 2) }}</h3>
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
    
    <div class="col-xxl-3 col-sm-6">
        <div class="card bg-white border-0 rounded-3 mb-4">
            <div class="card-body p-4">
                <div class="d-flex align-items-center justify-content-between">
                    <div class="flex-grow-1">
                        <span class="d-block fw-normal text-secondary mb-1">Net Profit (This Month)</span>
                        <h3 class="mb-0 fs-20 {{ $stats['net_this_month'] >= 0 ? 'text-success' : 'text-danger' }}">
                            ₦{{ number_format($stats['net_this_month'], 2) }}
                        </h3>
                    </div>
                    <div class="flex-shrink-0">
                        <div class="d-flex align-items-center justify-content-center wh-77 rounded-circle" style="background-color: rgba(116, 66, 162, 0.1);">
                            <span class="material-symbols-outlined fs-32 text-primary">payments</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-xxl-3 col-sm-6">
        <div class="card bg-white border-0 rounded-3 mb-4">
            <div class="card-body p-4">
                <div class="d-flex align-items-center justify-content-between">
                    <div class="flex-grow-1">
                        <span class="d-block fw-normal text-secondary mb-1">Company Balance</span>
                        <h3 class="mb-0 fs-20 {{ $stats['company_balance'] >= 0 ? 'text-success' : 'text-danger' }}">
                            ₦{{ number_format($stats['company_balance'], 2) }}
                        </h3>
                    </div>
                    <div class="flex-shrink-0">
                        <div class="d-flex align-items-center justify-content-center wh-77 rounded-circle" style="background-color: rgba(23, 162, 184, 0.1);">
                            <span class="material-symbols-outlined fs-32 text-info">account_balance</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Operational Stats -->
<div class="row">
    <div class="col-xxl-3 col-sm-6">
        <div class="card bg-white border-0 rounded-3 mb-4">
            <div class="card-body p-4">
                <div class="d-flex align-items-center justify-content-between">
                    <div class="flex-grow-1">
                        <span class="d-block fw-normal text-secondary mb-1">Total Drivers</span>
                        <h3 class="mb-0 fs-20 text-primary">{{ $stats['total_drivers'] }}</h3>
                    </div>
                    <div class="flex-shrink-0">
                        <div class="d-flex align-items-center justify-content-center wh-77 rounded-circle" style="background-color: rgba(116, 66, 162, 0.1);">
                            <span class="material-symbols-outlined fs-32 text-primary">group</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-xxl-3 col-sm-6">
        <div class="card bg-white border-0 rounded-3 mb-4">
            <div class="card-body p-4">
                <div class="d-flex align-items-center justify-content-between">
                    <div class="flex-grow-1">
                        <span class="d-block fw-normal text-secondary mb-1">Total Vehicles</span>
                        <h3 class="mb-0 fs-20 text-success">{{ $stats['total_vehicles'] }}</h3>
                    </div>
                    <div class="flex-shrink-0">
                        <div class="d-flex align-items-center justify-content-center wh-77 rounded-circle" style="background-color: rgba(76, 175, 80, 0.1);">
                            <span class="material-symbols-outlined fs-32 text-success">directions_car</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-xxl-3 col-sm-6">
        <div class="card bg-white border-0 rounded-3 mb-4">
            <div class="card-body p-4">
                <div class="d-flex align-items-center justify-content-between">
                    <div class="flex-grow-1">
                        <span class="d-block fw-normal text-secondary mb-1">Active Assignments</span>
                        <h3 class="mb-0 fs-20 text-info">{{ $stats['active_assignments'] }}</h3>
                    </div>
                    <div class="flex-shrink-0">
                        <div class="d-flex align-items-center justify-content-center wh-77 rounded-circle" style="background-color: rgba(23, 162, 184, 0.1);">
                            <span class="material-symbols-outlined fs-32 text-info">swap_horiz</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-xxl-3 col-sm-6">
        <div class="card bg-white border-0 rounded-3 mb-4">
            <div class="card-body p-4">
                <div class="d-flex align-items-center justify-content-between">
                    <div class="flex-grow-1">
                        <span class="d-block fw-normal text-secondary mb-1">Pending Payments</span>
                        <h3 class="mb-0 fs-18 text-warning">{{ $stats['pending_payments'] }}</h3>
                        <span class="d-block text-muted fs-12 mt-1">₦{{ number_format($stats['pending_payments_amount'], 2) }}</span>
                    </div>
                    <div class="flex-shrink-0">
                        <div class="d-flex align-items-center justify-content-center wh-77 rounded-circle" style="background-color: rgba(255, 193, 7, 0.1);">
                            <span class="material-symbols-outlined fs-32 text-warning">schedule</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Additional Financial Stats -->
<div class="row">
    <div class="col-xxl-3 col-sm-6">
        <div class="card bg-white border-0 rounded-3 mb-4">
            <div class="card-body p-4">
                <div class="d-flex align-items-center justify-content-between">
                    <div class="flex-grow-1">
                        <span class="d-block fw-normal text-secondary mb-1">Total Income (All Time)</span>
                        <h3 class="mb-0 fs-20 text-success">₦{{ number_format($stats['total_income'], 2) }}</h3>
                    </div>
                    <div class="flex-shrink-0">
                        <div class="d-flex align-items-center justify-content-center wh-77 rounded-circle" style="background-color: rgba(76, 175, 80, 0.1);">
                            <span class="material-symbols-outlined fs-32 text-success">monetization_on</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-xxl-3 col-sm-6">
        <div class="card bg-white border-0 rounded-3 mb-4">
            <div class="card-body p-4">
                <div class="d-flex align-items-center justify-content-between">
                    <div class="flex-grow-1">
                        <span class="d-block fw-normal text-secondary mb-1">Total Expenses (All Time)</span>
                        <h3 class="mb-0 fs-20 text-danger">₦{{ number_format($stats['total_expenses'], 2) }}</h3>
                    </div>
                    <div class="flex-shrink-0">
                        <div class="d-flex align-items-center justify-content-center wh-77 rounded-circle" style="background-color: rgba(220, 53, 69, 0.1);">
                            <span class="material-symbols-outlined fs-32 text-danger">money_off</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-xxl-3 col-sm-6">
        <div class="card bg-white border-0 rounded-3 mb-4">
            <div class="card-body p-4">
                <div class="d-flex align-items-center justify-content-between">
                    <div class="flex-grow-1">
                        <span class="d-block fw-normal text-secondary mb-1">Total Wallet Balance</span>
                        <h3 class="mb-0 fs-20 text-info">₦{{ number_format($stats['total_wallet_balance'], 2) }}</h3>
                        <span class="d-block text-muted fs-12 mt-1">Drivers' Combined Balance</span>
                    </div>
                    <div class="flex-shrink-0">
                        <div class="d-flex align-items-center justify-content-center wh-77 rounded-circle" style="background-color: rgba(23, 162, 184, 0.1);">
                            <span class="material-symbols-outlined fs-32 text-info">account_balance_wallet</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-xxl-3 col-sm-6">
        <div class="card bg-white border-0 rounded-3 mb-4">
            <div class="card-body p-4">
                <div class="d-flex align-items-center justify-content-between">
                    <div class="flex-grow-1">
                        <span class="d-block fw-normal text-secondary mb-1">Profit Margin</span>
                        <h3 class="mb-0 fs-20 text-primary">
                            @if($stats['total_income'] > 0)
                                {{ number_format((($stats['total_income'] - $stats['total_expenses']) / $stats['total_income']) * 100, 1) }}%
                            @else
                                0%
                            @endif
                        </h3>
                        <span class="d-block text-muted fs-12 mt-1">All Time Average</span>
                    </div>
                    <div class="flex-shrink-0">
                        <div class="d-flex align-items-center justify-content-center wh-77 rounded-circle" style="background-color: rgba(116, 66, 162, 0.1);">
                            <span class="material-symbols-outlined fs-32 text-primary">analytics</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-lg-6">
        <div class="card bg-white border-0 rounded-3 mb-4">
            <div class="card-body p-4">
                <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-3">
                    <h3 class="mb-0">Recent Transactions</h3>
                </div>
                
                <div class="default-table-area all-projects">
                    <div class="table-responsive">
                        <table class="table align-middle">
                            <thead>
                                <tr>
                                    <th scope="col">Driver</th>
                                    <th scope="col">Type</th>
                                    <th scope="col">Amount</th>
                                    <th scope="col">Status</th>
                                    <th scope="col">Date</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($recentTransactions as $transaction)
                                <tr>
                                    <td>{{ $transaction->driver->full_name }}</td>
                                    <td>
                                        <span class="badge bg-secondary">
                                            {{ str_replace('_', ' ', ucwords($transaction->type)) }}
                                        </span>
                                    </td>
                                    <td class="text-body">₦{{ number_format($transaction->amount, 2) }}</td>
                                    <td>
                                        @if($transaction->status == 'successful')
                                        <span class="badge bg-success">Success</span>
                                        @elseif($transaction->status == 'pending')
                                        <span class="badge bg-warning">Pending</span>
                                        @else
                                        <span class="badge bg-danger">Rejected</span>
                                        @endif
                                    </td>
                                    <td class="text-body">{{ $transaction->created_at->format('M d, Y') }}</td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="5" class="text-center text-muted py-4">No transactions yet</td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-lg-6">
        <div class="card bg-white border-0 rounded-3 mb-4">
            <div class="card-body p-4">
                <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-3">
                    <h3 class="mb-0">Active Vehicle Assignments</h3>
                </div>
                
                <div class="default-table-area all-projects">
                    <div class="table-responsive">
                        <table class="table align-middle">
                            <thead>
                                <tr>
                                    <th scope="col">Driver</th>
                                    <th scope="col">Vehicle</th>
                                    <th scope="col">Assigned</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($activeAssignments as $assignment)
                                <tr>
                                    <td class="text-body">{{ $assignment->driver->full_name }}</td>
                                    <td>
                                        <span class="fw-semibold d-block">{{ $assignment->vehicle->plate_number }}</span>
                                        <span class="fs-12 text-muted">{{ $assignment->vehicle->make }} {{ $assignment->vehicle->model }}</span>
                                    </td>
                                    <td class="text-body">{{ $assignment->assigned_at->format('M d, Y') }}</td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="3" class="text-center text-muted py-4">No active assignments</td>
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
