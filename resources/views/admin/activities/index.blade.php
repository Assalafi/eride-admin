@extends('layouts.app')

@section('title', 'System Activities & Requests')

@section('content')
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-4">
        <h3 class="mb-0">System Activities & Requests</h3>

        <nav style="--bs-breadcrumb-divider: '>';" aria-label="breadcrumb">
            <ol class="breadcrumb align-items-center mb-0 lh-1">
                <li class="breadcrumb-item">
                    <a href="{{ route('dashboard') }}" class="d-flex align-items-center text-decoration-none">
                        <i class="ri-home-4-line fs-18 text-primary me-1"></i>
                        <span class="text-secondary fw-medium hover">Dashboard</span>
                    </a>
                </li>
                <li class="breadcrumb-item active" aria-current="page">
                    <span class="fw-medium">Activities</span>
                </li>
            </ol>
        </nav>
    </div>

    <!-- Statistics Cards -->
    <div class="row mb-4">
        <div class="col-xl-2 col-lg-3 col-sm-6">
            <div class="card bg-white border-0 rounded-3 mb-4">
                <div class="card-body p-4">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <span class="text-secondary d-block mb-1">Pending Maintenance</span>
                            <h3 class="mb-0 text-warning">{{ $stats['pending_maintenance'] }}</h3>
                        </div>
                        <div class="rounded-circle wh-60 bg-warning-div d-flex align-items-center justify-content-center">
                            <span class="material-symbols-outlined text-warning fs-32">build</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-2 col-lg-3 col-sm-6">
            <div class="card bg-white border-0 rounded-3 mb-4">
                <div class="card-body p-4">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <span class="text-secondary d-block mb-1">Pending Payments</span>
                            <h3 class="mb-0 text-info">{{ $stats['pending_payments'] }}</h3>
                        </div>
                        <div class="rounded-circle wh-60 bg-info-div d-flex align-items-center justify-content-center">
                            <span class="material-symbols-outlined text-info fs-32">payments</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-2 col-lg-3 col-sm-6">
            <div class="card bg-white border-0 rounded-3 mb-4">
                <div class="card-body p-4">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <span class="text-secondary d-block mb-1">Active Assignments</span>
                            <h3 class="mb-0 text-success">{{ $stats['active_assignments'] }}</h3>
                        </div>
                        <div class="rounded-circle wh-60 bg-success-div d-flex align-items-center justify-content-center">
                            <span class="material-symbols-outlined text-success fs-32">swap_horiz</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-2 col-lg-3 col-sm-6">
            <div class="card bg-white border-0 rounded-3 mb-4">
                <div class="card-body p-4">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <span class="text-secondary d-block mb-1">Pending Charging</span>
                            <h3 class="mb-0 text-danger">{{ $stats['pending_charging'] }}</h3>
                        </div>
                        <div class="rounded-circle wh-60 bg-danger-div d-flex align-items-center justify-content-center">
                            <span class="material-symbols-outlined text-danger fs-32">ev_station</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-2 col-lg-3 col-sm-6">
            <div class="card bg-white border-0 rounded-3 mb-4">
                <div class="card-body p-4">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <span class="text-secondary d-block mb-1">In Progress Charging</span>
                            <h3 class="mb-0" style="color: #17a2b8;">{{ $stats['in_progress_charging'] }}</h3>
                        </div>
                        <div class="rounded-circle wh-60 d-flex align-items-center justify-content-center"
                            style="background-color: rgba(23, 162, 184, 0.1);">
                            <span class="material-symbols-outlined fs-32" style="color: #17a2b8;">electric_bolt</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-2 col-lg-3 col-sm-6">
            <div class="card bg-white border-0 rounded-3 mb-4">
                <div class="card-body p-4">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <span class="text-secondary d-block mb-1">Wallet Funding</span>
                            <h3 class="mb-0" style="color: #28a745;">{{ $stats['pending_wallet_funding'] }}</h3>
                        </div>
                        <div class="rounded-circle wh-60 d-flex align-items-center justify-content-center"
                            style="background-color: rgba(40, 167, 69, 0.1);">
                            <span class="material-symbols-outlined fs-32"
                                style="color: #28a745;">account_balance_wallet</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Quick Actions -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card bg-white border-0 rounded-3">
                <div class="card-body p-4">
                    <h5 class="mb-3">Quick Links</h5>
                    <div class="d-flex flex-wrap gap-2">
                        <a href="{{ route('admin.maintenance.index') }}" class="btn btn-outline-warning">
                            <span class="material-symbols-outlined me-2"
                                style="vertical-align: middle; font-size: 18px;">build</span>
                            View All Maintenance
                        </a>
                        <a href="{{ route('admin.payments.index') }}" class="btn btn-outline-info">
                            <span class="material-symbols-outlined me-2"
                                style="vertical-align: middle; font-size: 18px;">payments</span>
                            View All Payments
                        </a>
                        <a href="{{ route('admin.assignments.index') }}" class="btn btn-outline-success">
                            <span class="material-symbols-outlined me-2"
                                style="vertical-align: middle; font-size: 18px;">swap_horiz</span>
                            View All Assignments
                        </a>
                        <a href="{{ route('admin.charging.index') }}" class="btn btn-outline-danger">
                            <span class="material-symbols-outlined me-2"
                                style="vertical-align: middle; font-size: 18px;">ev_station</span>
                            View All Charging
                        </a>
                        @can('approve payments')
                            <a href="{{ route('admin.wallet-funding.index') }}" class="btn btn-outline-success">
                                <span class="material-symbols-outlined me-2"
                                    style="vertical-align: middle; font-size: 18px;">account_balance_wallet</span>
                                View Wallet Funding
                            </a>
                        @endcan
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Tabs -->
    <ul class="nav nav-tabs mb-3" role="tablist">
        <li class="nav-item" role="presentation">
            <button class="nav-link active" id="maintenance-tab" data-bs-toggle="tab" data-bs-target="#maintenance"
                type="button">
                <span class="material-symbols-outlined me-1" style="vertical-align: middle; font-size: 18px;">build</span>
                Maintenance Requests
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="payments-tab" data-bs-toggle="tab" data-bs-target="#payments" type="button">
                <span class="material-symbols-outlined me-1"
                    style="vertical-align: middle; font-size: 18px;">payments</span>
                Pending Payments
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="assignments-tab" data-bs-toggle="tab" data-bs-target="#assignments"
                type="button">
                <span class="material-symbols-outlined me-1"
                    style="vertical-align: middle; font-size: 18px;">swap_horiz</span>
                Recent Assignments
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="charging-tab" data-bs-toggle="tab" data-bs-target="#charging" type="button">
                <span class="material-symbols-outlined me-1"
                    style="vertical-align: middle; font-size: 18px;">ev_station</span>
                Charging Requests
            </button>
        </li>
        @can('approve payments')
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="wallet-funding-tab" data-bs-toggle="tab" data-bs-target="#wallet-funding"
                    type="button">
                    <span class="material-symbols-outlined me-1"
                        style="vertical-align: middle; font-size: 18px;">account_balance_wallet</span>
                    Wallet Funding
                </button>
            </li>
        @endcan
    </ul>

    <!-- Tab Content -->
    <div class="tab-content">
        <!-- Maintenance Requests Tab -->
        <div class="tab-pane fade show active" id="maintenance" role="tabpanel">
            <div class="card bg-white border-0 rounded-3">
                <div class="card-body p-0">
                    <div class="default-table-area">
                        <div class="table-responsive">
                            <table class="table align-middle">
                                <thead>
                                    <tr>
                                        <th>#</th>
                                        <th>Driver</th>
                                        <th>Vehicle</th>
                                        <th>Description</th>
                                        <th>Cost</th>
                                        <th>Status</th>
                                        <th>Date</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($maintenanceRequests as $request)
                                        <tr>
                                            <td>{{ $loop->iteration }}</td>
                                            <td>{{ $request->driver ? $request->driver->full_name : 'N/A' }}</td>
                                            <td>{{ $request->vehicle ? $request->vehicle->plate_number : 'N/A' }}</td>
                                            <td>{{ Str::limit($request->issue_description ?? 'N/A', 30) }}</td>
                                            <td>₦{{ number_format($request->total_cost ?? 0, 2) }}</td>
                                            <td>
                                                <span
                                                    class="badge 
                                            @if ($request->status === 'pending_manager_approval') bg-warning
                                            @elseif($request->status === 'pending_store_approval') bg-info
                                            @elseif($request->status === 'completed') bg-success
                                            @elseif($request->status === 'manager_denied') bg-danger
                                            @else bg-secondary @endif">
                                                    {{ str_replace('_', ' ', ucwords($request->status, '_')) }}
                                                </span>
                                            </td>
                                            <td>{{ $request->created_at->format('M d, Y') }}</td>
                                            <td>
                                                <a href="{{ route('admin.maintenance.show', $request) }}"
                                                    class="btn btn-sm btn-primary">
                                                    <i class="ri-eye-line"></i>
                                                </a>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="8" class="text-center py-4 text-muted">No maintenance requests
                                                found</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Pending Payments Tab -->
        <div class="tab-pane fade" id="payments" role="tabpanel">
            <div class="card bg-white border-0 rounded-3">
                <div class="card-body p-0">
                    <div class="default-table-area">
                        <div class="table-responsive">
                            <table class="table align-middle">
                                <thead>
                                    <tr>
                                        <th>#</th>
                                        <th>Driver</th>
                                        <th>Reference</th>
                                        <th>Amount</th>
                                        <th>Description</th>
                                        <th>Date</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($pendingPayments as $payment)
                                        <tr>
                                            <td>{{ $loop->iteration }}</td>
                                            <td>{{ $payment->driver->full_name }}</td>
                                            <td><code>{{ $payment->reference ?? 'N/A' }}</code></td>
                                            <td><strong>₦{{ number_format($payment->amount, 2) }}</strong></td>
                                            <td>{{ $payment->description ?? 'Daily remittance' }}</td>
                                            <td>{{ $payment->created_at->format('M d, Y H:i') }}</td>
                                            <td>
                                                <div class="d-flex gap-1">
                                                    @can('approve payments')
                                                        <form action="{{ route('admin.payments.approve', $payment) }}"
                                                            method="POST" class="d-inline">
                                                            @csrf
                                                            <button type="submit" class="btn btn-sm btn-success"
                                                                title="Approve">
                                                                <i class="ri-check-line"></i>
                                                            </button>
                                                        </form>
                                                        <form action="{{ route('admin.payments.reject', $payment) }}"
                                                            method="POST" class="d-inline">
                                                            @csrf
                                                            <button type="submit" class="btn btn-sm btn-danger"
                                                                title="Reject">
                                                                <i class="ri-close-line"></i>
                                                            </button>
                                                        </form>
                                                    @endcan
                                                </div>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="7" class="text-center py-4 text-muted">No pending payments
                                                found</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Recent Assignments Tab -->
        <div class="tab-pane fade" id="assignments" role="tabpanel">
            <div class="card bg-white border-0 rounded-3">
                <div class="card-body p-0">
                    <div class="default-table-area">
                        <div class="table-responsive">
                            <table class="table align-middle">
                                <thead>
                                    <tr>
                                        <th>#</th>
                                        <th>Driver</th>
                                        <th>Vehicle</th>
                                        <th>Assigned Date</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($recentAssignments as $assignment)
                                        <tr>
                                            <td>{{ $loop->iteration }}</td>
                                            <td>{{ $assignment->driver->full_name }}</td>
                                            <td><strong>{{ $assignment->vehicle->plate_number }}</strong></td>
                                            <td>{{ $assignment->assigned_at->format('M d, Y') }}</td>
                                            <td>
                                                <span class="badge bg-success">Active</span>
                                            </td>
                                            <td>
                                                @can('return vehicles')
                                                    <form action="{{ route('admin.assignments.return', $assignment) }}"
                                                        method="POST" class="d-inline">
                                                        @csrf
                                                        <button type="submit" class="btn btn-sm btn-warning"
                                                            title="Return Vehicle"
                                                            onclick="return confirm('Return this vehicle?')">
                                                            <i class="ri-arrow-go-back-line"></i> Return
                                                        </button>
                                                    </form>
                                                @endcan
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="6" class="text-center py-4 text-muted">No active assignments
                                                found</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Charging Requests Tab -->
        <div class="tab-pane fade" id="charging" role="tabpanel">
            <div class="card bg-white border-0 rounded-3">
                <div class="card-body p-0">
                    <div class="default-table-area">
                        <div class="table-responsive">
                            <table class="table align-middle">
                                <thead>
                                    <tr>
                                        <th>#</th>
                                        <th>Driver</th>
                                        <th>Vehicle</th>
                                        <th>Battery %</th>
                                        <th>Cost</th>
                                        <th>Status</th>
                                        <th>Date</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($chargingRequests as $request)
                                        <tr>
                                            <td>{{ $loop->iteration }}</td>
                                            <td>{{ $request->driver->full_name }}</td>
                                            <td><strong>{{ $request->vehicle->plate_number }}</strong></td>
                                            <td>
                                                @if ($request->battery_level_before)
                                                    <span
                                                        class="badge bg-warning">{{ number_format($request->battery_level_before, 0) }}%</span>
                                                    @if ($request->battery_level_after)
                                                        → <span
                                                            class="badge bg-success">{{ number_format($request->battery_level_after, 0) }}%</span>
                                                    @endif
                                                @else
                                                    <span class="text-muted">—</span>
                                                @endif
                                            </td>
                                            <td>
                                                <div>
                                                    <strong>₦{{ number_format($request->charging_cost, 2) }}</strong>
                                                    <br>
                                                    <small class="text-muted">
                                                        (¥{{ number_format($request->charging_cost / 203, 2) }})
                                                    </small>
                                                </div>
                                            </td>
                                            <td>
                                                <span
                                                    class="badge 
                                            @if ($request->status === 'pending') bg-warning
                                            @elseif($request->status === 'in_progress') bg-info
                                            @elseif($request->status === 'completed') bg-success
                                            @else bg-secondary @endif">
                                                    {{ ucfirst(str_replace('_', ' ', $request->status)) }}
                                                </span>
                                            </td>
                                            <td>{{ $request->created_at->format('M d, Y') }}</td>
                                            <td>
                                                <a href="{{ route('admin.charging.show', $request) }}"
                                                    class="btn btn-sm btn-primary">
                                                    <i class="ri-eye-line"></i> View
                                                </a>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="8" class="text-center py-4 text-muted">No charging requests
                                                found</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Wallet Funding Tab -->
        @can('approve payments')
            <div class="tab-pane fade" id="wallet-funding" role="tabpanel">
                <div class="card bg-white border-0 rounded-3">
                    <div class="card-body p-0">
                        <div class="default-table-area">
                            <div class="table-responsive">
                                <table class="table align-middle">
                                    <thead>
                                        <tr>
                                            <th>#</th>
                                            <th>Driver</th>
                                            <th>Amount</th>
                                            <th>Date</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse($walletFundingRequests as $request)
                                            <tr>
                                                <td>{{ $loop->iteration }}</td>
                                                <td>{{ $request->driver->full_name }}</td>
                                                <td class="fw-bold text-primary">₦{{ number_format($request->amount, 2) }}
                                                </td>
                                                <td>{{ $request->created_at->format('M d, Y') }}</td>
                                                <td>
                                                    <a href="{{ route('admin.wallet-funding.show', $request) }}"
                                                        class="btn btn-sm btn-primary">
                                                        <i class="ri-eye-line"></i> View
                                                    </a>
                                                </td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="5" class="text-center py-4 text-muted">No pending wallet
                                                    funding requests</td>
                                            </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        @endcan
    </div>
@endsection
