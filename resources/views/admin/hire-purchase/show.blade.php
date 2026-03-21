@extends('layouts.app')

@section('title', 'Contract Details - ' . $hirePurchase->contract_number)

@push('styles')
    <style>
        .timeline {
            position: relative;
            padding-left: 30px;
        }

        .timeline::before {
            content: '';
            position: absolute;
            left: 10px;
            top: 0;
            bottom: 0;
            width: 2px;
            background: #e9ecef;
        }

        .timeline-item {
            position: relative;
            padding-bottom: 20px;
        }

        .timeline-item::before {
            content: '';
            position: absolute;
            left: -24px;
            top: 5px;
            width: 10px;
            height: 10px;
            border-radius: 50%;
            background: #6c757d;
        }

        .timeline-item.paid::before {
            background: #198754;
        }

        .timeline-item.pending::before {
            background: #ffc107;
        }

        .timeline-item.overdue::before {
            background: #dc3545;
        }
    </style>
@endpush

@section('content')
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-4">
        <div>
            <h3 class="mb-1">
                <i class="ri-file-list-3-line me-2"></i>Contract: {{ $hirePurchase->contract_number }}
            </h3>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('admin.hire-purchase.index') }}">Hire Purchase</a></li>
                    <li class="breadcrumb-item active">{{ $hirePurchase->contract_number }}</li>
                </ol>
            </nav>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('admin.hire-purchase.calendar', $hirePurchase) }}" class="btn btn-info">
                <i class="ri-calendar-line me-1"></i>Payment Calendar
            </a>
            <a href="{{ route('admin.hire-purchase.edit', $hirePurchase) }}" class="btn btn-warning">
                <i class="ri-edit-line me-1"></i>Edit Contract
            </a>
            @if ($hirePurchase->status == 'active')
                <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#paymentModal">
                    <i class="ri-add-line me-1"></i>Record Payment
                </button>
            @endif
            <a href="{{ route('admin.hire-purchase.index') }}" class="btn btn-outline-secondary">
                <i class="ri-arrow-left-line me-1"></i>Back
            </a>
        </div>
    </div>

    <div class="row">
        <!-- Left Column - Contract Details -->
        <div class="col-lg-4">
            <!-- Contract Status Card -->
            <div class="card bg-white border-0 rounded-3 mb-4">
                <div class="card-body p-4 text-center">
                    @switch($hirePurchase->status)
                        @case('active')
                            @if ($hirePurchase->is_overdue)
                                <div class="bg-danger bg-opacity-10 rounded-circle d-inline-flex align-items-center justify-content-center mb-3"
                                    style="width: 80px; height: 80px;">
                                    <i class="ri-error-warning-line text-danger fs-32"></i>
                                </div>
                                <h4 class="text-danger">OVERDUE</h4>
                            @else
                                <div class="bg-success bg-opacity-10 rounded-circle d-inline-flex align-items-center justify-content-center mb-3"
                                    style="width: 80px; height: 80px;">
                                    <i class="ri-check-line text-success fs-32"></i>
                                </div>
                                <h4 class="text-success">ACTIVE</h4>
                            @endif
                        @break

                        @case('completed')
                            <div class="bg-primary bg-opacity-10 rounded-circle d-inline-flex align-items-center justify-content-center mb-3"
                                style="width: 80px; height: 80px;">
                                <i class="ri-trophy-line text-primary fs-32"></i>
                            </div>
                            <h4 class="text-primary">COMPLETED</h4>
                        @break

                        @case('defaulted')
                            <div class="bg-danger bg-opacity-10 rounded-circle d-inline-flex align-items-center justify-content-center mb-3"
                                style="width: 80px; height: 80px;">
                                <i class="ri-close-circle-line text-danger fs-32"></i>
                            </div>
                            <h4 class="text-danger">DEFAULTED</h4>
                        @break

                        @default
                            <div class="bg-secondary bg-opacity-10 rounded-circle d-inline-flex align-items-center justify-content-center mb-3"
                                style="width: 80px; height: 80px;">
                                <i class="ri-pause-circle-line text-secondary fs-32"></i>
                            </div>
                            <h4 class="text-secondary">{{ strtoupper($hirePurchase->status) }}</h4>
                    @endswitch

                    <!-- Progress Circle -->
                    <div class="my-4">
                        <div class="position-relative d-inline-block">
                            <svg width="150" height="150" viewBox="0 0 150 150">
                                <circle cx="75" cy="75" r="65" fill="none" stroke="#e9ecef"
                                    stroke-width="10" />
                                <circle cx="75" cy="75" r="65" fill="none" stroke="#198754"
                                    stroke-width="10" stroke-dasharray="{{ $hirePurchase->progress_percentage * 4.08 }} 408"
                                    stroke-linecap="round" transform="rotate(-90 75 75)" />
                            </svg>
                            <div class="position-absolute top-50 start-50 translate-middle text-center">
                                <h3 class="mb-0">{{ $hirePurchase->progress_percentage }}%</h3>
                                <small class="text-muted">Complete</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Driver Info Card -->
            <div class="card bg-white border-0 rounded-3 mb-4">
                <div class="card-body p-4">
                    <h6 class="mb-3"><i class="ri-user-line me-2"></i>Driver Information</h6>
                    <div class="d-flex align-items-center mb-3">
                        <div class="rounded-circle bg-primary text-white d-flex align-items-center justify-content-center me-3"
                            style="width: 50px; height: 50px;">
                            {{ strtoupper(substr($hirePurchase->driver->first_name, 0, 1)) }}{{ strtoupper(substr($hirePurchase->driver->last_name, 0, 1)) }}
                        </div>
                        <div>
                            <h6 class="mb-0">{{ $hirePurchase->driver->full_name }}</h6>
                            <small class="text-muted">{{ $hirePurchase->driver->phone_number }}</small>
                        </div>
                    </div>
                    <p class="mb-1"><strong>Email:</strong> {{ $hirePurchase->driver->user->email }}</p>
                    <p class="mb-0"><strong>Branch:</strong> {{ $hirePurchase->branch->name }}</p>
                </div>
            </div>

            <!-- Vehicle Info Card -->
            <div class="card bg-white border-0 rounded-3 mb-4">
                <div class="card-body p-4">
                    <h6 class="mb-3"><i class="ri-car-line me-2"></i>Vehicle Information</h6>
                    <div class="text-center mb-3">
                        <div class="bg-info bg-opacity-10 rounded-3 p-4 d-inline-block">
                            <i class="ri-car-fill text-info fs-48"></i>
                        </div>
                    </div>
                    <h5 class="text-center mb-3">{{ $hirePurchase->vehicle->plate_number }}</h5>
                    <p class="mb-1"><strong>Make:</strong> {{ $hirePurchase->vehicle->make }}</p>
                    <p class="mb-1"><strong>Model:</strong> {{ $hirePurchase->vehicle->model }}</p>
                    <p class="mb-0"><strong>Value:</strong> ₦{{ number_format($hirePurchase->vehicle_price) }}</p>
                </div>
            </div>
        </div>

        <!-- Right Column - Financial Details -->
        <div class="col-lg-8">
            <!-- Financial Summary -->
            <div class="card bg-white border-0 rounded-3 mb-4">
                <div class="card-body p-4">
                    <h5 class="mb-4"><i class="ri-money-dollar-circle-line me-2"></i>Financial Summary</h5>

                    <div class="row g-4">
                        <div class="col-md-4">
                            <div class="bg-light rounded-3 p-3 text-center">
                                <small class="text-muted d-block">Vehicle Price</small>
                                <h4 class="mb-0">₦{{ number_format($hirePurchase->vehicle_price) }}</h4>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="bg-light rounded-3 p-3 text-center">
                                <small class="text-muted d-block">Down Payment</small>
                                <h4 class="mb-0 text-info">₦{{ number_format($hirePurchase->down_payment) }}</h4>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="bg-light rounded-3 p-3 text-center">
                                <small class="text-muted d-block">Amount to Pay</small>
                                <h4 class="mb-0 text-primary">₦{{ number_format($hirePurchase->total_amount) }}</h4>
                            </div>
                        </div>
                    </div>

                    <hr class="my-4">

                    <div class="row g-4">
                        <div class="col-md-3">
                            <div class="border-start border-success border-4 ps-3">
                                <small class="text-muted d-block">Total Paid</small>
                                <h4 class="mb-0 text-success">₦{{ number_format($hirePurchase->total_paid) }}</h4>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="border-start border-danger border-4 ps-3">
                                <small class="text-muted d-block">Balance</small>
                                <h4 class="mb-0 text-danger">₦{{ number_format($hirePurchase->total_balance) }}</h4>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="border-start border-warning border-4 ps-3">
                                <small class="text-muted d-block">Penalties</small>
                                <h4 class="mb-0 text-warning">₦{{ number_format($hirePurchase->total_penalties) }}</h4>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="border-start border-primary border-4 ps-3">
                                <small class="text-muted d-block">Daily Payment</small>
                                <h4 class="mb-0 text-primary">₦{{ number_format($hirePurchase->daily_payment) }}</h4>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Payment Statistics -->
            <div class="card bg-white border-0 rounded-3 mb-4">
                <div class="card-body p-4">
                    <h5 class="mb-4"><i class="ri-bar-chart-line me-2"></i>Payment Statistics</h5>

                    <div class="row g-3">
                        <div class="col-md-3 col-6">
                            <div class="text-center">
                                <div class="bg-primary bg-opacity-10 rounded-circle d-inline-flex align-items-center justify-content-center mb-2"
                                    style="width: 50px; height: 50px;">
                                    <span class="fw-bold text-primary">{{ $paymentStats['total_payments'] }}</span>
                                </div>
                                <p class="mb-0 small">Total Payments</p>
                            </div>
                        </div>
                        <div class="col-md-3 col-6">
                            <div class="text-center">
                                <div class="bg-success bg-opacity-10 rounded-circle d-inline-flex align-items-center justify-content-center mb-2"
                                    style="width: 50px; height: 50px;">
                                    <span class="fw-bold text-success">{{ $paymentStats['paid_payments'] }}</span>
                                </div>
                                <p class="mb-0 small">Paid</p>
                            </div>
                        </div>
                        <div class="col-md-3 col-6">
                            <div class="text-center">
                                <div class="bg-warning bg-opacity-10 rounded-circle d-inline-flex align-items-center justify-content-center mb-2"
                                    style="width: 50px; height: 50px;">
                                    <span class="fw-bold text-warning">{{ $paymentStats['pending_payments'] }}</span>
                                </div>
                                <p class="mb-0 small">Pending</p>
                            </div>
                        </div>
                        <div class="col-md-3 col-6">
                            <div class="text-center">
                                <div class="bg-danger bg-opacity-10 rounded-circle d-inline-flex align-items-center justify-content-center mb-2"
                                    style="width: 50px; height: 50px;">
                                    <span class="fw-bold text-danger">{{ $paymentStats['overdue_payments'] }}</span>
                                </div>
                                <p class="mb-0 small">Overdue</p>
                            </div>
                        </div>
                    </div>

                    <hr class="my-4">

                    <div class="row g-3">
                        <div class="col-md-4">
                            <div class="d-flex justify-content-between">
                                <span class="text-muted">Payments Made:</span>
                                <strong>{{ $hirePurchase->payments_made }} /
                                    {{ $hirePurchase->total_payment_days }}</strong>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="d-flex justify-content-between">
                                <span class="text-muted">Late Payments:</span>
                                <strong class="text-warning">{{ $hirePurchase->late_payments }}</strong>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="d-flex justify-content-between">
                                <span class="text-muted">Missed Payments:</span>
                                <strong class="text-danger">{{ $hirePurchase->missed_payments }}</strong>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Timeline & Dates -->
            <div class="card bg-white border-0 rounded-3 mb-4">
                <div class="card-body p-4">
                    <h5 class="mb-4"><i class="ri-calendar-line me-2"></i>Contract Timeline</h5>

                    <div class="row g-4 mb-4">
                        <div class="col-md-3">
                            <small class="text-muted d-block">Start Date</small>
                            <strong>{{ $hirePurchase->start_date->format('M d, Y') }}</strong>
                        </div>
                        <div class="col-md-3">
                            <small class="text-muted d-block">Expected End</small>
                            <strong>{{ $hirePurchase->expected_end_date->format('M d, Y') }}</strong>
                        </div>
                        <div class="col-md-3">
                            <small class="text-muted d-block">Last Payment</small>
                            <strong>{{ $hirePurchase->last_payment_date ? $hirePurchase->last_payment_date->format('M d, Y') : 'N/A' }}</strong>
                        </div>
                        <div class="col-md-3">
                            <small class="text-muted d-block">Next Due</small>
                            @if ($hirePurchase->next_payment_due && $hirePurchase->status == 'active')
                                <strong class="{{ $hirePurchase->is_overdue ? 'text-danger' : '' }}">
                                    {{ $hirePurchase->next_payment_due->format('M d, Y') }}
                                </strong>
                            @else
                                <strong>N/A</strong>
                            @endif
                        </div>
                    </div>

                    <!-- Days Analysis -->
                    <div class="bg-light rounded-3 p-3">
                        <div class="row text-center">
                            <div class="col-md-3">
                                <h4 class="mb-0">{{ $daysAnalysis['total_days'] }}</h4>
                                <small class="text-muted">Total Days</small>
                            </div>
                            <div class="col-md-3">
                                <h4 class="mb-0 text-primary">{{ $daysAnalysis['days_elapsed'] }}</h4>
                                <small class="text-muted">Days Elapsed</small>
                            </div>
                            <div class="col-md-3">
                                <h4 class="mb-0 text-info">{{ $daysAnalysis['days_remaining'] }}</h4>
                                <small class="text-muted">Days Remaining</small>
                            </div>
                            <div class="col-md-3">
                                @if ($daysAnalysis['on_track'])
                                    <h4 class="mb-0 text-success"><i class="ri-check-line"></i></h4>
                                    <small class="text-success">On Track</small>
                                @else
                                    <h4 class="mb-0 text-danger"><i class="ri-close-line"></i></h4>
                                    <small class="text-danger">Behind</small>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Recent Payments -->
            <div class="card bg-white border-0 rounded-3 mb-4">
                <div class="card-body p-4">
                    <h5 class="mb-4"><i class="ri-history-line me-2"></i>Payment History</h5>

                    <div class="table-responsive">
                        <table class="table align-middle">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Due Date</th>
                                    <th>Amount</th>
                                    <th>Paid</th>
                                    <th>Penalty</th>
                                    <th>Status</th>
                                    <th>Paid Date</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($hirePurchase->payments->take(30) as $payment)
                                    <tr>
                                        <td>{{ $payment->payment_number }}</td>
                                        <td>{{ $payment->due_date->format('M d') }}</td>
                                        <td>₦{{ number_format($payment->expected_amount) }}</td>
                                        <td>
                                            @if ($payment->amount_paid > 0)
                                                <span
                                                    class="text-success">₦{{ number_format($payment->amount_paid) }}</span>
                                            @else
                                                -
                                            @endif
                                        </td>
                                        <td>
                                            @if ($payment->penalty_amount > 0)
                                                <span
                                                    class="text-danger">₦{{ number_format($payment->penalty_amount) }}</span>
                                            @else
                                                -
                                            @endif
                                        </td>
                                        <td>
                                            @switch($payment->status)
                                                @case('paid')
                                                    <span class="badge bg-success">Paid</span>
                                                @break

                                                @case('pending')
                                                    <span class="badge bg-warning">Pending</span>
                                                @break

                                                @case('overdue')
                                                    <span class="badge bg-danger">Overdue</span>
                                                @break

                                                @default
                                                    <span class="badge bg-secondary">{{ ucfirst($payment->status) }}</span>
                                            @endswitch
                                        </td>
                                        <td>{{ $payment->paid_date ? $payment->paid_date->format('M d') : '-' }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Contract Notes -->
            @if ($hirePurchase->notes)
                <div class="card bg-white border-0 rounded-3 mb-4">
                    <div class="card-body p-4">
                        <h5 class="mb-3"><i class="ri-sticky-note-line me-2"></i>Notes</h5>
                        <p class="mb-0">{{ $hirePurchase->notes }}</p>
                    </div>
                </div>
            @endif
        </div>
    </div>

    <!-- Payment Modal -->
    <div class="modal fade" id="paymentModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <form action="{{ route('admin.hire-purchase.payment', $hirePurchase) }}" method="POST">
                    @csrf
                    <div class="modal-header">
                        <h5 class="modal-title">
                            <i class="ri-money-dollar-circle-line me-2"></i>Record Payment
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="alert alert-info">
                            <div class="d-flex justify-content-between">
                                <span>Expected Daily Payment:</span>
                                <strong>₦{{ number_format($hirePurchase->daily_payment) }}</strong>
                            </div>
                            <div class="d-flex justify-content-between">
                                <span>Current Balance:</span>
                                <strong>₦{{ number_format($hirePurchase->total_balance) }}</strong>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Amount (₦) <span class="text-danger">*</span></label>
                            <input type="number" class="form-control" name="amount"
                                value="{{ $hirePurchase->daily_payment }}" step="0.01" min="1" required>
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
                            <textarea class="form-control" name="notes" rows="2" placeholder="Optional notes..."></textarea>
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
