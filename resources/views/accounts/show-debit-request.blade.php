@extends('layouts.app')

@section('title', 'Debit Request #' . $debitRequest->id)

@section('content')
<div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-4">
    <h3 class="mb-0">Debit Request #{{ $debitRequest->id }}</h3>

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
            <li class="breadcrumb-item">
                <a href="{{ route('accounts.debit-requests.index') }}" class="text-decoration-none">Debit Requests</a>
            </li>
            <li class="breadcrumb-item active" aria-current="page">
                <span class="fw-medium">Request #{{ $debitRequest->id }}</span>
            </li>
        </ol>
    </nav>
</div>

<div class="row">
    <div class="col-lg-8">
        <div class="card bg-white border-0 rounded-3 mb-4">
            <div class="card-body p-4">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h4 class="mb-0">Request Details</h4>
                    @if($debitRequest->status === 'pending')
                    <span class="badge bg-warning">Pending Approval</span>
                    @elseif($debitRequest->status === 'approved')
                    <span class="badge bg-success">Approved</span>
                    @else
                    <span class="badge bg-danger">Rejected</span>
                    @endif
                </div>

                <!-- Amount -->
                <div class="mb-3">
                    <label class="form-label fw-semibold text-secondary">Amount Requested</label>
                    <h3 class="text-dark mb-0">₦{{ number_format($debitRequest->amount, 2) }}</h3>
                    @if($debitRequest->amount >= $threshold)
                    <small class="text-warning">
                        <span class="material-symbols-outlined" style="font-size: 14px; vertical-align: middle;">warning</span>
                        Above approval threshold (₦{{ number_format($threshold, 2) }})
                    </small>
                    @endif
                </div>

                <!-- Description -->
                <div class="mb-3">
                    <label class="form-label fw-semibold text-secondary">Description</label>
                    <div class="bg-light rounded-2 p-3">
                        <p class="text-dark mb-0">{{ $debitRequest->description }}</p>
                    </div>
                </div>

                <!-- Receipt Document -->
                @if($debitRequest->receipt_document)
                <div class="mb-3">
                    <label class="form-label fw-semibold text-secondary">Supporting Document</label>
                    <div>
                        <a href="{{ asset('storage/' . $debitRequest->receipt_document) }}" 
                           target="_blank" 
                           class="btn btn-outline-primary btn-sm">
                            <span class="material-symbols-outlined me-1" style="vertical-align: middle; font-size: 18px;">description</span>
                            View Document
                        </a>
                    </div>
                </div>
                @endif

                <!-- Request Info -->
                <div class="row g-3 mt-2">
                    <div class="col-md-6">
                        <label class="form-label fw-semibold text-secondary">Requested By</label>
                        <p class="text-dark mb-0">{{ $debitRequest->requester->name }}</p>
                        <small class="text-secondary">{{ $debitRequest->requester->email }}</small>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold text-secondary">Branch</label>
                        <p class="text-dark mb-0">{{ $debitRequest->branch->name }}</p>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold text-secondary">Request Date</label>
                        <p class="text-dark mb-0">{{ $debitRequest->created_at->format('M d, Y h:i A') }}</p>
                    </div>
                    @if($debitRequest->approved_at)
                    <div class="col-md-6">
                        <label class="form-label fw-semibold text-secondary">
                            {{ $debitRequest->status === 'approved' ? 'Approved' : 'Rejected' }} Date
                        </label>
                        <p class="text-dark mb-0">{{ $debitRequest->approved_at->format('M d, Y h:i A') }}</p>
                    </div>
                    @endif
                </div>

                <!-- Approval Info -->
                @if($debitRequest->approver)
                <div class="mt-4 pt-4 border-top">
                    <label class="form-label fw-semibold text-secondary">
                        {{ $debitRequest->status === 'approved' ? 'Approved' : 'Rejected' }} By
                    </label>
                    <p class="text-dark mb-2">{{ $debitRequest->approver->name }}</p>
                    
                    @if($debitRequest->approval_notes)
                    <label class="form-label fw-semibold text-secondary mt-3">Notes</label>
                    <div class="bg-light rounded-2 p-3">
                        <p class="text-dark mb-0">{{ $debitRequest->approval_notes }}</p>
                    </div>
                    @endif
                </div>
                @endif
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        @can('approve debit requests')
        @if($debitRequest->status === 'pending' && $debitRequest->requested_by !== auth()->id())
        <!-- Approval Form -->
        <div class="card bg-white border-0 rounded-3 mb-4">
            <div class="card-body p-4">
                <h5 class="mb-3">Review Request</h5>
                
                <form action="{{ route('accounts.debit-requests.review', $debitRequest) }}" method="POST">
                    @csrf
                    
                    <!-- Notes -->
                    <div class="mb-3">
                        <label for="notes" class="form-label fw-semibold">Notes (Optional)</label>
                        <textarea name="notes" 
                                  id="notes"
                                  class="form-control @error('notes') is-invalid @enderror" 
                                  rows="4" 
                                  placeholder="Add any notes or comments...">{{ old('notes') }}</textarea>
                        @error('notes')
                        <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <!-- Action Buttons -->
                    <div class="d-grid gap-2">
                        <button type="submit" 
                                name="action" 
                                value="approve" 
                                class="btn btn-success"
                                onclick="return confirm('Are you sure you want to approve this debit request? The amount will be debited from the company account.')">
                            <span class="material-symbols-outlined me-2" style="vertical-align: middle;">check_circle</span>
                            Approve Request
                        </button>
                        <button type="submit" 
                                name="action" 
                                value="reject" 
                                class="btn btn-danger"
                                onclick="return confirm('Are you sure you want to reject this debit request?')">
                            <span class="material-symbols-outlined me-2" style="vertical-align: middle;">cancel</span>
                            Reject Request
                        </button>
                    </div>
                </form>
            </div>
        </div>
        @endif
        @endcan

        <!-- Status Info -->
        <div class="card bg-white border-0 rounded-3 mb-4">
            <div class="card-body p-4">
                <h5 class="mb-3">Request Timeline</h5>
                
                <!-- Requested -->
                <div class="d-flex align-items-start mb-3">
                    <div class="flex-shrink-0">
                        <div class="rounded-circle bg-primary bg-opacity-10 p-2">
                            <span class="material-symbols-outlined text-primary">add_circle</span>
                        </div>
                    </div>
                    <div class="flex-grow-1 ms-3">
                        <h6 class="mb-1">Request Created</h6>
                        <small class="text-secondary">{{ $debitRequest->created_at->format('M d, Y h:i A') }}</small>
                    </div>
                </div>

                @if($debitRequest->status !== 'pending')
                <!-- Approved/Rejected -->
                <div class="d-flex align-items-start">
                    <div class="flex-shrink-0">
                        <div class="rounded-circle bg-{{ $debitRequest->status === 'approved' ? 'success' : 'danger' }} bg-opacity-10 p-2">
                            <span class="material-symbols-outlined text-{{ $debitRequest->status === 'approved' ? 'success' : 'danger' }}">
                                {{ $debitRequest->status === 'approved' ? 'check_circle' : 'cancel' }}
                            </span>
                        </div>
                    </div>
                    <div class="flex-grow-1 ms-3">
                        <h6 class="mb-1">{{ $debitRequest->status === 'approved' ? 'Approved' : 'Rejected' }}</h6>
                        <small class="text-secondary">{{ $debitRequest->approved_at->format('M d, Y h:i A') }}</small>
                        <p class="text-secondary mb-0 mt-1 small">By {{ $debitRequest->approver->name }}</p>
                    </div>
                </div>
                @endif
            </div>
        </div>

        <!-- Back Button -->
        <div class="d-grid">
            <a href="{{ route('accounts.debit-requests.index') }}" class="btn btn-secondary">
                <span class="material-symbols-outlined me-2" style="vertical-align: middle;">arrow_back</span>
                Back to List
            </a>
        </div>
    </div>
</div>
@endsection
