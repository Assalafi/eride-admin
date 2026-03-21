@extends('layouts.app')

@section('title', 'Transaction Details')

@section('content')
<div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-4">
    <h3 class="mb-0">Transaction Details #{{ $companyAccountTransaction->id }}</h3>

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
                <span class="fw-medium">Transaction #{{ $companyAccountTransaction->id }}</span>
            </li>
        </ol>
    </nav>
</div>

<div class="row">
    <div class="col-lg-8">
        <!-- Transaction Info -->
        <div class="card bg-white border-0 rounded-3 mb-4">
            <div class="card-body p-4">
                <h5 class="mb-4">Transaction Information</h5>

                <div class="row mb-3">
                    <div class="col-md-6">
                        <label class="text-secondary mb-1">Transaction Type</label>
                        <p class="mb-0">
                            @if($companyAccountTransaction->type === 'income')
                            <span class="badge bg-success fs-14">
                                <i class="ri-arrow-up-line me-1"></i> Income (Credit)
                            </span>
                            @else
                            <span class="badge bg-danger fs-14">
                                <i class="ri-arrow-down-line me-1"></i> Expense (Debit)
                            </span>
                            @endif
                        </p>
                    </div>
                    <div class="col-md-6">
                        <label class="text-secondary mb-1">Amount</label>
                        <p class="mb-0 fw-bold fs-24 {{ $companyAccountTransaction->isIncome() ? 'text-success' : 'text-danger' }}">
                            {{ $companyAccountTransaction->isIncome() ? '+' : '-' }}₦{{ number_format($companyAccountTransaction->amount, 2) }}
                        </p>
                    </div>
                </div>

                <div class="row mb-3">
                    <div class="col-md-6">
                        <label class="text-secondary mb-1">Branch</label>
                        <p class="mb-0"><strong>{{ $companyAccountTransaction->branch->name }}</strong></p>
                    </div>
                    <div class="col-md-6">
                        <label class="text-secondary mb-1">Category</label>
                        <p class="mb-0">
                            <span class="badge bg-secondary fs-14">
                                {{ \App\Models\CompanyAccountTransaction::getCategories()[$companyAccountTransaction->category] ?? $companyAccountTransaction->category }}
                            </span>
                        </p>
                    </div>
                </div>

                <div class="row mb-3">
                    <div class="col-md-6">
                        <label class="text-secondary mb-1">Transaction Date</label>
                        <p class="mb-0">{{ $companyAccountTransaction->transaction_date->format('F d, Y') }}</p>
                    </div>
                    <div class="col-md-6">
                        <label class="text-secondary mb-1">Recorded On</label>
                        <p class="mb-0">{{ $companyAccountTransaction->created_at->format('M d, Y H:i') }}</p>
                    </div>
                </div>

                @if($companyAccountTransaction->reference)
                <div class="mb-3">
                    <label class="text-secondary mb-1">Reference</label>
                    <p class="mb-0"><strong>{{ $companyAccountTransaction->reference }}</strong></p>
                </div>
                @endif

                <div class="mb-3">
                    <label class="text-secondary mb-1">Description</label>
                    <div class="p-3 bg-light rounded-3">
                        <p class="mb-0">{{ $companyAccountTransaction->description }}</p>
                    </div>
                </div>

                <div class="mb-0">
                    <label class="text-secondary mb-1">Recorded By</label>
                    <p class="mb-0">{{ $companyAccountTransaction->recordedBy->name }}</p>
                </div>
            </div>
        </div>

        <!-- Receipt Document -->
        @if($companyAccountTransaction->receipt_document)
        <div class="card bg-white border-0 rounded-3 mb-4">
            <div class="card-body p-4">
                <h5 class="mb-3">Receipt/Document</h5>
                
                @php
                    $extension = pathinfo($companyAccountTransaction->receipt_document, PATHINFO_EXTENSION);
                @endphp

                @if(in_array(strtolower($extension), ['jpg', 'jpeg', 'png']))
                <div class="text-center">
                    <img src="{{ asset('storage/' . $companyAccountTransaction->receipt_document) }}" 
                         alt="Receipt" 
                         class="img-fluid rounded-3 border"
                         style="max-height: 500px; cursor: pointer;"
                         data-bs-toggle="modal"
                         data-bs-target="#receiptModal">
                    <p class="text-muted mt-2"><small>Click to view full size</small></p>
                </div>

                <!-- Full Size Modal -->
                <div class="modal fade" id="receiptModal" tabindex="-1">
                    <div class="modal-dialog modal-lg modal-dialog-centered">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title">Receipt Document</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                            </div>
                            <div class="modal-body p-0">
                                <img src="{{ asset('storage/' . $companyAccountTransaction->receipt_document) }}" 
                                     alt="Receipt" 
                                     class="img-fluid w-100">
                            </div>
                        </div>
                    </div>
                </div>
                @else
                <div class="text-center p-4">
                    <i class="ri-file-pdf-line fs-48 text-danger mb-3"></i>
                    <p class="mb-3">PDF Document</p>
                    <a href="{{ asset('storage/' . $companyAccountTransaction->receipt_document) }}" 
                       target="_blank" 
                       class="btn btn-primary">
                        <i class="ri-download-line me-2"></i> Download Document
                    </a>
                </div>
                @endif
            </div>
        </div>
        @endif
    </div>

    <div class="col-lg-4">
        <!-- Quick Actions -->
        <div class="card bg-white border-0 rounded-3 mb-4">
            <div class="card-body p-4">
                <h5 class="mb-3">Actions</h5>

                <div class="d-grid gap-2">
                    <a href="{{ route('admin.company-account.index') }}" class="btn btn-outline-secondary">
                        <i class="ri-arrow-left-line me-2"></i> Back to List
                    </a>
                    @if($companyAccountTransaction->receipt_document)
                    <a href="{{ asset('storage/' . $companyAccountTransaction->receipt_document) }}" 
                       target="_blank" 
                       class="btn btn-outline-primary">
                        <i class="ri-download-line me-2"></i> Download Receipt
                    </a>
                    @endif
                </div>
            </div>
        </div>

        <!-- Transaction Summary -->
        <div class="card {{ $companyAccountTransaction->isIncome() ? 'bg-success' : 'bg-danger' }} border-0 rounded-3 mb-4 text-white">
            <div class="card-body p-4">
                <h6 class="text-white-50 mb-2">TRANSACTION AMOUNT</h6>
                <h2 class="mb-0 text-white">
                    {{ $companyAccountTransaction->isIncome() ? '+' : '-' }}₦{{ number_format($companyAccountTransaction->amount, 2) }}
                </h2>
                <small class="text-white-50 mt-2 d-block">
                    {{ $companyAccountTransaction->isIncome() ? 'Company Income' : 'Company Expense' }}
                </small>
            </div>
        </div>

        <!-- Details Card -->
        <div class="card bg-light border-0 rounded-3">
            <div class="card-body p-4">
                <h6 class="mb-3">Transaction Summary</h6>
                
                <div class="d-flex justify-content-between mb-2">
                    <span class="text-secondary">ID:</span>
                    <strong>#{{ $companyAccountTransaction->id }}</strong>
                </div>
                <div class="d-flex justify-content-between mb-2">
                    <span class="text-secondary">Type:</span>
                    <strong class="text-capitalize">{{ ucfirst($companyAccountTransaction->type) }}</strong>
                </div>
                <div class="d-flex justify-content-between mb-2">
                    <span class="text-secondary">Branch:</span>
                    <strong>{{ $companyAccountTransaction->branch->name }}</strong>
                </div>
                <div class="d-flex justify-content-between mb-0">
                    <span class="text-secondary">Date:</span>
                    <strong>{{ $companyAccountTransaction->transaction_date->format('M d, Y') }}</strong>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
