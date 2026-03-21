@extends('layouts.app')

@section('title', 'Funding Request Details')

@section('content')
<div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-4">
    <h3 class="mb-0">Funding Request #{{ $walletFundingRequest->id }}</h3>

    <nav style="--bs-breadcrumb-divider: '>';" aria-label="breadcrumb">
        <ol class="breadcrumb align-items-center mb-0 lh-1">
            <li class="breadcrumb-item">
                <a href="{{ route('dashboard') }}" class="d-flex align-items-center text-decoration-none">
                    <i class="ri-home-4-line fs-18 text-primary me-1"></i>
                    <span class="text-secondary fw-medium hover">Dashboard</span>
                </a>
            </li>
            <li class="breadcrumb-item">
                <a href="{{ route('driver.wallet-funding.index') }}" class="text-decoration-none">Wallet Funding</a>
            </li>
            <li class="breadcrumb-item active" aria-current="page">
                <span class="fw-medium">Request #{{ $walletFundingRequest->id }}</span>
            </li>
        </ol>
    </nav>
</div>

<div class="row">
    <div class="col-lg-8">
        <!-- Request Status Alert -->
        @if($walletFundingRequest->status === 'pending')
        <div class="alert alert-warning mb-4">
            <i class="ri-time-line me-2"></i>
            <strong>Pending Review:</strong> Your request is waiting for manager approval.
        </div>
        @elseif($walletFundingRequest->status === 'approved')
        <div class="alert alert-success mb-4">
            <i class="ri-check-circle-line me-2"></i>
            <strong>Approved!</strong> Your wallet has been funded with ₦{{ number_format($walletFundingRequest->amount, 2) }}.
        </div>
        @else
        <div class="alert alert-danger mb-4">
            <i class="ri-close-circle-line me-2"></i>
            <strong>Rejected:</strong> Your request was not approved. See admin notes below.
        </div>
        @endif

        <!-- Request Details -->
        <div class="card bg-white border-0 rounded-3 mb-4">
            <div class="card-body p-4">
                <h5 class="mb-4">Request Information</h5>

                <div class="row mb-3">
                    <div class="col-md-6">
                        <label class="text-secondary mb-1">Amount Requested</label>
                        <p class="mb-0 fw-bold text-primary fs-24">₦{{ number_format($walletFundingRequest->amount, 2) }}</p>
                    </div>
                    <div class="col-md-6">
                        <label class="text-secondary mb-1">Status</label>
                        <p class="mb-0">
                            @if($walletFundingRequest->status === 'pending')
                            <span class="badge bg-warning fs-14">
                                <i class="ri-time-line me-1"></i> Pending
                            </span>
                            @elseif($walletFundingRequest->status === 'approved')
                            <span class="badge bg-success fs-14">
                                <i class="ri-check-line me-1"></i> Approved
                            </span>
                            @else
                            <span class="badge bg-danger fs-14">
                                <i class="ri-close-line me-1"></i> Rejected
                            </span>
                            @endif
                        </p>
                    </div>
                </div>

                <div class="row mb-3">
                    <div class="col-md-6">
                        <label class="text-secondary mb-1">Submitted Date</label>
                        <p class="mb-0">{{ $walletFundingRequest->created_at->format('M d, Y H:i') }}</p>
                    </div>
                    @if($walletFundingRequest->approved_at)
                    <div class="col-md-6">
                        <label class="text-secondary mb-1">Processed Date</label>
                        <p class="mb-0">{{ $walletFundingRequest->approved_at->format('M d, Y H:i') }}</p>
                    </div>
                    @endif
                </div>

                @if($walletFundingRequest->description)
                <div class="mb-3">
                    <label class="text-secondary mb-1">My Notes</label>
                    <p class="mb-0">{{ $walletFundingRequest->description }}</p>
                </div>
                @endif

                @if($walletFundingRequest->approved_at)
                <div class="mb-3">
                    <label class="text-secondary mb-1">Processed By</label>
                    <p class="mb-0">{{ $walletFundingRequest->approver->name ?? 'System' }}</p>
                </div>
                @endif

                @if($walletFundingRequest->admin_notes)
                <div class="mb-0">
                    <label class="text-secondary mb-1">Admin Response</label>
                    <div class="alert {{ $walletFundingRequest->isApproved() ? 'alert-success' : 'alert-danger' }} mb-0">
                        <i class="ri-message-line me-2"></i>
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
                         style="max-height: 500px; cursor: pointer;"
                         data-bs-toggle="modal"
                         data-bs-target="#receiptModal">
                    <p class="text-muted mt-2"><small>Click image to view full size</small></p>
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
        <div class="card bg-white border-0 rounded-3 mb-4">
            <div class="card-body p-4">
                <h5 class="mb-3">Actions</h5>

                <div class="d-grid gap-2">
                    <a href="{{ route('driver.wallet-funding.index') }}" class="btn btn-outline-secondary">
                        <i class="ri-arrow-left-line me-2"></i> Back to My Requests
                    </a>
                    @if($walletFundingRequest->isPending())
                    <div class="text-center text-muted mt-2">
                        <small><i class="ri-time-line me-1"></i> Waiting for approval...</small>
                    </div>
                    @endif
                </div>
            </div>
        </div>

        <!-- Timeline -->
        <div class="card bg-white border-0 rounded-3 mb-4">
            <div class="card-body p-4">
                <h5 class="mb-3">Request Timeline</h5>

                <div class="timeline">
                    <div class="timeline-item mb-3">
                        <div class="d-flex">
                            <div class="flex-shrink-0">
                                <div class="rounded-circle bg-primary d-flex align-items-center justify-content-center" style="width: 32px; height: 32px;">
                                    <i class="ri-send-plane-line text-white"></i>
                                </div>
                            </div>
                            <div class="flex-grow-1 ms-3">
                                <h6 class="mb-1">Request Submitted</h6>
                                <small class="text-muted">{{ $walletFundingRequest->created_at->format('M d, Y H:i') }}</small>
                            </div>
                        </div>
                    </div>

                    @if($walletFundingRequest->approved_at)
                    <div class="timeline-item">
                        <div class="d-flex">
                            <div class="flex-shrink-0">
                                <div class="rounded-circle {{ $walletFundingRequest->isApproved() ? 'bg-success' : 'bg-danger' }} d-flex align-items-center justify-content-center" style="width: 32px; height: 32px;">
                                    <i class="{{ $walletFundingRequest->isApproved() ? 'ri-check-line' : 'ri-close-line' }} text-white"></i>
                                </div>
                            </div>
                            <div class="flex-grow-1 ms-3">
                                <h6 class="mb-1">{{ $walletFundingRequest->isApproved() ? 'Request Approved' : 'Request Rejected' }}</h6>
                                <small class="text-muted">{{ $walletFundingRequest->approved_at->format('M d, Y H:i') }}</small>
                                @if($walletFundingRequest->approver)
                                <br><small class="text-muted">By: {{ $walletFundingRequest->approver->name }}</small>
                                @endif
                            </div>
                        </div>
                    </div>
                    @endif
                </div>
            </div>
        </div>

        <!-- Request Summary -->
        <div class="card bg-light border-0 rounded-3">
            <div class="card-body p-4">
                <h6 class="mb-3">Request Summary</h6>
                
                <div class="d-flex justify-content-between mb-2">
                    <span class="text-secondary">Request ID:</span>
                    <strong>#{{ $walletFundingRequest->id }}</strong>
                </div>
                <div class="d-flex justify-content-between mb-2">
                    <span class="text-secondary">Amount:</span>
                    <strong class="text-primary">₦{{ number_format($walletFundingRequest->amount, 2) }}</strong>
                </div>
                <div class="d-flex justify-content-between mb-2">
                    <span class="text-secondary">Status:</span>
                    <strong class="text-capitalize">{{ ucfirst($walletFundingRequest->status) }}</strong>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
