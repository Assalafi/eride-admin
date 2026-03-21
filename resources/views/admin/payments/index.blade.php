@extends('layouts.app')

@section('title', 'Payments Management')

@push('styles')
    <link rel="stylesheet" href="{{ asset('assets/css/data-table.css') }}">
@endpush

@section('content')
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-4">
        <h3 class="mb-0">Payments Management</h3>

        <div class="d-flex gap-2">
            <a href="{{ route('admin.payments.pdf', request()->query()) }}" class="btn btn-primary"
                title="Generate PDF Report">
                <i class="ri-file-pdf-line me-1"></i>Export PDF
            </a>
        </div>

        <nav style="--bs-breadcrumb-divider: '>';" aria-label="breadcrumb">
            <ol class="breadcrumb align-items-center mb-0 lh-1">
                <li class="breadcrumb-item">
                    <a href="{{ route('dashboard') }}" class="d-flex align-items-center text-decoration-none">
                        <i class="ri-home-4-line fs-18 text-primary me-1"></i>
                        <span class="text-secondary fw-medium hover">Dashboard</span>
                    </a>
                </li>
                <li class="breadcrumb-item active" aria-current="page">
                    <span class="fw-medium">Payments</span>
                </li>
            </ol>
        </nav>
    </div>

    <!-- Filters -->
    <div class="card bg-white border-0 rounded-3 mb-4">
        <div class="card-body p-4">
            <form method="GET" action="{{ route('admin.payments.index') }}" id="filterForm">
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

                    <div class="col-lg-2 col-md-4" id="startDateDiv"
                        style="display: {{ $timeFilter == 'custom' ? 'block' : 'none' }};">
                        <label class="form-label">Start Date</label>
                        <input type="date" class="form-control" name="start_date" value="{{ $startDate }}">
                    </div>

                    <div class="col-lg-2 col-md-4" id="endDateDiv"
                        style="display: {{ $timeFilter == 'custom' ? 'block' : 'none' }};">
                        <label class="form-label">End Date</label>
                        <input type="date" class="form-control" name="end_date" value="{{ $endDate }}">
                    </div>

                    <div class="col-lg-2 col-md-4">
                        <label class="form-label">Driver</label>
                        <select class="form-select" name="driver_id">
                            <option value="">All Drivers</option>
                            @foreach ($drivers as $driver)
                                <option value="{{ $driver->id }}" {{ $driverId == $driver->id ? 'selected' : '' }}>
                                    {{ $driver->full_name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    @if (auth()->user()->hasRole('Super Admin'))
                        <div class="col-lg-2 col-md-4">
                            <label class="form-label">Branch</label>
                            <select class="form-select" name="branch_id">
                                <option value="">All Branches</option>
                                @foreach ($branches as $branch)
                                    <option value="{{ $branch->id }}" {{ $branchId == $branch->id ? 'selected' : '' }}>
                                        {{ $branch->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    @endif

                    <div class="col-lg-2 col-md-4">
                        <label class="form-label">Status</label>
                        <select class="form-select" name="status">
                            <option value="">All Status</option>
                            <option value="pending" {{ $status == 'pending' ? 'selected' : '' }}>Pending</option>
                            <option value="successful" {{ $status == 'successful' ? 'selected' : '' }}>Successful</option>
                            <option value="rejected" {{ $status == 'rejected' ? 'selected' : '' }}>Rejected</option>
                        </select>
                    </div>

                    <div class="col-lg-2 col-md-4">
                        <label class="form-label">Type</label>
                        <select class="form-select" name="type">
                            <option value="">All Types</option>
                            <option value="daily_remittance" {{ $type == 'daily_remittance' ? 'selected' : '' }}>Daily
                                Remittance</option>
                            <option value="charging_payment" {{ $type == 'charging_payment' ? 'selected' : '' }}>Charging
                                Payment</option>
                            <option value="maintenance_debit" {{ $type == 'maintenance_debit' ? 'selected' : '' }}>
                                Maintenance Debit</option>
                            <option value="wallet_funding" {{ $type == 'wallet_funding' ? 'selected' : '' }}>Wallet Funding
                            </option>
                        </select>
                    </div>

                    <div class="col-lg-2 col-md-4">
                        <label class="form-label">Charging Status</label>
                        <select class="form-select" name="charging_status">
                            <option value="">All Charging</option>
                            <option value="approved" {{ $chargingStatus == 'approved' ? 'selected' : '' }}>Approved
                            </option>
                            <option value="completed" {{ $chargingStatus == 'completed' ? 'selected' : '' }}>Completed
                            </option>
                        </select>
                    </div>

                    <div class="col-lg-12 d-flex align-items-end">
                        <button type="submit" class="btn btn-primary me-2">
                            <i class="ri-filter-line me-1"></i> Apply Filters
                        </button>
                        <a href="{{ route('admin.payments.index') }}" class="btn btn-outline-secondary">
                            <i class="ri-refresh-line"></i> Reset
                        </a>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Income Summary -->
    <div class="row g-3 mb-4">
        <div class="col-lg-3 col-md-6">
            <div class="card bg-white border-0 rounded-3 h-100">
                <div class="card-body p-4">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <p class="text-secondary mb-1 fw-medium">Daily Remittance</p>
                            <h4 class="mb-0 text-success">₦{{ number_format($incomeSummary['daily_remittance'], 2) }}
                            </h4>
                        </div>
                        <div class="bg-success bg-opacity-10 rounded-3 p-3">
                            <i class="ri-money-dollar-circle-line text-success fs-24"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-3 col-md-6">
            <div class="card bg-white border-0 rounded-3 h-100">
                <div class="card-body p-4">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <p class="text-secondary mb-1 fw-medium">Charging</p>
                            <h4 class="mb-0 text-primary">₦{{ number_format($incomeSummary['charging'], 2) }}</h4>
                            <small class="text-muted">({{ number_format($incomeSummary['charging'] / 203, 2) }}
                                ¥)</small>
                        </div>
                        <div class="bg-primary bg-opacity-10 rounded-3 p-3">
                            <i class="ri-ev-station-line text-primary fs-24"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-3 col-md-6">
            <div class="card bg-white border-0 rounded-3 h-100">
                <div class="card-body p-4">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <p class="text-secondary mb-1 fw-medium">Maintenance</p>
                            <h4 class="mb-0 text-warning">₦{{ number_format($incomeSummary['maintenance'], 2) }}</h4>
                        </div>
                        <div class="bg-warning bg-opacity-10 rounded-3 p-3">
                            <i class="ri-tools-line text-warning fs-24"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-3 col-md-6">
            <div class="card bg-white border-0 rounded-3 h-100">
                <div class="card-body p-4">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <p class="text-secondary mb-1 fw-medium">Total Income</p>
                            <h4 class="mb-0 text-info">₦{{ number_format($incomeSummary['total'], 2) }}</h4>
                        </div>
                        <div class="bg-info bg-opacity-10 rounded-3 p-3">
                            <i class="ri-wallet-3-line text-info fs-24"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="card bg-white border-0 rounded-3 mb-4">
        <div class="card-body p-4">
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-4">
                <h4 class="mb-0">All Transactions</h4>

                @can('approve payments')
                    <div>
                        <button type="button" class="btn btn-success" data-bs-toggle="modal"
                            data-bs-target="#generateRemittancesModal">
                            <i class="ri-currency-line me-1"></i> Generate Daily Remittances
                        </button>
                    </div>
                @endcan
            </div>

            <div class="default-table-area">
                <div class="table-responsive">
                    <table class="table align-middle" id="paymentsTable">
                        <thead>
                            <tr>
                                <th scope="col">#</th>
                                <th scope="col">Driver</th>
                                <th scope="col">Type</th>
                                <th scope="col">Amount</th>
                                <th scope="col">Status</th>
                                <th scope="col">Payment Proof</th>
                                <th scope="col">Date</th>
                                <th scope="col">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($transactions as $transaction)
                                <tr data-status="{{ $transaction->status }}">
                                    <td>{{ (($transactions?->currentPage() ?? 1) - 1) * ($transactions?->perPage() ?? 20) + $loop->iteration }}
                                    </td>
                                    <td>
                                        <div>
                                            <strong>{{ $transaction->driver->full_name ?? 'Unknown Driver' }}</strong>
                                            @if ($transaction->is_hire_purchase_payment ?? false)
                                                <span class="badge bg-info ms-1" title="Hire Purchase Driver">
                                                    <i class="ri-car-line"></i> HP
                                                </span>
                                            @endif
                                            <br>
                                            <small
                                                class="text-muted">{{ $transaction->driver->phone_number ?? 'N/A' }}</small>
                                        </div>
                                    </td>
                                    <td>
                                        <span
                                            class="badge {{ $transaction->is_hire_purchase_payment ?? false ? 'bg-info' : 'bg-secondary' }}">
                                            @if (($transaction->type ?? '') === 'charging_payment' || substr($transaction->reference ?? '', 0, 6) === 'CHARGE')
                                                Charging Payment
                                            @elseif ($transaction->is_hire_purchase_payment ?? false)
                                                Hire Purchase
                                            @else
                                                {{ str_replace('_', ' ', ucwords($transaction->type ?? 'N/A')) }}
                                            @endif
                                        </span>
                                    </td>
                                    <td class="fw-semibold">₦{{ number_format($transaction->amount ?? 0, 2) }}</td>
                                    <td>
                                        @if ($transaction->status == 'successful')
                                            <span class="badge bg-success">Success</span>
                                        @elseif($transaction->status == 'pending')
                                            <span class="badge bg-warning">Pending</span>
                                        @else
                                            <div>
                                                <span class="badge bg-danger">Rejected</span>
                                                @if ($transaction->description ?? null)
                                                    <button type="button" class="btn btn-sm btn-outline-secondary ms-1"
                                                        data-bs-toggle="tooltip" data-bs-placement="top"
                                                        title="{{ $transaction->description }}">
                                                        <i class="ri-message-3-line"></i>
                                                    </button>
                                                @endif
                                            </div>
                                        @endif
                                    </td>
                                    <td>
                                        @if ($transaction->payment_proof ?? null)
                                            <a href="{{ asset('storage/' . $transaction->payment_proof) }}"
                                                target="_blank" class="btn btn-sm btn-info" title="View Receipt">
                                                <i class="ri-image-line"></i> View
                                            </a>
                                        @else
                                            <span class="badge bg-secondary">Not Uploaded</span>
                                        @endif
                                    </td>
                                    <td>{{ $transaction->created_at?->format('M d, Y H:i') ?? 'N/A' }}</td>
                                    <td>
                                        @if ($transaction->status == 'pending' && ($transaction->type ?? '') === 'daily_remittance')
                                            @can('approve payments')
                                                <div class="d-flex gap-1">
                                                    <button type="button" class="btn btn-sm btn-success" title="Approve"
                                                        onclick="openApprovalModal('{{ route('admin.payments.approve', $transaction) }}', '{{ $transaction->driver->full_name ?? 'Unknown Driver' }}', '{{ $transaction->is_hire_purchase_payment ?? false ? 'Hire Purchase' : $transaction->type ?? 'N/A' }}', {{ $transaction->amount ?? 0 }})">
                                                        <i class="ri-check-line"></i>
                                                    </button>
                                                    <button type="button" class="btn btn-sm btn-danger" title="Reject"
                                                        onclick="openRejectionModal('{{ route('admin.payments.reject', $transaction) }}', '{{ $transaction->driver->full_name ?? 'Unknown Driver' }}', '₦{{ number_format($transaction->amount ?? 0, 2) }}')">
                                                        <i class="ri-close-line"></i>
                                                    </button>
                                                    <button type="button" class="btn btn-sm btn-warning"
                                                        title="Skip Payment"
                                                        onclick="openSkipModal('{{ route('admin.payments.skip', $transaction) }}', '{{ $transaction->driver->full_name ?? 'Unknown Driver' }}', '₦{{ number_format($transaction->amount ?? 0, 2) }}')">
                                                        <i class="ri-calendar-close-line"></i>
                                                    </button>
                                                </div>
                                            @endcan
                                        @elseif($transaction->status == 'rejected' && ($transaction->type ?? '') === 'daily_remittance')
                                            @can('approve payments')
                                                <div class="d-flex gap-1">
                                                    <form action="{{ route('admin.payments.restore', $transaction) }}"
                                                        method="POST" class="d-inline">
                                                        @csrf
                                                        <button type="submit" class="btn btn-sm btn-warning" title="Restore"
                                                            onclick="return confirm('Restore this payment? This will create a new identical transaction with pending status.')">
                                                            <i class="ri-refresh-line"></i>
                                                        </button>
                                                    </form>
                                                </div>
                                            @endcan
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="text-center py-4">
                                        <div class="text-muted">
                                            <span class="material-symbols-outlined fs-48">payments</span>
                                            <p class="mt-2">No transactions found</p>
                                        </div>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="mt-3">
                    {{ $transactions?->links() ?? '' }}
                </div>
            </div>
        </div>
    </div>

    <!-- Generate Daily Remittances Modal -->
    <div class="modal fade" id="generateRemittancesModal" tabindex="-1" aria-labelledby="generateRemittancesModalLabel"
        aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="generateRemittancesModalLabel">
                        <i class="ri-currency-line me-2"></i>Generate Daily Remittances
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form action="{{ route('admin.payments.generate-daily-remittances') }}" method="POST"
                    id="generateRemittanceForm">
                    @csrf
                    <div class="modal-body">
                        <div class="alert alert-info">
                            <i class="ri-information-line me-2"></i>
                            <strong>Action:</strong> This will create daily remittance transactions for all active drivers
                            with
                            assigned vehicles who don't have one for the selected date.
                        </div>

                        @if (auth()->user()->hasRole('Super Admin'))
                            <div class="row">
                                <div class="col-md-6">
                                    <!-- Date Selection -->
                                    <div class="mb-3">
                                        <label for="remittanceDate" class="form-label">
                                            <i class="ri-calendar-line me-1"></i>Remittance Date
                                        </label>
                                        <input type="date" name="date" id="remittanceDate" class="form-control"
                                            value="{{ date('Y-m-d') }}" max="{{ date('Y-m-d') }}" required>
                                        <div class="form-text">Select the date for which to generate remittances</div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <!-- Branch Selection -->
                                    <div class="mb-3">
                                        <label for="branchId" class="form-label">
                                            <i class="ri-building-line me-1"></i>Branch
                                        </label>
                                        <select name="branch_id" id="branchId" class="form-select">
                                            <option value="">All Branches</option>
                                            @if (isset($branches))
                                                @foreach ($branches as $branch)
                                                    <option value="{{ $branch->id }}">{{ $branch->name }}</option>
                                                @endforeach
                                            @endif
                                        </select>
                                        <div class="form-text">Filter by branch</div>
                                    </div>
                                </div>
                            </div>

                            <!-- Skip Charging Check Option -->
                            <div class="mb-3">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="skipChargingCheck"
                                        name="skip_charging_check" value="1">
                                    <label class="form-check-label" for="skipChargingCheck">
                                        <strong>Skip charging activity check</strong>
                                        <br><small class="text-muted">Select specific drivers without charging activity to
                                            include them anyway</small>
                                    </label>
                                </div>
                            </div>

                            <!-- Driver Selection Panel (hidden by default) -->
                            <div id="driverSelectionPanel" class="mb-3" style="display: none;">
                                <div class="card border">
                                    <div
                                        class="card-header bg-light d-flex justify-content-between align-items-center py-2">
                                        <span><i class="ri-user-line me-1"></i>Drivers without remittance for selected
                                            date</span>
                                        <button type="button" class="btn btn-sm btn-outline-primary"
                                            id="loadDriversBtn">
                                            <i class="ri-refresh-line me-1"></i>Load Drivers
                                        </button>
                                    </div>
                                    <div class="card-body p-0">
                                        <div id="driverListLoading" class="text-center py-4" style="display: none;">
                                            <div class="spinner-border spinner-border-sm text-primary" role="status">
                                            </div>
                                            <span class="ms-2">Loading drivers...</span>
                                        </div>
                                        <div id="driverListEmpty" class="text-center py-4 text-muted"
                                            style="display: none;">
                                            <i class="ri-user-unfollow-line fs-32"></i>
                                            <p class="mb-0">No drivers found without remittance for this date</p>
                                        </div>
                                        <div id="driverListContainer" style="max-height: 300px; overflow-y: auto;">
                                            <table class="table table-sm table-hover mb-0">
                                                <thead class="table-light sticky-top">
                                                    <tr>
                                                        <th style="width: 40px;">
                                                            <input type="checkbox" class="form-check-input"
                                                                id="selectAllDrivers">
                                                        </th>
                                                        <th>Driver</th>
                                                        <th>Branch</th>
                                                        <th>Type</th>
                                                        <th>Charging</th>
                                                    </tr>
                                                </thead>
                                                <tbody id="driverListBody">
                                                    <tr>
                                                        <td colspan="5" class="text-center text-muted py-3">
                                                            Click "Load Drivers" to fetch the list
                                                        </td>
                                                    </tr>
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                    <div class="card-footer bg-light py-2">
                                        <small class="text-muted"><span id="selectedDriverCount">0</span> driver(s)
                                            selected</small>
                                    </div>
                                </div>
                            </div>
                        @else
                            <input type="hidden" name="date" value="{{ date('Y-m-d') }}">
                            <input type="hidden" name="branch_id" value="{{ auth()->user()->branch_id }}">
                        @endif

                        <div class="bg-light rounded p-3">
                            <p class="mb-1"><strong>Amount:</strong>
                                ₦{{ number_format(setting('daily_remittance_amount', 5000), 2) }} per driver (Hire Purchase
                                drivers use their contract amount)</p>
                            <p class="mb-1"><strong>Status:</strong> All transactions will be created with <span
                                    class="badge bg-warning">Pending</span> status</p>
                            <p class="mb-0"><strong>Note:</strong> Drivers who already have a remittance for the selected
                                date will be skipped automatically.</p>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                            <i class="ri-close-line me-1"></i>Cancel
                        </button>
                        <button type="submit" class="btn btn-success">
                            <i class="ri-check-line me-1"></i>Generate Remittances
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const skipChargingCheck = document.getElementById('skipChargingCheck');
            const driverSelectionPanel = document.getElementById('driverSelectionPanel');
            const loadDriversBtn = document.getElementById('loadDriversBtn');
            const driverListBody = document.getElementById('driverListBody');
            const driverListLoading = document.getElementById('driverListLoading');
            const driverListEmpty = document.getElementById('driverListEmpty');
            const driverListContainer = document.getElementById('driverListContainer');
            const selectAllDrivers = document.getElementById('selectAllDrivers');
            const selectedDriverCount = document.getElementById('selectedDriverCount');
            const dateInput = document.getElementById('remittanceDate');
            const branchSelect = document.getElementById('branchId');

            // Toggle driver selection panel
            if (skipChargingCheck) {
                skipChargingCheck.addEventListener('change', function() {
                    driverSelectionPanel.style.display = this.checked ? 'block' : 'none';
                    if (this.checked) {
                        loadDrivers();
                    }
                });
            }

            // Load drivers button
            if (loadDriversBtn) {
                loadDriversBtn.addEventListener('click', loadDrivers);
            }

            // Reload when date or branch changes
            if (dateInput) {
                dateInput.addEventListener('change', function() {
                    if (skipChargingCheck && skipChargingCheck.checked) {
                        loadDrivers();
                    }
                });
            }
            if (branchSelect) {
                branchSelect.addEventListener('change', function() {
                    if (skipChargingCheck && skipChargingCheck.checked) {
                        loadDrivers();
                    }
                });
            }

            // Select all checkbox
            if (selectAllDrivers) {
                selectAllDrivers.addEventListener('change', function() {
                    const checkboxes = driverListBody.querySelectorAll('input[name="selected_drivers[]"]');
                    checkboxes.forEach(cb => cb.checked = this.checked);
                    updateSelectedCount();
                });
            }

            function loadDrivers() {
                const date = dateInput ? dateInput.value : '{{ date('Y-m-d') }}';
                const branchId = branchSelect ? branchSelect.value : '';

                driverListLoading.style.display = 'block';
                driverListContainer.style.display = 'none';
                driverListEmpty.style.display = 'none';

                fetch(`{{ route('admin.payments.drivers-without-remittance') }}?date=${date}&branch_id=${branchId}`, {
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest',
                            'Accept': 'application/json'
                        }
                    })
                    .then(response => response.json())
                    .then(data => {
                        driverListLoading.style.display = 'none';

                        if (data.drivers && data.drivers.length > 0) {
                            driverListContainer.style.display = 'block';
                            driverListEmpty.style.display = 'none';

                            let html = '';
                            data.drivers.forEach(driver => {
                                const typeClass = driver.is_hire_purchase ? 'bg-info' : 'bg-secondary';
                                const typeLabel = driver.is_hire_purchase ? 'Hire Purchase' : 'Regular';
                                const chargingClass = driver.has_charging ? 'bg-success' : 'bg-danger';
                                const chargingLabel = driver.has_charging ? 'Yes' : 'No';

                                html += `
                                <tr>
                                    <td>
                                        <input type="checkbox" class="form-check-input driver-checkbox" 
                                            name="selected_drivers[]" value="${driver.id}" 
                                            ${!driver.has_charging ? 'checked' : ''}>
                                    </td>
                                    <td>
                                        <strong>${driver.name}</strong>
                                        <br><small class="text-muted">${driver.phone}</small>
                                    </td>
                                    <td><small>${driver.branch}</small></td>
                                    <td><span class="badge ${typeClass}">${typeLabel}</span></td>
                                    <td><span class="badge ${chargingClass}">${chargingLabel}</span></td>
                                </tr>
                            `;
                            });
                            driverListBody.innerHTML = html;

                            // Add event listeners to checkboxes
                            driverListBody.querySelectorAll('.driver-checkbox').forEach(cb => {
                                cb.addEventListener('change', updateSelectedCount);
                            });

                            updateSelectedCount();
                        } else {
                            driverListContainer.style.display = 'none';
                            driverListEmpty.style.display = 'block';
                        }
                    })
                    .catch(error => {
                        console.error('Error loading drivers:', error);
                        driverListLoading.style.display = 'none';
                        driverListBody.innerHTML =
                            '<tr><td colspan="5" class="text-center text-danger">Error loading drivers</td></tr>';
                        driverListContainer.style.display = 'block';
                    });
            }

            function updateSelectedCount() {
                const checked = driverListBody.querySelectorAll('input[name="selected_drivers[]"]:checked').length;
                selectedDriverCount.textContent = checked;
            }
        });
    </script>

    <!-- Rejection Comment Modal -->
    <div class="modal fade" id="rejectionModal" tabindex="-1" aria-labelledby="rejectionModalLabel"
        aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="rejectionModalLabel">
                        <i class="ri-close-circle-line text-danger me-2"></i>Reject Payment
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form id="rejectionForm" method="POST" action="">
                    @csrf
                    <div class="modal-body">
                        <div class="alert alert-warning d-flex align-items-center" role="alert">
                            <i class="ri-alert-line me-2"></i>
                            <div>
                                <strong>Warning:</strong> You are about to reject this payment. Please provide a reason for
                                the rejection.
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="rejection_comment" class="form-label">
                                <strong>Rejection Reason <span class="text-danger">*</span></strong>
                            </label>
                            <textarea class="form-control" id="rejection_comment" name="rejection_comment" rows="4" maxlength="500"
                                required placeholder="Please explain why this payment is being rejected..."></textarea>
                            <div class="form-text">
                                Maximum 500 characters. This reason will be visible to the driver.
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Transaction Details:</label>
                            <div class="card bg-light">
                                <div class="card-body p-3">
                                    <div class="row">
                                        <div class="col-6">
                                            <small class="text-muted">Driver:</small><br>
                                            <strong id="rejection_driver_name">-</strong>
                                        </div>
                                        <div class="col-6">
                                            <small class="text-muted">Amount:</small><br>
                                            <strong id="rejection_amount">-</strong>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                            <i class="ri-close-line me-1"></i>Cancel
                        </button>
                        <button type="submit" class="btn btn-danger">
                            <i class="ri-close-circle-line me-1"></i>Reject Payment
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Approval Modal -->
    <div class="modal fade" id="approvalModal" tabindex="-1" aria-labelledby="approvalModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="approvalModalLabel">
                        <i class="ri-check-line text-success me-2"></i>Approve Payment
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form id="approvalForm" method="POST" action="">
                    @csrf
                    <div class="modal-body">
                        <div class="alert alert-success d-flex align-items-center" role="alert">
                            <i class="ri-check-line me-2"></i>
                            <div>
                                <strong>Confirm Approval:</strong> You are about to approve this payment.
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Transaction Details:</label>
                            <div class="card bg-light">
                                <div class="card-body p-3">
                                    <div class="row">
                                        <div class="col-6">
                                            <small class="text-muted">Driver:</small><br>
                                            <strong id="approval_driver_name">-</strong>
                                        </div>
                                        <div class="col-6">
                                            <small class="text-muted">Type:</small><br>
                                            <strong id="approval_type">-</strong>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="approval_amount" class="form-label">
                                <strong>Amount <span class="text-danger">*</span></strong>
                                @if (auth()->user()->hasRole('Super Admin'))
                                    <small class="text-muted">(Super Admin can edit)</small>
                                @endif
                            </label>
                            <div class="input-group">
                                <span class="input-group-text">₦</span>
                                <input type="number" id="approval_amount" name="amount" class="form-control"
                                    step="0.01" min="0.01" required
                                    @if (!auth()->user()->hasRole('Super Admin')) readonly @endif>
                            </div>
                            @if (auth()->user()->hasRole('Super Admin'))
                                <div class="form-text">
                                    As Super Admin, you can modify the amount before approval.
                                </div>
                            @else
                                <div class="form-text">
                                    Only Super Admin can modify payment amounts.
                                </div>
                            @endif
                        </div>

                        @if (auth()->user()->hasRole('Super Admin'))
                            <div class="mb-3">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="enableAmountEdit">
                                    <label class="form-check-label" for="enableAmountEdit">
                                        <strong>Enable Amount Editing</strong>
                                    </label>
                                </div>
                            </div>
                        @endif
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                            <i class="ri-close-line me-1"></i>Cancel
                        </button>
                        <button type="submit" class="btn btn-success">
                            <i class="ri-check-line me-1"></i>Approve Payment
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Skip Payment Modal -->
    <div class="modal fade" id="skipModal" tabindex="-1" aria-labelledby="skipModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="skipModalLabel">
                        <i class="ri-calendar-close-line text-warning me-2"></i>Skip Payment
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form id="skipForm" method="POST" action="">
                    @csrf
                    <div class="modal-body">
                        <div class="alert alert-warning d-flex align-items-center" role="alert">
                            <i class="ri-alert-line me-2"></i>
                            <div>
                                <strong>Skip Payment:</strong> This will mark the payment as skipped. The driver will not be
                                penalized for this day.
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Transaction Details:</label>
                            <div class="card bg-light">
                                <div class="card-body p-3">
                                    <div class="row">
                                        <div class="col-6">
                                            <small class="text-muted">Driver:</small><br>
                                            <strong id="skip_driver_name">-</strong>
                                        </div>
                                        <div class="col-6">
                                            <small class="text-muted">Amount:</small><br>
                                            <strong id="skip_amount">-</strong>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="skip_reason" class="form-label">
                                <strong>Reason for Skipping <span class="text-danger">*</span></strong>
                            </label>
                            <select class="form-select" id="skip_reason" name="skip_reason" required>
                                <option value="">Select a reason...</option>
                                <option value="sick">Driver Sick</option>
                                <option value="vehicle_maintenance">Vehicle Under Maintenance</option>
                                <option value="personal_emergency">Personal Emergency</option>
                                <option value="public_holiday">Public Holiday</option>
                                <option value="other">Other Reason</option>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label for="skip_notes" class="form-label">
                                <strong>Additional Notes</strong>
                            </label>
                            <textarea class="form-control" id="skip_notes" name="skip_notes" rows="3" maxlength="500"
                                placeholder="Enter any additional details..."></textarea>
                            <div class="form-text">
                                Optional. Maximum 500 characters.
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                            <i class="ri-close-line me-1"></i>Cancel
                        </button>
                        <button type="submit" class="btn btn-warning">
                            <i class="ri-calendar-close-line me-1"></i>Skip Payment
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script src="{{ asset('assets/js/data-table.js') }}"></script>
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

        // Handle rejection modal
        function openRejectionModal(rejectUrl, driverName, amount) {
            // Set form action
            document.getElementById('rejectionForm').action = rejectUrl;

            // Set transaction details
            document.getElementById('rejection_driver_name').textContent = driverName;
            document.getElementById('rejection_amount').textContent = amount;

            // Clear previous comment
            document.getElementById('rejection_comment').value = '';

            // Show modal
            const modal = new bootstrap.Modal(document.getElementById('rejectionModal'));
            modal.show();
        }

        // Handle approval modal
        function openApprovalModal(approveUrl, driverName, type, amount) {
            // Set form action
            document.getElementById('approvalForm').action = approveUrl;

            // Set transaction details
            document.getElementById('approval_driver_name').textContent = driverName;

            // Format transaction type
            let formattedType = type;
            if (type === 'charging_payment') {
                formattedType = 'Charging Payment';
            } else {
                formattedType = type.replace('_', ' ').replace(/\b\w/g, l => l.toUpperCase());
            }
            document.getElementById('approval_type').textContent = formattedType;

            // Set amount
            const amountInput = document.getElementById('approval_amount');
            amountInput.value = amount;

            // Reset checkbox and disable amount initially
            const enableEditCheckbox = document.getElementById('enableAmountEdit');
            if (enableEditCheckbox) {
                enableEditCheckbox.checked = false;
                amountInput.disabled = true;
            } else {
                // Non-Super Admin - amount is always disabled
                amountInput.disabled = true;
            }

            // Show modal
            const modal = new bootstrap.Modal(document.getElementById('approvalModal'));
            modal.show();
        }

        // Handle amount edit enable/disable
        document.getElementById('enableAmountEdit')?.addEventListener('change', function() {
            const amountInput = document.getElementById('approval_amount');
            amountInput.disabled = !this.checked;
            if (this.checked) {
                amountInput.focus();
                amountInput.select();
            }
        });

        // Handle skip payment modal
        function openSkipModal(skipUrl, driverName, amount) {
            // Set form action
            document.getElementById('skipForm').action = skipUrl;

            // Set transaction details
            document.getElementById('skip_driver_name').textContent = driverName;
            document.getElementById('skip_amount').textContent = amount;

            // Reset form fields
            document.getElementById('skip_reason').value = '';
            document.getElementById('skip_notes').value = '';

            // Show modal
            const modal = new bootstrap.Modal(document.getElementById('skipModal'));
            modal.show();
        }

        // Handle character count for rejection comment
        document.getElementById('rejection_comment').addEventListener('input', function() {
            const maxLength = 500;
            const currentLength = this.value.length;
            const remaining = maxLength - currentLength;

            // Update character count if it exists
            let charCount = this.parentNode.querySelector('.char-count');
            if (!charCount) {
                charCount = document.createElement('div');
                charCount.className = 'char-count text-muted small mt-1';
                this.parentNode.appendChild(charCount);
            }

            charCount.textContent = `${currentLength}/${maxLength} characters`;

            if (remaining < 50) {
                charCount.classList.add('text-warning');
            } else {
                charCount.classList.remove('text-warning');
            }
        });

        // Initialize tooltips
        var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'))
        var tooltipList = tooltipTriggerList.map(function(tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl)
        });
    </script>
@endpush
