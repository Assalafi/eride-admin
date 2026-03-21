@extends('layouts.app')

@section('title', 'Charging Request Details')

@section('content')
<div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-4">
    <h3 class="mb-0">Charging Request #{{ $chargingRequest->id }}</h3>

    <nav style="--bs-breadcrumb-divider: '>';" aria-label="breadcrumb">
        <ol class="breadcrumb align-items-center mb-0 lh-1">
            <li class="breadcrumb-item">
                <a href="{{ route('dashboard') }}" class="d-flex align-items-center text-decoration-none">
                    <i class="ri-home-4-line fs-18 text-primary me-1"></i>
                    <span class="text-secondary fw-medium hover">Dashboard</span>
                </a>
            </li>
            <li class="breadcrumb-item">
                <a href="{{ route('admin.charging.index') }}" class="text-decoration-none">Charging</a>
            </li>
            <li class="breadcrumb-item active" aria-current="page">
                <span class="fw-medium">Request #{{ $chargingRequest->id }}</span>
            </li>
        </ol>
    </nav>
</div>

<div class="row">
    <div class="col-lg-8">
        <!-- Status & Actions -->
        <div class="card bg-white border-0 rounded-3 mb-4">
            <div class="card-body p-4">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="mb-2">Status</h5>
                        <span class="badge fs-16
                            @if($chargingRequest->status === 'pending') bg-warning
                            @elseif($chargingRequest->status === 'approved') bg-primary
                            @elseif($chargingRequest->status === 'in_progress') bg-info
                            @elseif($chargingRequest->status === 'completed') bg-success
                            @else bg-secondary
                            @endif">
                            {{ ucfirst(str_replace('_', ' ', $chargingRequest->status)) }}
                        </span>
                    </div>
                    <div class="d-flex gap-2">
                        @if($chargingRequest->status === 'pending')
                            @can('approve charging requests')
                            <!-- Only Branch Manager and Super Admin can approve -->
                            <form action="{{ route('admin.charging.start', $chargingRequest) }}" method="POST" onsubmit="return confirm('Are you sure you want to approve this charging request? This will record the payment transaction and allow the operator to start charging.')">
                                @csrf
                                <button type="submit" class="btn btn-primary">
                                    <span class="material-symbols-outlined me-2" style="vertical-align: middle;">verified</span>
                                    Approve Request
                                </button>
                            </form>
                            <form action="{{ route('admin.charging.cancel', $chargingRequest) }}" method="POST" onsubmit="return confirm('Are you sure you want to cancel this charging request? This action cannot be undone.')">
                                @csrf
                                <button type="submit" class="btn btn-danger">
                                    <span class="material-symbols-outlined me-2" style="vertical-align: middle;">cancel</span>
                                    Cancel
                                </button>
                            </form>
                            @else
                            <span class="badge bg-warning fs-16">Waiting for manager approval</span>
                            @endcan
                        @endif

                        @if($chargingRequest->status === 'approved')
                            @can('complete charging requests')
                            <!-- Only Charging Station Operator can start -->
                            <form action="{{ route('admin.charging.operator-start', $chargingRequest) }}" method="POST" onsubmit="return confirm('Are you sure you want to start the charging session? Please ensure the vehicle is properly connected.')">
                                @csrf
                                <button type="submit" class="btn btn-info">
                                    <span class="material-symbols-outlined me-2" style="vertical-align: middle;">ev_station</span>
                                    Start Charging
                                </button>
                            </form>
                            @else
                            <span class="badge bg-primary fs-16">Approved - Waiting for operator</span>
                            @endcan
                        @endif

                        @if($chargingRequest->status === 'in_progress')
                            @can('complete charging requests')
                            <!-- Both Operator and Manager can complete -->
                            <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#completeModal">
                                <span class="material-symbols-outlined me-2" style="vertical-align: middle;">check_circle</span>
                                Complete Charging
                            </button>
                            @else
                            <span class="badge bg-info fs-16">Charging in progress</span>
                            @endcan
                        @endif

                        @if($chargingRequest->status === 'completed')
                            <span class="badge bg-success fs-16">
                                <span class="material-symbols-outlined" style="vertical-align: middle; font-size: 18px;">check_circle</span>
                                Completed
                            </span>
                        @endif

                        <a href="{{ route('admin.charging.index') }}" class="btn btn-secondary">
                            <i class="ri-arrow-left-line"></i> Back
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Charging Details -->
        <div class="card bg-white border-0 rounded-3 mb-4">
            <div class="card-body p-4">
                <h5 class="mb-4">Charging Details</h5>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <p class="text-secondary mb-1">Driver</p>
                        <p class="fw-semibold">{{ $chargingRequest->driver->full_name }}</p>
                    </div>
                    <div class="col-md-6 mb-3">
                        <p class="text-secondary mb-1">Vehicle</p>
                        <p class="fw-semibold">{{ $chargingRequest->vehicle->plate_number }} ({{ $chargingRequest->vehicle->make }} {{ $chargingRequest->vehicle->model }})</p>
                    </div>
                    <div class="col-md-6 mb-3">
                        <p class="text-secondary mb-1">Location</p>
                        <p class="fw-semibold">{{ $chargingRequest->location ?? 'N/A' }}</p>
                    </div>
                    <div class="col-md-6 mb-3">
                        <p class="text-secondary mb-1">Charging Cost</p>
                        <p class="fw-semibold text-primary">₦{{ number_format($chargingRequest->charging_cost, 2) }}</p>
                        <small class="text-muted">(¥{{ number_format($chargingRequest->charging_cost / 203, 2) }})</small>
                    </div>
                </div>

                <hr>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <p class="text-secondary mb-1">Battery Level Before</p>
                        <p class="fw-semibold">
                            @if($chargingRequest->battery_level_before)
                            <span class="badge bg-warning">{{ number_format($chargingRequest->battery_level_before, 0) }}%</span>
                            @else
                            N/A
                            @endif
                        </p>
                    </div>
                    <div class="col-md-6 mb-3">
                        <p class="text-secondary mb-1">Battery Level After</p>
                        <p class="fw-semibold">
                            @if($chargingRequest->battery_level_after)
                            <span class="badge bg-success">{{ number_format($chargingRequest->battery_level_after, 0) }}%</span>
                            @else
                            N/A
                            @endif
                        </p>
                    </div>
                    <div class="col-md-6 mb-3">
                        <p class="text-secondary mb-1">Energy Consumed</p>
                        <p class="fw-semibold">{{ $chargingRequest->energy_consumed ? number_format($chargingRequest->energy_consumed, 2) . ' kWh' : 'N/A' }}</p>
                    </div>
                    <div class="col-md-6 mb-3">
                        <p class="text-secondary mb-1">Duration</p>
                        <p class="fw-semibold">{{ $chargingRequest->duration_minutes ? $chargingRequest->duration_minutes . ' minutes' : 'N/A' }}</p>
                    </div>
                </div>

                <hr>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <p class="text-secondary mb-1">Charging Start</p>
                        <p class="fw-semibold">{{ $chargingRequest->charging_start ? $chargingRequest->charging_start->format('M d, Y H:i:s') : 'N/A' }}</p>
                    </div>
                    <div class="col-md-6 mb-3">
                        <p class="text-secondary mb-1">Charging End</p>
                        <p class="fw-semibold">{{ $chargingRequest->charging_end ? $chargingRequest->charging_end->format('M d, Y H:i:s') : 'N/A' }}</p>
                    </div>
                </div>

                @if($chargingRequest->notes)
                <hr>
                <div class="mb-0">
                    <p class="text-secondary mb-1">Notes</p>
                    <p class="mb-0">{{ $chargingRequest->notes }}</p>
                </div>
                @endif
            </div>
        </div>

        <!-- Payment Receipt -->
        @if($chargingRequest->payment_receipt)
        <div class="card bg-white border-0 rounded-3 mb-4">
            <div class="card-body p-4">
                <h5 class="mb-3">Payment Receipt</h5>
                
                @php
                    $extension = pathinfo($chargingRequest->payment_receipt, PATHINFO_EXTENSION);
                @endphp

                @if(in_array(strtolower($extension), ['jpg', 'jpeg', 'png']))
                <div class="text-center">
                    <img src="{{ asset('storage/' . $chargingRequest->payment_receipt) }}" 
                         alt="Payment Receipt" 
                         class="img-fluid rounded-3 border"
                         style="max-height: 400px; cursor: pointer;"
                         data-bs-toggle="modal"
                         data-bs-target="#receiptModal">
                    <p class="text-muted mt-2"><small>Click to view full size</small></p>
                </div>

                <!-- Receipt Modal -->
                <div class="modal fade" id="receiptModal" tabindex="-1">
                    <div class="modal-dialog modal-lg modal-dialog-centered">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title">Payment Receipt</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                            </div>
                            <div class="modal-body p-0">
                                <img src="{{ asset('storage/' . $chargingRequest->payment_receipt) }}" 
                                     alt="Payment Receipt" 
                                     class="img-fluid w-100">
                            </div>
                        </div>
                    </div>
                </div>
                @else
                <div class="text-center p-4">
                    <i class="ri-file-pdf-line fs-48 text-danger mb-3"></i>
                    <p class="mb-3">PDF Receipt</p>
                    <a href="{{ asset('storage/' . $chargingRequest->payment_receipt) }}" 
                       target="_blank" 
                       class="btn btn-primary">
                        <i class="ri-download-line me-2"></i> Download Receipt
                    </a>
                </div>
                @endif
            </div>
        </div>
        @endif

        <!-- Approval Info -->
        @if($chargingRequest->approver)
        <div class="card bg-white border-0 rounded-3 mb-4">
            <div class="card-body p-4">
                <h5 class="mb-3">Approval Information</h5>
                <div class="row">
                    <div class="col-md-6 mb-2">
                        <p class="text-secondary mb-1">Approved By</p>
                        <p class="fw-semibold">{{ $chargingRequest->approver->name }}</p>
                    </div>
                    <div class="col-md-6 mb-2">
                        <p class="text-secondary mb-1">Approved At</p>
                        <p class="fw-semibold">{{ $chargingRequest->approved_at->format('M d, Y H:i:s') }}</p>
                    </div>
                </div>
            </div>
        </div>
        @endif
    </div>

    <div class="col-lg-4">
        <!-- Timeline -->
        <div class="card bg-white border-0 rounded-3 mb-4">
            <div class="card-body p-4">
                <h5 class="mb-3">Timeline</h5>
                <div class="timeline">
                    <div class="mb-3">
                        <div class="d-flex align-items-start">
                            <span class="material-symbols-outlined text-warning me-2">add_circle</span>
                            <div>
                                <p class="mb-0 fw-semibold">Request Created</p>
                                <small class="text-muted">{{ $chargingRequest->created_at->format('M d, Y H:i') }}</small>
                            </div>
                        </div>
                    </div>

                    @if($chargingRequest->approved_at)
                    <div class="mb-3">
                        <div class="d-flex align-items-start">
                            <span class="material-symbols-outlined text-primary me-2">verified</span>
                            <div>
                                <p class="mb-0 fw-semibold">Request Approved</p>
                                <small class="text-muted">{{ $chargingRequest->approved_at->format('M d, Y H:i') }}</small>
                            </div>
                        </div>
                    </div>
                    @endif

                    @if($chargingRequest->charging_start)
                    <div class="mb-3">
                        <div class="d-flex align-items-start">
                            <span class="material-symbols-outlined text-info me-2">ev_station</span>
                            <div>
                                <p class="mb-0 fw-semibold">Charging Started</p>
                                <small class="text-muted">{{ $chargingRequest->charging_start->format('M d, Y H:i') }}</small>
                            </div>
                        </div>
                    </div>
                    @endif

                    @if($chargingRequest->charging_end)
                    <div class="mb-0">
                        <div class="d-flex align-items-start">
                            <span class="material-symbols-outlined text-success me-2">check_circle</span>
                            <div>
                                <p class="mb-0 fw-semibold">Charging Completed</p>
                                <small class="text-muted">{{ $chargingRequest->charging_end->format('M d, Y H:i') }}</small>
                            </div>
                        </div>
                    </div>
                    @endif
                </div>
            </div>
        </div>

        <!-- Driver Wallet -->
        <div class="card bg-white border-0 rounded-3 mb-4">
            <div class="card-body p-4">
                <h5 class="mb-3">Driver Wallet</h5>
                <p class="text-secondary mb-1">Current Balance</p>
                <h3 class="text-success mb-0">₦{{ number_format($chargingRequest->driver->wallet->balance ?? 0, 2) }}</h3>
                <hr>
                <div class="alert alert-info mb-0 py-2 small">
                    <i class="ri-information-line me-1"></i>
                    <strong>Note:</strong> Driver paid charging cost of ₦{{ number_format($chargingRequest->charging_cost, 2) }} (¥{{ number_format($chargingRequest->charging_cost / 203, 2) }}) directly (not deducted from wallet).
                    @if($chargingRequest->status === 'approved' || $chargingRequest->status === 'in_progress' || $chargingRequest->status === 'completed')
                    <br><i class="ri-checkbox-circle-line me-1 text-success"></i> Payment recorded.
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Complete Charging Modal -->
<div class="modal fade" id="completeModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="{{ route('admin.charging.complete', $chargingRequest) }}" method="POST" onsubmit="return confirm('Are you sure you want to complete this charging session? Please ensure you have entered the correct battery level and energy data.')">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title">Complete Charging Session</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="battery_level_after" class="form-label fw-semibold">Battery Level After (%) <span class="text-danger">*</span></label>
                        <input type="number" class="form-control" id="battery_level_after" name="battery_level_after" min="0" max="100" step="0.01" required>
                        <small class="text-muted">Enter the final battery percentage after charging</small>
                    </div>
                    <div class="mb-3">
                        <label for="energy_consumed" class="form-label fw-semibold">Energy Consumed (kWh)</label>
                        <input type="number" class="form-control" id="energy_consumed" name="energy_consumed" min="0" step="0.01">
                        <small class="text-muted">Optional: Total energy consumed during charging</small>
                    </div>
                    <div class="alert alert-success mb-0">
                        <i class="ri-checkbox-circle-line me-2"></i>
                        Payment of <strong>₦{{ number_format($chargingRequest->charging_cost, 2) }} (¥{{ number_format($chargingRequest->charging_cost / 203, 2) }})</strong> has been recorded. Complete the charging session with final battery data.
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success">Complete Charging Session</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
