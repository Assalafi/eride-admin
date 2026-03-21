@extends('layouts.app')

@section('title', 'Request Wallet Funding')

@section('content')
<div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-4">
    <h3 class="mb-0">Request Wallet Funding</h3>

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
                <span class="fw-medium">Request Funding</span>
            </li>
        </ol>
    </nav>
</div>

<div class="row">
    <div class="col-lg-8">
        <div class="card bg-white border-0 rounded-3 mb-4">
            <div class="card-body p-4">
                <h5 class="mb-4">Submit Funding Request</h5>

                <div class="alert alert-info mb-4">
                    <i class="ri-information-line me-2"></i>
                    <strong>Important:</strong> Upload a clear image of your payment receipt. Your request will be reviewed by a manager before funds are added to your wallet.
                </div>

                <form action="{{ route('driver.wallet-funding.store') }}" method="POST" enctype="multipart/form-data" id="fundingForm">
                    @csrf

                    <div class="mb-4">
                        <label class="form-label fw-semibold">Amount (₦) <span class="text-danger">*</span></label>
                        <input type="number" 
                               class="form-control @error('amount') is-invalid @enderror" 
                               name="amount" 
                               value="{{ old('amount') }}"
                               min="100"
                               max="1000000"
                               step="0.01"
                               placeholder="Enter amount"
                               required>
                        @error('amount')
                        <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                        <small class="text-muted">Minimum: ₦100.00 | Maximum: ₦1,000,000.00</small>
                    </div>

                    <div class="mb-4">
                        <label class="form-label fw-semibold">Payment Receipt <span class="text-danger">*</span></label>
                        <input type="file" 
                               class="form-control @error('receipt_image') is-invalid @enderror" 
                               name="receipt_image" 
                               id="receiptImage"
                               accept="image/jpeg,image/png,image/jpg"
                               required>
                        @error('receipt_image')
                        <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                        <small class="text-muted">Upload a clear photo of your payment receipt (JPG, PNG max 5MB)</small>
                        
                        <!-- Image Preview -->
                        <div id="imagePreview" class="mt-3" style="display: none;">
                            <label class="form-label fw-semibold">Preview:</label>
                            <div class="border rounded-3 p-2">
                                <img id="previewImg" src="" alt="Preview" class="img-fluid rounded" style="max-height: 300px;">
                            </div>
                        </div>
                    </div>

                    <div class="mb-4">
                        <label class="form-label fw-semibold">Additional Notes (Optional)</label>
                        <textarea class="form-control @error('description') is-invalid @enderror" 
                                  name="description" 
                                  rows="4" 
                                  placeholder="Add any additional information about this payment...">{{ old('description') }}</textarea>
                        @error('description')
                        <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                        <small class="text-muted">Maximum 500 characters</small>
                    </div>

                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary">
                            <i class="ri-send-plane-line me-2"></i> Submit Request
                        </button>
                        <a href="{{ route('driver.wallet-funding.index') }}" class="btn btn-outline-secondary">
                            <i class="ri-close-line me-2"></i> Cancel
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        <!-- Current Balance -->
        <div class="card bg-white border-0 rounded-3 mb-4">
            <div class="card-body p-4">
                <h6 class="text-secondary mb-2">CURRENT WALLET BALANCE</h6>
                <h3 class="text-primary mb-0">₦{{ number_format($driver->wallet->balance ?? 0, 2) }}</h3>
            </div>
        </div>

        <!-- Tips Card -->
        <div class="card bg-white border-0 rounded-3 mb-4">
            <div class="card-body p-4">
                <h6 class="mb-3">
                    <i class="ri-lightbulb-line text-warning me-2"></i>
                    Tips for Fast Approval
                </h6>
                
                <ul class="ps-3 mb-0">
                    <li class="mb-2">
                        <small>Ensure receipt is clear and readable</small>
                    </li>
                    <li class="mb-2">
                        <small>Amount on receipt should match requested amount</small>
                    </li>
                    <li class="mb-2">
                        <small>Include payment date and reference number</small>
                    </li>
                    <li class="mb-0">
                        <small>Submit requests during business hours for faster processing</small>
                    </li>
                </ul>
            </div>
        </div>

        <!-- Info Card -->
        <div class="card bg-light border-0 rounded-3">
            <div class="card-body p-4">
                <h6 class="mb-3">
                    <i class="ri-information-line text-info me-2"></i>
                    Processing Time
                </h6>
                <p class="mb-0"><small>Wallet funding requests are typically processed within 1-2 business hours. You'll be notified once your request is approved or rejected.</small></p>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
// Image preview
document.getElementById('receiptImage').addEventListener('change', function(e) {
    const file = e.target.files[0];
    if (file) {
        // Check file size (5MB max)
        if (file.size > 5 * 1024 * 1024) {
            alert('File size must be less than 5MB');
            this.value = '';
            return;
        }

        const reader = new FileReader();
        reader.onload = function(event) {
            document.getElementById('previewImg').src = event.target.result;
            document.getElementById('imagePreview').style.display = 'block';
        }
        reader.readAsDataURL(file);
    } else {
        document.getElementById('imagePreview').style.display = 'none';
    }
});

// Form validation
document.getElementById('fundingForm').addEventListener('submit', function(e) {
    const amount = parseFloat(document.querySelector('[name="amount"]').value);
    
    if (amount < 100) {
        e.preventDefault();
        alert('Amount must be at least ₦100.00');
        return false;
    }
    
    if (amount > 1000000) {
        e.preventDefault();
        alert('Amount cannot exceed ₦1,000,000.00');
        return false;
    }

    if (!document.getElementById('receiptImage').files.length) {
        e.preventDefault();
        alert('Please upload a payment receipt');
        return false;
    }

    return true;
});
</script>
@endpush
