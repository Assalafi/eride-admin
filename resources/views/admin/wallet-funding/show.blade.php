@extends('layouts.app')

@section('title', 'Wallet Funding Request Details')

@section('content')
<div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-4">
    <h3 class="mb-0">Wallet Funding Request #{{ $walletFundingRequest->id }}</h3>

    <nav style="--bs-breadcrumb-divider: '>';" aria-label="breadcrumb">
        <ol class="breadcrumb align-items-center mb-0 lh-1">
            <li class="breadcrumb-item">
                <a href="{{ route('dashboard') }}" class="d-flex align-items-center text-decoration-none">
                    <i class="ri-home-4-line fs-18 text-primary me-1"></i>
                    <span class="text-secondary fw-medium hover">Dashboard</span>
                </a>
            </li>
            <li class="breadcrumb-item">
                <a href="{{ route('admin.wallet-funding.index') }}" class="text-decoration-none">Wallet Funding</a>
            </li>
            <li class="breadcrumb-item active" aria-current="page">
                <span class="fw-medium">Request #{{ $walletFundingRequest->id }}</span>
            </li>
        </ol>
    </nav>
</div>

<div class="row">
    <div class="col-lg-8">
        <!-- Request Details -->
        <div class="card bg-white border-0 rounded-3 mb-4">
            <div class="card-body p-4">
                <h5 class="mb-4">Request Information</h5>

                <div class="row mb-3">
                    <div class="col-md-6">
                        <label class="text-secondary mb-1">Driver</label>
                        <p class="mb-0"><strong>{{ $walletFundingRequest->driver->full_name }}</strong></p>
                    </div>
                    <div class="col-md-6">
                        <label class="text-secondary mb-1">Branch</label>
                        <p class="mb-0">{{ $walletFundingRequest->driver->branch->name }}</p>
                    </div>
                </div>

                <div class="row mb-3">
                    <div class="col-md-6">
                        <label class="text-secondary mb-1">Amount Requested</label>
                        <p class="mb-0 fw-bold text-primary fs-24">₦{{ number_format($walletFundingRequest->amount, 2) }}</p>
                    </div>
                    <div class="col-md-6">
                        <label class="text-secondary mb-1">Current Wallet Balance</label>
                        <p class="mb-0 fs-18">₦{{ number_format($walletFundingRequest->driver->wallet->balance ?? 0, 2) }}</p>
                    </div>
                </div>

                <div class="row mb-3">
                    <div class="col-md-6">
                        <label class="text-secondary mb-1">Status</label>
                        <p class="mb-0">
                            @if($walletFundingRequest->status === 'pending')
                            <span class="badge bg-warning fs-14">Pending</span>
                            @elseif($walletFundingRequest->status === 'approved')
                            <span class="badge bg-success fs-14">Approved</span>
                            @else
                            <span class="badge bg-danger fs-14">Rejected</span>
                            @endif
                        </p>
                    </div>
                    <div class="col-md-6">
                        <label class="text-secondary mb-1">Date Submitted</label>
                        <p class="mb-0">{{ $walletFundingRequest->created_at->format('M d, Y H:i') }}</p>
                    </div>
                </div>

                @if($walletFundingRequest->description)
                <div class="mb-3">
                    <label class="text-secondary mb-1">Driver's Notes</label>
                    <p class="mb-0">{{ $walletFundingRequest->description }}</p>
                </div>
                @endif

                @if($walletFundingRequest->approved_at)
                <div class="row mb-3">
                    <div class="col-md-6">
                        <label class="text-secondary mb-1">Processed By</label>
                        <p class="mb-0">{{ $walletFundingRequest->approver->name ?? 'N/A' }}</p>
                    </div>
                    <div class="col-md-6">
                        <label class="text-secondary mb-1">Processed Date</label>
                        <p class="mb-0">{{ $walletFundingRequest->approved_at->format('M d, Y H:i') }}</p>
                    </div>
                </div>
                @endif

                @if($walletFundingRequest->admin_notes)
                <div class="mb-0">
                    <label class="text-secondary mb-1">Admin Notes</label>
                    <div class="alert alert-info mb-0">
                        {{ $walletFundingRequest->admin_notes }}
                    </div>
                </div>
                @endif
            </div>
        </div>

        <!-- Receipt Image -->
        <div class="card bg-white border-0 rounded-3 mb-4">
            <div class="card-body p-4">
                <h5 class="mb-3">Payment Receipt</h5>
                
                @if($walletFundingRequest->receipt_image)
                <div class="text-center">
                    <img src="{{ asset('storage/' . $walletFundingRequest->receipt_image) }}" 
                         alt="Payment Receipt" 
                         class="img-fluid rounded-3 border"
                         style="max-height: 600px; cursor: pointer;"
                         data-bs-toggle="modal"
                         data-bs-target="#receiptModal">
                    <p class="text-muted mt-2"><small>Click image to enlarge</small></p>
                </div>

                <!-- Full Size Image Modal -->
                <div class="modal fade" id="receiptModal" tabindex="-1">
                    <div class="modal-dialog modal-lg modal-dialog-centered">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title">Payment Receipt</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                            </div>
                            <div class="modal-body p-0">
                                <img src="{{ asset('storage/' . $walletFundingRequest->receipt_image) }}" 
                                     alt="Payment Receipt" 
                                     class="img-fluid w-100">
                            </div>
                        </div>
                    </div>
                </div>
                @else
                <p class="text-muted">No receipt uploaded</p>
                @endif
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        <!-- Quick Actions -->
        @if($walletFundingRequest->isPending())
        <div class="card bg-white border-0 rounded-3 mb-4">
            <div class="card-body p-4">
                <h5 class="mb-3">Quick Actions</h5>

                <div class="d-grid gap-2">
                    <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#approveModal">
                        <i class="ri-check-line me-2"></i> Approve Request
                    </button>
                    <button type="button" class="btn btn-danger" data-bs-toggle="modal" data-bs-target="#rejectModal">
                        <i class="ri-close-line me-2"></i> Reject Request
                    </button>
                    <a href="{{ route('admin.wallet-funding.index') }}" class="btn btn-outline-secondary">
                        <i class="ri-arrow-left-line me-2"></i> Back to List
                    </a>
                </div>
            </div>
        </div>

        <!-- Approve Modal -->
        <div class="modal fade" id="approveModal" tabindex="-1">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Approve Wallet Funding</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <form action="{{ route('admin.wallet-funding.approve', $walletFundingRequest) }}" method="POST">
                        @csrf
                        <div class="modal-body">
                            <p>Are you sure you want to approve this funding request?</p>
                            <div class="alert alert-info">
                                <strong>Driver:</strong> {{ $walletFundingRequest->driver->full_name }}<br>
                                <strong>Amount:</strong> ₦{{ number_format($walletFundingRequest->amount, 2) }}<br>
                                <strong>Current Balance:</strong> ₦{{ number_format($walletFundingRequest->driver->wallet->balance ?? 0, 2) }}<br>
                                <strong>New Balance:</strong> ₦{{ number_format(($walletFundingRequest->driver->wallet->balance ?? 0) + $walletFundingRequest->amount, 2) }}
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Admin Notes (Optional)</label>
                                <textarea class="form-control" name="admin_notes" rows="3" placeholder="Add any notes..."></textarea>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" class="btn btn-success">Approve & Fund Wallet</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Reject Modal -->
        <div class="modal fade" id="rejectModal" tabindex="-1">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Reject Wallet Funding</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <form action="{{ route('admin.wallet-funding.reject', $walletFundingRequest) }}" method="POST">
                        @csrf
                        <div class="modal-body">
                            <p>Are you sure you want to reject this funding request?</p>
                            <div class="alert alert-warning">
                                <strong>Driver:</strong> {{ $walletFundingRequest->driver->full_name }}<br>
                                <strong>Amount:</strong> ₦{{ number_format($walletFundingRequest->amount, 2) }}
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Reason for Rejection <span class="text-danger">*</span></label>
                                <textarea class="form-control" name="admin_notes" rows="3" placeholder="Explain why this request is being rejected..." required></textarea>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" class="btn btn-danger">Reject Request</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        @else
        <div class="card bg-white border-0 rounded-3 mb-4">
            <div class="card-body p-4">
                <h5 class="mb-3">Actions</h5>
                <div class="d-grid">
                    <a href="{{ route('admin.wallet-funding.index') }}" class="btn btn-outline-secondary">
                        <i class="ri-arrow-left-line me-2"></i> Back to List
                    </a>
                </div>
            </div>
        </div>
        @endif

        <!-- Driver Wallet Info -->
        <div class="card bg-white border-0 rounded-3 mb-4">
            <div class="card-body p-4">
                <h5 class="mb-3">Driver Wallet Info</h5>
                
                <div class="mb-3">
                    <small class="text-secondary d-block mb-1">Current Balance</small>
                    <h4 class="text-primary mb-0">₦{{ number_format($walletFundingRequest->driver->wallet->balance ?? 0, 2) }}</h4>
                </div>

                @if($walletFundingRequest->isPending())
                <div class="mb-0">
                    <small class="text-secondary d-block mb-1">Balance After Approval</small>
                    <h4 class="text-success mb-0">₦{{ number_format(($walletFundingRequest->driver->wallet->balance ?? 0) + $walletFundingRequest->amount, 2) }}</h4>
                </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection
