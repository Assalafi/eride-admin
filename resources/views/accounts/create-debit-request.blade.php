@extends('layouts.app')

@section('title', 'New Debit Request')

@section('content')
<div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-4">
    <h3 class="mb-0">New Debit Request</h3>

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
                <span class="fw-medium">New Debit Request</span>
            </li>
        </ol>
    </nav>
</div>

<div class="row">
    <div class="col-lg-8">
        <div class="card bg-white border-0 rounded-3 mb-4">
            <div class="card-body p-4">
                <h4 class="mb-4">Request Debit from Company Account</h4>

                @if ($errors->any())
                <div class="alert alert-danger mb-3">
                    <ul class="mb-0">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
                @endif

                <form action="{{ route('accounts.debit-requests.store') }}" method="POST" enctype="multipart/form-data">
                    @csrf

                    <!-- Branch Selection (for Accountants only) -->
                    @if(!$userBranch)
                    <div class="mb-3">
                        <label for="branch_id" class="form-label fw-semibold">Branch <span class="text-danger">*</span></label>
                        <select name="branch_id" 
                                id="branch_id"
                                class="form-select @error('branch_id') is-invalid @enderror" 
                                required>
                            <option value="">Select Branch</option>
                            @foreach($branches as $branch)
                            <option value="{{ $branch->id }}" {{ old('branch_id') == $branch->id ? 'selected' : '' }}>
                                {{ $branch->name }}
                            </option>
                            @endforeach
                        </select>
                        @error('branch_id')
                        <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                        <small class="text-muted">Select which branch this debit request is for</small>
                    </div>
                    @endif

                    <!-- Amount -->
                    <div class="mb-3">
                        <label for="amount" class="form-label fw-semibold">Amount (₦) <span class="text-danger">*</span></label>
                        <input type="number" 
                               name="amount" 
                               id="amount"
                               class="form-control @error('amount') is-invalid @enderror" 
                               placeholder="Enter amount"
                               value="{{ old('amount') }}"
                               min="1"
                               step="0.01"
                               required>
                        @error('amount')
                        <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                        <small class="text-muted">Enter the amount you need to debit from the company account</small>
                    </div>

                    <!-- Description -->
                    <div class="mb-3">
                        <label for="description" class="form-label fw-semibold">Description <span class="text-danger">*</span></label>
                        <textarea name="description" 
                                  id="description"
                                  class="form-control @error('description') is-invalid @enderror" 
                                  rows="5" 
                                  placeholder="Provide detailed description of what this debit is for..."
                                  required>{{ old('description') }}</textarea>
                        @error('description')
                        <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                        <small class="text-muted">Be specific about what this debit request is for (e.g., office supplies, fuel purchase, etc.)</small>
                    </div>

                    <!-- Receipt Upload -->
                    <div class="mb-3">
                        <label for="receipt_document" class="form-label fw-semibold">Supporting Document/Receipt (Optional)</label>
                        <input type="file" 
                               name="receipt_document" 
                               id="receipt_document"
                               class="form-control @error('receipt_document') is-invalid @enderror"
                               accept=".pdf,.jpg,.jpeg,.png">
                        @error('receipt_document')
                        <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                        <small class="text-muted">Upload invoice, receipt, or any supporting document (PDF, JPG, PNG - Max 2MB)</small>
                    </div>

                    <!-- Action Buttons -->
                    <div class="d-flex gap-2 mt-4">
                        <button type="submit" class="btn btn-primary">
                            <span class="material-symbols-outlined me-2" style="vertical-align: middle;">send</span>
                            Submit Request
                        </button>
                        <a href="{{ route('accounts.index') }}" class="btn btn-secondary">
                            <span class="material-symbols-outlined me-2" style="vertical-align: middle;">cancel</span>
                            Cancel
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Info Sidebar -->
    <div class="col-lg-4">
        <!-- Approval Info -->
        <div class="card bg-white border-0 rounded-3 mb-4">
            <div class="card-body p-4">
                <h5 class="mb-3">Approval Process</h5>
                
                <div class="mb-3">
                    <div class="d-flex align-items-center mb-2">
                        <span class="material-symbols-outlined text-primary me-2">info</span>
                        <strong>Approval Threshold</strong>
                    </div>
                    <p class="text-secondary mb-0 ps-4 small">
                        Requests above <strong class="text-primary">₦{{ number_format($threshold, 2) }}</strong> require Super Admin approval.
                    </p>
                </div>

                <div class="mb-3">
                    <div class="d-flex align-items-center mb-2">
                        <span class="material-symbols-outlined text-warning me-2">schedule</span>
                        <strong>Processing Time</strong>
                    </div>
                    <p class="text-secondary mb-0 ps-4 small">
                        Requests are typically reviewed within 24-48 hours.
                    </p>
                </div>

                <div class="mb-0">
                    <div class="d-flex align-items-center mb-2">
                        <span class="material-symbols-outlined text-success me-2">check_circle</span>
                        <strong>After Approval</strong>
                    </div>
                    <p class="text-secondary mb-0 ps-4 small">
                        Once approved, the amount will be automatically debited from the company account.
                    </p>
                </div>
            </div>
        </div>

        <!-- Tips -->
        <div class="card bg-primary bg-opacity-10 border-0 rounded-3 mb-4">
            <div class="card-body p-4">
                <h6 class="fw-bold text-primary mb-3">
                    <span class="material-symbols-outlined me-1" style="vertical-align: middle;">lightbulb</span>
                    Tips
                </h6>
                <ul class="text-secondary mb-0 ps-3 small">
                    <li class="mb-2">Provide clear and detailed description</li>
                    <li class="mb-2">Attach supporting documents when possible</li>
                    <li class="mb-2">Ensure amount is accurate</li>
                    <li class="mb-0">Keep track of your request status</li>
                </ul>
            </div>
        </div>
    </div>
</div>
@endsection
