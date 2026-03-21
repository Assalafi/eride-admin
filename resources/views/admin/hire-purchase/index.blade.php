@extends('layouts.app')

@section('title', 'Hire Purchase Management')

@push('styles')
    <link rel="stylesheet" href="{{ asset('assets/css/data-table.css') }}">
    <style>
        .stat-card {
            transition: transform 0.2s;
        }

        .stat-card:hover {
            transform: translateY(-2px);
        }

        .progress-thin {
            height: 6px;
        }

        .overdue-badge {
            animation: pulse 2s infinite;
        }

        @keyframes pulse {

            0%,
            100% {
                opacity: 1;
            }

            50% {
                opacity: 0.7;
            }
        }
    </style>
@endpush

@section('content')
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-4">
        <h3 class="mb-0">
            <i class="ri-car-line me-2"></i>Hire Purchase Management
        </h3>

        <div class="d-flex gap-2">
            <a href="{{ route('admin.hire-purchase.create') }}" class="btn btn-primary">
                <i class="ri-add-line me-1"></i>New Contract
            </a>
            <a href="{{ route('admin.hire-purchase.export', request()->query()) }}" class="btn btn-outline-primary">
                <i class="ri-file-pdf-line me-1"></i>Export PDF
            </a>
        </div>
    </div>

    <!-- Summary Cards -->
    <div class="row g-3 mb-4">
        <div class="col-xl-3 col-md-6">
            <div class="card bg-white border-0 rounded-3 h-100 stat-card">
                <div class="card-body p-4">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <p class="text-secondary mb-1 fw-medium">Total Contracts</p>
                            <h3 class="mb-0">{{ number_format($summary['total_contracts']) }}</h3>
                            <small class="text-success">{{ $summary['active_contracts'] }} active</small>
                        </div>
                        <div class="bg-primary bg-opacity-10 rounded-3 p-3">
                            <i class="ri-file-list-3-line text-primary fs-24"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6">
            <div class="card bg-white border-0 rounded-3 h-100 stat-card">
                <div class="card-body p-4">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <p class="text-secondary mb-1 fw-medium">Total Vehicle Value</p>
                            <h3 class="mb-0 text-info">₦{{ number_format($summary['total_vehicle_value']) }}</h3>
                            <small class="text-muted">Fleet value under HP</small>
                        </div>
                        <div class="bg-info bg-opacity-10 rounded-3 p-3">
                            <i class="ri-car-line text-info fs-24"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6">
            <div class="card bg-white border-0 rounded-3 h-100 stat-card">
                <div class="card-body p-4">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <p class="text-secondary mb-1 fw-medium">Total Collected</p>
                            <h3 class="mb-0 text-success">₦{{ number_format($summary['total_collected']) }}</h3>
                            <small class="text-muted">All time payments</small>
                        </div>
                        <div class="bg-success bg-opacity-10 rounded-3 p-3">
                            <i class="ri-money-dollar-circle-line text-success fs-24"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6">
            <div class="card bg-white border-0 rounded-3 h-100 stat-card">
                <div class="card-body p-4">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <p class="text-secondary mb-1 fw-medium">Outstanding Balance</p>
                            <h3 class="mb-0 text-warning">₦{{ number_format($summary['total_outstanding']) }}</h3>
                            <small class="text-danger">{{ $summary['overdue_contracts'] }} overdue</small>
                        </div>
                        <div class="bg-warning bg-opacity-10 rounded-3 p-3">
                            <i class="ri-wallet-3-line text-warning fs-24"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Second Row Stats -->
    <div class="row g-3 mb-4">
        <div class="col-xl-2 col-md-4">
            <div class="card bg-success bg-opacity-10 border-0 rounded-3">
                <div class="card-body p-3 text-center">
                    <h4 class="mb-0 text-success">{{ $summary['completed_contracts'] }}</h4>
                    <small class="text-muted">Completed</small>
                </div>
            </div>
        </div>
        <div class="col-xl-2 col-md-4">
            <div class="card bg-danger bg-opacity-10 border-0 rounded-3">
                <div class="card-body p-3 text-center">
                    <h4 class="mb-0 text-danger">{{ $summary['defaulted_contracts'] }}</h4>
                    <small class="text-muted">Defaulted</small>
                </div>
            </div>
        </div>
        <div class="col-xl-2 col-md-4">
            <div class="card bg-warning bg-opacity-10 border-0 rounded-3">
                <div class="card-body p-3 text-center">
                    <h4 class="mb-0 text-warning">{{ $summary['overdue_contracts'] }}</h4>
                    <small class="text-muted">Overdue</small>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="card bg-danger bg-opacity-10 border-0 rounded-3">
                <div class="card-body p-3 text-center">
                    <h4 class="mb-0 text-danger">₦{{ number_format($summary['total_penalties']) }}</h4>
                    <small class="text-muted">Total Penalties</small>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="card bg-primary bg-opacity-10 border-0 rounded-3">
                <div class="card-body p-3 text-center">
                    @php
                        $collectionRate =
                            $summary['total_amount_due'] > 0
                                ? round(
                                    ($summary['total_collected'] /
                                        ($summary['total_collected'] + $summary['total_outstanding'])) *
                                        100,
                                    1,
                                )
                                : 0;
                    @endphp
                    <h4 class="mb-0 text-primary">{{ $collectionRate }}%</h4>
                    <small class="text-muted">Collection Rate</small>
                </div>
            </div>
        </div>
    </div>

    <!-- Alerts Section -->
    @if ($overduePayments->count() > 0)
        <div class="alert alert-danger d-flex align-items-center mb-4" role="alert">
            <i class="ri-error-warning-line fs-24 me-3"></i>
            <div>
                <strong>{{ $overduePayments->count() }} Overdue Payments!</strong>
                Some drivers have missed their payment dates. Please follow up immediately.
            </div>
        </div>
    @endif

    @if ($paymentsDueToday->count() > 0)
        <div class="alert alert-warning d-flex align-items-center mb-4" role="alert">
            <i class="ri-time-line fs-24 me-3"></i>
            <div>
                <strong>{{ $paymentsDueToday->count() }} Payments Due Today!</strong>
                Ensure these payments are collected before end of day.
            </div>
        </div>
    @endif

    <!-- Filters -->
    <div class="card bg-white border-0 rounded-3 mb-4">
        <div class="card-body p-4">
            <form method="GET" action="{{ route('admin.hire-purchase.index') }}">
                <div class="row g-3">
                    <div class="col-lg-2 col-md-4">
                        <label class="form-label">Status</label>
                        <select class="form-select" name="status">
                            <option value="">All Status</option>
                            <option value="active" {{ request('status') == 'active' ? 'selected' : '' }}>Active</option>
                            <option value="completed" {{ request('status') == 'completed' ? 'selected' : '' }}>Completed
                            </option>
                            <option value="defaulted" {{ request('status') == 'defaulted' ? 'selected' : '' }}>Defaulted
                            </option>
                            <option value="terminated" {{ request('status') == 'terminated' ? 'selected' : '' }}>Terminated
                            </option>
                            <option value="suspended" {{ request('status') == 'suspended' ? 'selected' : '' }}>Suspended
                            </option>
                        </select>
                    </div>

                    @if (auth()->user()->hasRole('Super Admin'))
                        <div class="col-lg-2 col-md-4">
                            <label class="form-label">Branch</label>
                            <select class="form-select" name="branch_id">
                                <option value="">All Branches</option>
                                @foreach ($branches as $branch)
                                    <option value="{{ $branch->id }}"
                                        {{ request('branch_id') == $branch->id ? 'selected' : '' }}>
                                        {{ $branch->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    @endif

                    <div class="col-lg-2 col-md-4">
                        <label class="form-label">Start Date</label>
                        <input type="date" class="form-control" name="start_date"
                            value="{{ request('start_date') }}">
                    </div>

                    <div class="col-lg-2 col-md-4">
                        <label class="form-label">End Date</label>
                        <input type="date" class="form-control" name="end_date" value="{{ request('end_date') }}">
                    </div>

                    <div class="col-lg-3 col-md-6">
                        <label class="form-label">Search</label>
                        <input type="text" class="form-control" name="search" value="{{ request('search') }}"
                            placeholder="Driver name, phone, plate...">
                    </div>

                    <div class="col-lg-1 col-md-2 d-flex align-items-end">
                        <button type="submit" class="btn btn-primary w-100">
                            <i class="ri-filter-line"></i>
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Contracts Table -->
    <div class="card bg-white border-0 rounded-3 mb-4">
        <div class="card-body p-4">
            <h5 class="mb-4">Hire Purchase Contracts</h5>

            <div class="table-responsive">
                <table class="table align-middle" id="contractsTable">
                    <thead>
                        <tr>
                            <th>Contract #</th>
                            <th>Driver</th>
                            <th>Vehicle</th>
                            <th>Total Amount</th>
                            <th>Paid</th>
                            <th>Balance</th>
                            <th>Progress</th>
                            <th>Status</th>
                            <th>Next Due</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($contracts as $contract)
                            <tr>
                                <td>
                                    <a href="{{ route('admin.hire-purchase.show', $contract) }}"
                                        class="fw-semibold text-primary">
                                        {{ $contract->contract_number }}
                                    </a>
                                </td>
                                <td>
                                    <div>
                                        <strong>{{ $contract->driver->full_name }}</strong><br>
                                        <small class="text-muted">{{ $contract->driver->phone_number }}</small>
                                    </div>
                                </td>
                                <td>
                                    <div>
                                        <strong>{{ $contract->vehicle->plate_number }}</strong><br>
                                        <small class="text-muted">{{ $contract->vehicle->make }}
                                            {{ $contract->vehicle->model }}</small>
                                    </div>
                                </td>
                                <td class="fw-semibold">₦{{ number_format($contract->total_amount) }}</td>
                                <td class="text-success fw-semibold">₦{{ number_format($contract->total_paid) }}</td>
                                <td class="text-danger fw-semibold">₦{{ number_format($contract->total_balance) }}</td>
                                <td style="min-width: 120px;">
                                    <div class="d-flex align-items-center gap-2">
                                        <div class="progress progress-thin flex-grow-1">
                                            <div class="progress-bar bg-success" role="progressbar"
                                                style="width: {{ $contract->progress_percentage }}%"></div>
                                        </div>
                                        <small class="text-muted">{{ $contract->progress_percentage }}%</small>
                                    </div>
                                    <small
                                        class="text-muted">{{ $contract->payments_made }}/{{ $contract->total_payment_days }}
                                        days</small>
                                </td>
                                <td>
                                    @switch($contract->status)
                                        @case('active')
                                            @if ($contract->is_overdue)
                                                <span class="badge bg-danger overdue-badge">Overdue</span>
                                            @else
                                                <span class="badge bg-success">Active</span>
                                            @endif
                                        @break

                                        @case('completed')
                                            <span class="badge bg-primary">Completed</span>
                                        @break

                                        @case('defaulted')
                                            <span class="badge bg-danger">Defaulted</span>
                                        @break

                                        @case('terminated')
                                            <span class="badge bg-secondary">Terminated</span>
                                        @break

                                        @case('suspended')
                                            <span class="badge bg-warning">Suspended</span>
                                        @break

                                        @default
                                            <span class="badge bg-secondary">{{ ucfirst($contract->status) }}</span>
                                    @endswitch
                                </td>
                                <td>
                                    @if ($contract->next_payment_due && $contract->status == 'active')
                                        @if ($contract->next_payment_due < today())
                                            <span class="text-danger fw-semibold">
                                                {{ $contract->next_payment_due->format('M d') }}
                                                <br><small>({{ $contract->next_payment_due->diffForHumans() }})</small>
                                            </span>
                                        @elseif($contract->next_payment_due->isToday())
                                            <span class="text-warning fw-semibold">Today</span>
                                        @else
                                            <span
                                                class="text-muted">{{ $contract->next_payment_due->format('M d') }}</span>
                                        @endif
                                    @else
                                        <span class="text-muted">-</span>
                                    @endif
                                </td>
                                <td>
                                    <div class="dropdown">
                                        <button class="btn btn-sm btn-primary dropdown-toggle" type="button" data-bs-toggle="dropdown">
                                            Actions
                                        </button>
                                        <ul class="dropdown-menu">
                                            <!-- View Actions -->
                                            <li><h6 class="dropdown-header"><i class="ri-eye-line me-1"></i>View</h6></li>
                                            <li>
                                                <a class="dropdown-item" href="{{ route('admin.hire-purchase.show', $contract) }}">
                                                    <i class="ri-eye-line me-2"></i>View Details
                                                </a>
                                            </li>
                                            <li>
                                                <a class="dropdown-item" href="{{ route('admin.hire-purchase.calendar', $contract) }}">
                                                    <i class="ri-calendar-line me-2"></i>Payment Calendar
                                                </a>
                                            </li>
                                            
                                            <!-- Edit Actions -->
                                            @canany(['edit hire purchase', 'approve hire purchase contract', 'reject hire purchase contract'])
                                                <li><hr class="dropdown-divider"></li>
                                                <li><h6 class="dropdown-header"><i class="ri-edit-line me-1"></i>Edit</h6></li>
                                                @can('edit hire purchase')
                                                    <li>
                                                        <a class="dropdown-item" href="{{ route('admin.hire-purchase.edit', $contract) }}">
                                                            <i class="ri-edit-line me-2"></i>Edit Contract
                                                        </a>
                                                    </li>
                                                @endcan
                                                @if ($contract->status == 'pending')
                                                    @can('approve hire purchase contract')
                                                        <li>
                                                            <a class="dropdown-item text-success" href="#" onclick="confirmAction('approve', {{ $contract->id }})">
                                                                <i class="ri-check-line me-2"></i>Approve Contract
                                                            </a>
                                                        </li>
                                                    @endcan
                                                    @can('reject hire purchase contract')
                                                        <li>
                                                            <a class="dropdown-item text-danger" href="#" onclick="confirmAction('reject', {{ $contract->id }})">
                                                                <i class="ri-close-line me-2"></i>Reject Contract
                                                            </a>
                                                        </li>
                                                    @endcan
                                                @endif
                                            @endcanany
                                            
                                            <!-- Payment Actions -->
                                            @if ($contract->status == 'active')
                                                <li><hr class="dropdown-divider"></li>
                                                <li><h6 class="dropdown-header"><i class="ri-money-dollar-circle-line me-1"></i>Payments</h6></li>
                                                @can('record hire purchase payment')
                                                    <li>
                                                        <button type="button" class="dropdown-item" data-bs-toggle="modal" data-bs-target="#paymentModal"
                                                            onclick="setPaymentModal({{ $contract->id }}, '{{ $contract->contract_number }}', {{ $contract->daily_payment }})">
                                                            <i class="ri-add-line me-2"></i>Record Payment
                                                        </button>
                                                    </li>
                                                @endcan
                                                @can('view hire purchase payments')
                                                    <li>
                                                        <a class="dropdown-item" href="{{ route('admin.hire-purchase.show', $contract) }}#payments">
                                                            <i class="ri-history-line me-2"></i>Payment History
                                                        </a>
                                                    </li>
                                                @endcan
                                                @can('view hire purchase schedule')
                                                    <li>
                                                        <a class="dropdown-item" href="{{ route('admin.hire-purchase.show', $contract) }}#schedule">
                                                            <i class="ri-calendar-check-line me-2"></i>Payment Schedule
                                                        </a>
                                                    </li>
                                                @endcan
                                            @endif
                                            
                                            <!-- Contract Actions -->
                                            @if ($contract->status == 'active')
                                                @canany(['terminate hire purchase contract', 'add hire purchase penalty', 'waive hire purchase penalty'])
                                                    <li><hr class="dropdown-divider"></li>
                                                    <li><h6 class="dropdown-header"><i class="ri-settings-3-line me-1"></i>Contract</h6></li>
                                                    @can('terminate hire purchase contract')
                                                        <li>
                                                            <a class="dropdown-item text-warning" href="#" onclick="confirmAction('terminate', {{ $contract->id }})">
                                                                <i class="ri-stop-circle-line me-2"></i>Terminate Contract
                                                            </a>
                                                        </li>
                                                    @endcan
                                                    @can('add hire purchase penalty')
                                                        <li>
                                                            <a class="dropdown-item text-info" href="#" onclick="openPenaltyModal({{ $contract->id }})">
                                                                <i class="ri-add-circle-line me-2"></i>Add Penalty
                                                            </a>
                                                        </li>
                                                    @endcan
                                                    @can('waive hire purchase penalty')
                                                        <li>
                                                            <a class="dropdown-item text-success" href="#" onclick="openWaiveModal({{ $contract->id }})">
                                                                <i class="ri-gift-line me-2"></i>Waive Penalty
                                                            </a>
                                                        </li>
                                                    @endcan
                                                @endcanany
                                            @endif
                                            
                                            <!-- Export Actions -->
                                            @canany(['export hire purchase report', 'print hire purchase contract'])
                                                <li><hr class="dropdown-divider"></li>
                                                <li><h6 class="dropdown-header"><i class="ri-download-line me-1"></i>Export</h6></li>
                                                @can('export hire purchase report')
                                                    <li>
                                                        <a class="dropdown-item" href="{{ route('admin.hire-purchase.export', ['contract_id' => $contract->id]) }}">
                                                            <i class="ri-file-excel-line me-2"></i>Export Report
                                                        </a>
                                                    </li>
                                                @endcan
                                                @can('print hire purchase contract')
                                                    <li>
                                                        <a class="dropdown-item" href="#" onclick="window.print()">
                                                            <i class="ri-printer-line me-2"></i>Print Contract
                                                        </a>
                                                    </li>
                                                @endcan
                                            @endcanany
                                            
                                            <!-- Delete Action -->
                                            @can('delete hire purchase')
                                                @if ($contract->status != 'active')
                                                    <li><hr class="dropdown-divider"></li>
                                                    <li>
                                                        <a class="dropdown-item text-danger" href="#" onclick="confirmDelete({{ $contract->id }})">
                                                            <i class="ri-delete-bin-line me-2"></i>Delete Contract
                                                        </a>
                                                    </li>
                                                @endif
                                            @endcan
                                        </ul>
                                    </div>
                                </td>
                            </tr>
                            @empty
                                <tr>
                                    <td colspan="10" class="text-center py-4">
                                        <div class="text-muted">
                                            <i class="ri-car-line fs-48 d-block mb-2"></i>
                                            <p>No hire purchase contracts found</p>
                                            <a href="{{ route('admin.hire-purchase.create') }}"
                                                class="btn btn-primary btn-sm">
                                                Create First Contract
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="mt-3">
                    {{ $contracts->links() }}
                </div>
            </div>
        </div>

        <!-- Payments Due Today -->
        @if ($paymentsDueToday->count() > 0)
            <div class="card bg-white border-0 rounded-3 mb-4">
                <div class="card-body p-4">
                    <h5 class="mb-4">
                        <i class="ri-calendar-todo-line me-2 text-warning"></i>
                        Payments Due Today ({{ $paymentsDueToday->count() }})
                    </h5>
                    <div class="table-responsive">
                        <table class="table align-middle">
                            <thead>
                                <tr>
                                    <th>Driver</th>
                                    <th>Vehicle</th>
                                    <th>Amount Due</th>
                                    <th>Contract</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($paymentsDueToday as $payment)
                                    <tr>
                                        <td>{{ $payment->contract->driver->full_name }}</td>
                                        <td>{{ $payment->contract->vehicle->plate_number }}</td>
                                        <td class="fw-semibold">₦{{ number_format($payment->expected_amount) }}</td>
                                        <td>{{ $payment->contract->contract_number }}</td>
                                        <td>
                                            <a href="{{ route('admin.hire-purchase.show', $payment->contract) }}"
                                                class="btn btn-sm btn-primary">View</a>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        @endif

        <!-- Overdue Payments -->
        @if ($overduePayments->count() > 0)
            <div class="card bg-white border-0 rounded-3 mb-4">
                <div class="card-body p-4">
                    <h5 class="mb-4">
                        <i class="ri-error-warning-line me-2 text-danger"></i>
                        Overdue Payments ({{ $overduePayments->count() }})
                    </h5>
                    <div class="table-responsive">
                        <table class="table align-middle">
                            <thead>
                                <tr>
                                    <th>Driver</th>
                                    <th>Vehicle</th>
                                    <th>Amount Due</th>
                                    <th>Due Date</th>
                                    <th>Days Overdue</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($overduePayments as $payment)
                                    <tr class="table-danger">
                                        <td>{{ $payment->contract->driver->full_name }}</td>
                                        <td>{{ $payment->contract->vehicle->plate_number }}</td>
                                        <td class="fw-semibold">₦{{ number_format($payment->expected_amount) }}</td>
                                        <td>{{ $payment->due_date->format('M d, Y') }}</td>
                                        <td>
                                            <span class="badge bg-danger">{{ $payment->due_date->diffInDays(now()) }}
                                                days</span>
                                        </td>
                                        <td>
                                            <a href="{{ route('admin.hire-purchase.show', $payment->contract) }}"
                                                class="btn btn-sm btn-danger">Collect</a>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        @endif

        <!-- Quick Payment Modal -->
        <div class="modal fade" id="paymentModal" tabindex="-1">
            <div class="modal-dialog">
                <div class="modal-content">
                    <form id="paymentForm" method="POST" action="">
                        @csrf
                        <div class="modal-header">
                            <h5 class="modal-title">
                                <i class="ri-money-dollar-circle-line me-2"></i>Record Payment
                            </h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">
                            <div class="alert alert-info">
                                <strong>Contract:</strong> <span id="modalContractNumber"></span>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Amount (₦) <span class="text-danger">*</span></label>
                                <input type="number" class="form-control" name="amount" id="paymentAmount" step="0.01"
                                    min="1" required>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Payment Date <span class="text-danger">*</span></label>
                                <input type="date" class="form-control" name="payment_date" value="{{ date('Y-m-d') }}"
                                    required>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Payment Method <span class="text-danger">*</span></label>
                                <select class="form-select" name="payment_method" required>
                                    <option value="cash">Cash</option>
                                    <option value="transfer">Bank Transfer</option>
                                    <option value="pos">POS</option>
                                    <option value="mobile">Mobile Money</option>
                                </select>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Notes</label>
                                <textarea class="form-control" name="notes" rows="2"></textarea>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" class="btn btn-success">
                                <i class="ri-check-line me-1"></i>Record Payment
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endsection

    @push('scripts')
        <script>
            function setPaymentModal(contractId, contractNumber, dailyPayment) {
                document.getElementById('paymentForm').action = `/admin/hire-purchase/${contractId}/payment`;
                document.getElementById('modalContractNumber').textContent = contractNumber;
                document.getElementById('paymentAmount').value = dailyPayment;
            }
            
            function confirmAction(action, contractId) {
                let message = '';
                let url = '';
                
                switch(action) {
                    case 'approve':
                        message = 'Are you sure you want to approve this hire purchase contract?';
                        url = `/admin/hire-purchase/${contractId}/approve`;
                        break;
                    case 'reject':
                        message = 'Are you sure you want to reject this hire purchase contract?';
                        url = `/admin/hire-purchase/${contractId}/reject`;
                        break;
                    case 'terminate':
                        message = 'Are you sure you want to terminate this contract? This action cannot be undone.';
                        url = `/admin/hire-purchase/${contractId}/terminate`;
                        break;
                }
                
                if (confirm(message)) {
                    const form = document.createElement('form');
                    form.method = 'POST';
                    form.action = url;
                    
                    const csrfToken = document.createElement('input');
                    csrfToken.type = 'hidden';
                    csrfToken.name = '_token';
                    csrfToken.value = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
                    form.appendChild(csrfToken);
                    
                    const methodField = document.createElement('input');
                    methodField.type = 'hidden';
                    methodField.name = '_method';
                    methodField.value = 'PUT';
                    form.appendChild(methodField);
                    
                    document.body.appendChild(form);
                    form.submit();
                }
            }
            
            function confirmDelete(contractId) {
                if (confirm('Are you sure you want to delete this hire purchase contract? This action cannot be undone.')) {
                    const form = document.createElement('form');
                    form.method = 'POST';
                    form.action = `/admin/hire-purchase/${contractId}`;
                    
                    const csrfToken = document.createElement('input');
                    csrfToken.type = 'hidden';
                    csrfToken.name = '_token';
                    csrfToken.value = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
                    form.appendChild(csrfToken);
                    
                    const methodField = document.createElement('input');
                    methodField.type = 'hidden';
                    methodField.name = '_method';
                    methodField.value = 'DELETE';
                    form.appendChild(methodField);
                    
                    document.body.appendChild(form);
                    form.submit();
                }
            }
            
            function openPenaltyModal(contractId) {
                // Implementation for penalty modal
                alert('Penalty modal would open here for contract: ' + contractId);
            }
            
            function openWaiveModal(contractId) {
                // Implementation for waiver modal
                alert('Waive penalty modal would open here for contract: ' + contractId);
            }
        </script>
    @endpush
