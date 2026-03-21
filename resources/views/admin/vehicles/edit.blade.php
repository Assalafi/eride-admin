@extends('layouts.app')

@section('title', 'Edit Vehicle')

@section('content')
<div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-4">
    <h3 class="mb-0">Edit Vehicle</h3>

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
                <span class="fw-medium">Edit</span>
            </li>
        </ol>
    </nav>
</div>

<div class="row justify-content-center">
    <div class="col-lg-8">
        <div class="card bg-white border-0 rounded-3 mb-4">
            <div class="card-body p-4">
                <h4 class="mb-4">Edit Vehicle Information</h4>

                <form action="{{ route('admin.vehicles.update', $vehicle) }}" method="POST">
                    @csrf
                    @method('PUT')

                    <div class="mb-3">
                        <label for="plate_number" class="form-label fw-semibold">Plate Number <span class="text-danger">*</span></label>
                        <input type="text" 
                               class="form-control text-uppercase @error('plate_number') is-invalid @enderror" 
                               id="plate_number" 
                               name="plate_number" 
                               value="{{ old('plate_number', $vehicle->plate_number) }}" 
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
                                   value="{{ old('make', $vehicle->make) }}" 
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
                                   value="{{ old('model', $vehicle->model) }}" 
                                   required>
                            @error('model')
                            <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="alert alert-info">
                        <i class="ri-information-line me-2"></i>
                        <strong>Branch:</strong> {{ $vehicle->branch->name }}
                    </div>

                    <div class="d-flex gap-2 mt-4">
                        <button type="submit" class="btn btn-primary">
                            <span class="material-symbols-outlined me-2" style="vertical-align: middle;">save</span>
                            Update Vehicle
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
