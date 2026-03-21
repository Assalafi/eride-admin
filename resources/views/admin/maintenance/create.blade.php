@extends('layouts.app')

@section('title', 'New Maintenance Request')

@section('content')
<div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-4">
    <h3 class="mb-0">New Maintenance Request</h3>

    <nav style="--bs-breadcrumb-divider: '>';" aria-label="breadcrumb">
        <ol class="breadcrumb align-items-center mb-0 lh-1">
            <li class="breadcrumb-item">
                <a href="{{ route('dashboard') }}" class="d-flex align-items-center text-decoration-none">
                    <i class="ri-home-4-line fs-18 text-primary me-1"></i>
                    <span class="text-secondary fw-medium hover">Dashboard</span>
                </a>
            </li>
            <li class="breadcrumb-item">
                <a href="{{ route('admin.maintenance.index') }}" class="text-decoration-none">Maintenance</a>
            </li>
            <li class="breadcrumb-item active" aria-current="page">
                <span class="fw-medium">New Request</span>
            </li>
        </ol>
    </nav>
</div>

<div class="row">
    <div class="col-lg-8">
        <div class="card bg-white border-0 rounded-3 mb-4">
            <div class="card-body p-4">
                <h4 class="mb-4">Create Maintenance Request</h4>

                <form action="{{ route('admin.maintenance.store') }}" method="POST">
                    @csrf

                    <div class="mb-3">
                        <label for="driver_id" class="form-label fw-semibold">Select Driver <span class="text-danger">*</span></label>
                        <select class="form-select @error('driver_id') is-invalid @enderror" id="driver_id" name="driver_id" required>
                            <option value="">Choose a driver...</option>
                            @foreach($drivers as $driver)
                            <option value="{{ $driver->id }}" {{ old('driver_id') == $driver->id ? 'selected' : '' }}>
                                {{ $driver->full_name }} - Wallet: ₦{{ number_format($driver->wallet->balance ?? 0, 2) }}
                            </option>
                            @endforeach
                        </select>
                        @error('driver_id')
                        <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Select Parts <span class="text-danger">*</span></label>
                        <div id="parts-container">
                            <div class="part-row mb-2">
                                <div class="row">
                                    <div class="col-md-8">
                                        <select class="form-select" name="parts[0][part_id]" required>
                                            <option value="">Choose a part...</option>
                                            @foreach($parts as $part)
                                            <option value="{{ $part->id }}">{{ $part->name }} - ₦{{ number_format($part->cost, 2) }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-md-3">
                                        <input type="number" class="form-control" name="parts[0][quantity]" placeholder="Qty" min="1" value="1" required>
                                    </div>
                                    <div class="col-md-1">
                                        <button type="button" class="btn btn-danger btn-sm w-100" onclick="removePartRow(this)" disabled>
                                            <i class="ri-delete-bin-line"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <button type="button" class="btn btn-sm btn-outline-primary mt-2" onclick="addPartRow()">
                            <i class="ri-add-line"></i> Add Another Part
                        </button>
                    </div>

                    <div class="d-flex gap-2 mt-4">
                        <button type="submit" class="btn btn-primary">
                            <span class="material-symbols-outlined me-2" style="vertical-align: middle;">save</span>
                            Create Request
                        </button>
                        <a href="{{ route('admin.maintenance.index') }}" class="btn btn-secondary">
                            <span class="material-symbols-outlined me-2" style="vertical-align: middle;">cancel</span>
                            Cancel
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        <div class="card bg-white border-0 rounded-3 mb-4">
            <div class="card-body p-4">
                <h5 class="mb-3">Information</h5>
                <div class="d-flex align-items-start mb-3">
                    <span class="material-symbols-outlined text-primary me-2">info</span>
                    <p class="mb-0 text-secondary">The request will be submitted for manager approval.</p>
                </div>
                <div class="d-flex align-items-start mb-3">
                    <span class="material-symbols-outlined text-primary me-2">account_balance_wallet</span>
                    <p class="mb-0 text-secondary">Manager will check if driver's wallet has sufficient balance.</p>
                </div>
                <div class="d-flex align-items-start">
                    <span class="material-symbols-outlined text-primary me-2">inventory</span>
                    <p class="mb-0 text-secondary">Parts will be deducted from inventory upon completion.</p>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
let partIndex = 1;

function addPartRow() {
    const container = document.getElementById('parts-container');
    const newRow = document.createElement('div');
    newRow.className = 'part-row mb-2';
    newRow.innerHTML = `
        <div class="row">
            <div class="col-md-8">
                <select class="form-select" name="parts[${partIndex}][part_id]" required>
                    <option value="">Choose a part...</option>
                    @foreach($parts as $part)
                    <option value="{{ $part->id }}">{{ $part->name }} - ₦{{ number_format($part->cost, 2) }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <input type="number" class="form-control" name="parts[${partIndex}][quantity]" placeholder="Qty" min="1" value="1" required>
            </div>
            <div class="col-md-1">
                <button type="button" class="btn btn-danger btn-sm w-100" onclick="removePartRow(this)">
                    <i class="ri-delete-bin-line"></i>
                </button>
            </div>
        </div>
    `;
    container.appendChild(newRow);
    partIndex++;
}

function removePartRow(button) {
    button.closest('.part-row').remove();
}
</script>
@endpush
