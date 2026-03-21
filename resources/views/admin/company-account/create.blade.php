@extends('layouts.app')

@section('title', 'Record Transaction')

@section('content')
<div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-4">
    <h3 class="mb-0">Record Transaction</h3>

    <nav style="--bs-breadcrumb-divider: '>';" aria-label="breadcrumb">
        <ol class="breadcrumb align-items-center mb-0 lh-1">
            <li class="breadcrumb-item">
                <a href="{{ route('dashboard') }}" class="d-flex align-items-center text-decoration-none">
                    <i class="ri-home-4-line fs-18 text-primary me-1"></i>
                    <span class="text-secondary fw-medium hover">Dashboard</span>
                </a>
            </li>
            <li class="breadcrumb-item">
                <a href="{{ route('admin.company-account.index') }}" class="text-decoration-none">Company Account</a>
            </li>
            <li class="breadcrumb-item active" aria-current="page">
                <span class="fw-medium">Record Transaction</span>
            </li>
        </ol>
    </nav>
</div>

<div class="row">
    <div class="col-lg-8">
        <div class="card bg-white border-0 rounded-3">
            <div class="card-body p-4">
                <h5 class="mb-4">Transaction Details</h5>

                <form action="{{ route('admin.company-account.store') }}" method="POST" enctype="multipart/form-data" id="transactionForm">
                    @csrf

                    <div class="row mb-4">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Transaction Type <span class="text-danger">*</span></label>
                            <select class="form-select @error('type') is-invalid @enderror" name="type" id="transactionType" required>
                                <option value="">Select Type</option>
                                <option value="income" {{ old('type') == 'income' ? 'selected' : '' }}>Income (Credit)</option>
                                <option value="expense" {{ old('type') == 'expense' ? 'selected' : '' }}>Expense (Debit)</option>
                            </select>
                            @error('type')
                            <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Branch <span class="text-danger">*</span></label>
                            <select class="form-select @error('branch_id') is-invalid @enderror" name="branch_id" required>
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
                        </div>
                    </div>

                    <div class="row mb-4">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Category <span class="text-danger">*</span></label>
                            <select class="form-select @error('category') is-invalid @enderror" name="category" id="categorySelect" required>
                                <option value="">Select Category</option>
                            </select>
                            @error('category')
                            <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Amount (₦) <span class="text-danger">*</span></label>
                            <input type="number" 
                                   class="form-control @error('amount') is-invalid @enderror" 
                                   name="amount" 
                                   value="{{ old('amount') }}"
                                   min="0.01"
                                   step="0.01"
                                   placeholder="0.00"
                                   required>
                            @error('amount')
                            <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="mb-4">
                        <label class="form-label fw-semibold">Transaction Date <span class="text-danger">*</span></label>
                        <input type="date" 
                               class="form-control @error('transaction_date') is-invalid @enderror" 
                               name="transaction_date" 
                               value="{{ old('transaction_date', date('Y-m-d')) }}"
                               required>
                        @error('transaction_date')
                        <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-4">
                        <label class="form-label fw-semibold">Description <span class="text-danger">*</span></label>
                        <textarea class="form-control @error('description') is-invalid @enderror" 
                                  name="description" 
                                  rows="4" 
                                  placeholder="Enter detailed description of the transaction..."
                                  required>{{ old('description') }}</textarea>
                        @error('description')
                        <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                        <small class="text-muted">Maximum 1000 characters</small>
                    </div>

                    <div class="mb-4">
                        <label class="form-label fw-semibold">Reference (Optional)</label>
                        <input type="text" 
                               class="form-control @error('reference') is-invalid @enderror" 
                               name="reference" 
                               value="{{ old('reference') }}"
                               placeholder="Invoice number, receipt number, etc.">
                        @error('reference')
                        <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-4">
                        <label class="form-label fw-semibold">Receipt/Document (Optional)</label>
                        <input type="file" 
                               class="form-control @error('receipt_document') is-invalid @enderror" 
                               name="receipt_document" 
                               accept=".pdf,.jpg,.jpeg,.png">
                        @error('receipt_document')
                        <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                        <small class="text-muted">Supported formats: PDF, JPG, PNG (Max 5MB)</small>
                    </div>

                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary">
                            <i class="ri-save-line me-2"></i> Record Transaction
                        </button>
                        <a href="{{ route('admin.company-account.index') }}" class="btn btn-outline-secondary">
                            <i class="ri-close-line me-2"></i> Cancel
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        <!-- Help Card -->
        <div class="card bg-white border-0 rounded-3 mb-4">
            <div class="card-body p-4">
                <h6 class="mb-3">
                    <i class="ri-information-line text-info me-2"></i>
                    Transaction Guidelines
                </h6>
                
                <ul class="ps-3 mb-0">
                    <li class="mb-2">
                        <small><strong>Income:</strong> Daily remittances, other company income</small>
                    </li>
                    <li class="mb-2">
                        <small><strong>Expenses:</strong> Fuel, maintenance, salaries, utilities, etc.</small>
                    </li>
                    <li class="mb-2">
                        <small>Always attach receipts for verification</small>
                    </li>
                    <li class="mb-0">
                        <small>Provide detailed descriptions for audit trail</small>
                    </li>
                </ul>
            </div>
        </div>

        <!-- Categories Card -->
        <div class="card bg-light border-0 rounded-3">
            <div class="card-body p-4">
                <h6 class="mb-3">
                    <i class="ri-list-check text-primary me-2"></i>
                    Available Categories
                </h6>
                
                <div class="mb-3">
                    <strong class="text-success d-block mb-2">Income Categories:</strong>
                    <small class="d-block mb-1">• Daily Remittance</small>
                    <small class="d-block">• Other Income</small>
                </div>

                <div>
                    <strong class="text-danger d-block mb-2">Expense Categories:</strong>
                    <small class="d-block mb-1">• Fuel</small>
                    <small class="d-block mb-1">• Maintenance</small>
                    <small class="d-block mb-1">• Salary</small>
                    <small class="d-block mb-1">• Utilities</small>
                    <small class="d-block mb-1">• Rent</small>
                    <small class="d-block mb-1">• Insurance</small>
                    <small class="d-block mb-1">• Loan Payment</small>
                    <small class="d-block">• Other Expense</small>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
// Category options based on transaction type
const incomeCategories = {
    'daily_remittance': 'Daily Remittance',
    'charging': 'EV Charging Service',
    'maintenance_income': 'Maintenance Payment'
};

const expenseCategories = {
    'fuel': 'Fuel',
    'maintenance_expense': 'Maintenance Expense',
    'salary': 'Salary',
    'utilities': 'Utilities',
    'rent': 'Rent',
    'insurance': 'Insurance',
    'loan_payment': 'Loan Payment',
    'miscellaneous': 'Miscellaneous'
};

// Update categories when transaction type changes
document.getElementById('transactionType').addEventListener('change', function() {
    const categorySelect = document.getElementById('categorySelect');
    categorySelect.innerHTML = '<option value="">Select Category</option>';
    
    const categories = this.value === 'income' ? incomeCategories : expenseCategories;
    
    for (const [key, value] of Object.entries(categories)) {
        const option = document.createElement('option');
        option.value = key;
        option.textContent = value;
        categorySelect.appendChild(option);
    }
});

// Trigger change on page load if type is already selected
document.addEventListener('DOMContentLoaded', function() {
    const typeSelect = document.getElementById('transactionType');
    if (typeSelect.value) {
        typeSelect.dispatchEvent(new Event('change'));
        
        // Restore old category value if it exists
        const oldCategory = '{{ old("category") }}';
        if (oldCategory) {
            document.getElementById('categorySelect').value = oldCategory;
        }
    }
});
</script>
@endpush
