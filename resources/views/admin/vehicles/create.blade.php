@extends('layouts.app')

@section('title', 'Add New Vehicle')

@section('content')
<div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-4">
    <h3 class="mb-0">Add New Vehicle</h3>

    <nav style="--bs-breadcrumb-divider: '>';" aria-label="breadcrumb">
        <ol class="breadcrumb align-items-center mb-0 lh-1">
            <li class="breadcrumb-item">
                <a href="{{ route('dashboard') }}" class="d-flex align-items-center text-decoration-none">
                    <i class="ri-home-4-line fs-18 text-primary me-1"></i>
                    <span class="text-secondary fw-medium hover">Dashboard</span>
                </a>
            </li>
            <li class="breadcrumb-item">
                <a href="{{ route('admin.vehicles.index') }}" class="text-decoration-none">Vehicles</a>
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
                <h4 class="mb-4">Vehicle Information</h4>

                <form action="{{ route('admin.vehicles.store') }}" method="POST">
                    @csrf

                    <div class="mb-3">
                        <label for="plate_number" class="form-label fw-semibold">Plate Number <span class="text-danger">*</span></label>
                        <input type="text" 
                               class="form-control text-uppercase @error('plate_number') is-invalid @enderror" 
                               id="plate_number" 
                               name="plate_number" 
                               value="{{ old('plate_number') }}" 
                               placeholder="ABC-123-XY"
                               required>
                        @error('plate_number')
                        <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="make" class="form-label fw-semibold">Make <span class="text-danger">*</span></label>
                            <input type="text" 
                                   class="form-control @error('make') is-invalid @enderror" 
                                   id="make" 
                                   name="make" 
                                   value="{{ old('make') }}" 
                                   placeholder="e.g., Toyota"
                                   required>
                            @error('make')
                            <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-6 mb-3">
                            <label for="model" class="form-label fw-semibold">Model <span class="text-danger">*</span></label>
                            <input type="text" 
                                   class="form-control @error('model') is-invalid @enderror" 
                                   id="model" 
                                   name="model" 
                                   value="{{ old('model') }}" 
                                   placeholder="e.g., Camry"
                                   required>
                            @error('model')
                            <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    @if(auth()->user()->hasRole('Super Admin'))
                    <div class="mb-3">
                        <label for="branch_id" class="form-label fw-semibold">Select Branch <span class="text-danger">*</span></label>
                        <select class="form-select @error('branch_id') is-invalid @enderror" 
                                id="branch_id" 
                                name="branch_id" 
                                required>
                            <option value="">Choose a branch...</option>
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
                    <div class="alert alert-info">
                        <i class="ri-information-line me-2"></i>
                        <strong>Branch:</strong> {{ auth()->user()->branch->name }}
                    </div>
                    @endif

                    <div class="d-flex gap-2 mt-4">
                        <button type="submit" class="btn btn-primary">
                            <span class="material-symbols-outlined me-2" style="vertical-align: middle;">save</span>
                            Create Vehicle
                        </button>
                        <a href="{{ route('admin.vehicles.index') }}" class="btn btn-secondary">
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
