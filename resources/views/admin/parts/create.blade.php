@extends('layouts.app')

@section('title', 'Add New Part')

@section('content')
<div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-4">
    <h3 class="mb-0">Add New Part</h3>

    <nav style="--bs-breadcrumb-divider: '>';" aria-label="breadcrumb">
        <ol class="breadcrumb align-items-center mb-0 lh-1">
            <li class="breadcrumb-item">
                <a href="{{ route('dashboard') }}" class="d-flex align-items-center text-decoration-none">
                    <i class="ri-home-4-line fs-18 text-primary me-1"></i>
                    <span class="text-secondary fw-medium hover">Dashboard</span>
                </a>
            </li>
            <li class="breadcrumb-item">
                <a href="{{ route('admin.parts.index') }}" class="text-decoration-none">Parts & Inventory</a>
            </li>
            <li class="breadcrumb-item active" aria-current="page">
                <span class="fw-medium">Add New</span>
            </li>
        </ol>
    </nav>
</div>

<div class="row justify-content-center">
    <div class="col-lg-8">
        <div class="card bg-white border-0 rounded-3 mb-4">
            <div class="card-body p-4">
                <h4 class="mb-4">Part Information</h4>

                <form action="{{ route('admin.parts.store') }}" method="POST" enctype="multipart/form-data">
                    @csrf

                    @if(auth()->user()->hasRole('Super Admin'))
                    <div class="mb-3">
                        <label for="branch_id" class="form-label fw-semibold">Branch <span class="text-danger">*</span></label>
                        <select class="form-select @error('branch_id') is-invalid @enderror" 
                                id="branch_id" 
                                name="branch_id" 
                                required>
                            <option value="">Select Branch...</option>
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
                    @else
                    <div class="alert alert-info mb-3">
                        <i class="ri-information-line me-2"></i>
                        <strong>Branch:</strong> {{ auth()->user()->branch->name }} (Part will be assigned to your branch)
                    </div>
                    @endif

                    <div class="mb-3">
                        <label for="name" class="form-label fw-semibold">Part Name <span class="text-danger">*</span></label>
                        <input type="text" 
                               class="form-control @error('name') is-invalid @enderror" 
                               id="name" 
                               name="name" 
                               value="{{ old('name') }}" 
                               placeholder="e.g., Brake Pad, Oil Filter"
                               required>
                        @error('name')
                        <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label for="sku" class="form-label fw-semibold">SKU <span class="text-danger">*</span></label>
                            <input type="text" 
                                   class="form-control text-uppercase @error('sku') is-invalid @enderror" 
                                   id="sku" 
                                   name="sku" 
                                   value="{{ old('sku') }}" 
                                   placeholder="e.g., BP-001"
                                   required>
                            @error('sku')
                            <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-4 mb-3">
                            <label for="cost" class="form-label fw-semibold">Cost (₦) <span class="text-danger">*</span></label>
                            <input type="number" 
                                   class="form-control @error('cost') is-invalid @enderror" 
                                   id="cost" 
                                   name="cost" 
                                   value="{{ old('cost') }}" 
                                   step="0.01"
                                   min="0"
                                   placeholder="0.00"
                                   required>
                            @error('cost')
                            <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-4 mb-3">
                            <label for="initial_quantity" class="form-label fw-semibold">Initial Quantity <span class="text-danger">*</span></label>
                            <input type="number" 
                                   class="form-control @error('initial_quantity') is-invalid @enderror" 
                                   id="initial_quantity" 
                                   name="initial_quantity" 
                                   value="{{ old('initial_quantity', 0) }}" 
                                   min="0"
                                   placeholder="0"
                                   required>
                            @error('initial_quantity')
                            <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <small class="text-muted">Starting stock for this branch</small>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="picture" class="form-label fw-semibold">Part Picture <span class="text-danger">*</span></label>
                        <input type="file" 
                               class="form-control @error('picture') is-invalid @enderror" 
                               id="picture" 
                               name="picture" 
                               accept="image/*"
                               required>
                        @error('picture')
                        <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                        <small class="text-muted">Accepted formats: JPG, PNG. Max size: 2MB</small>
                    </div>

                    <div class="mb-3">
                        <label for="description" class="form-label fw-semibold">Description</label>
                        <textarea class="form-control @error('description') is-invalid @enderror" 
                                  id="description" 
                                  name="description" 
                                  rows="3"
                                  placeholder="Optional description of the part">{{ old('description') }}</textarea>
                        @error('description')
                        <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="d-flex gap-2 mt-4">
                        <button type="submit" class="btn btn-primary">
                            <span class="material-symbols-outlined me-2" style="vertical-align: middle;">save</span>
                            Create Part
                        </button>
                        <a href="{{ route('admin.parts.index') }}" class="btn btn-secondary">
                            <span class="material-symbols-outlined me-2" style="vertical-align: middle;">cancel</span>
                            Cancel
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
