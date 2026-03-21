@extends('layouts.app')

@section('title', 'New Charging Request')

@section('content')
<div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-4">
    <h3 class="mb-0">New Charging Request</h3>

    <nav style="--bs-breadcrumb-divider: '>';" aria-label="breadcrumb">
        <ol class="breadcrumb align-items-center mb-0 lh-1">
            <li class="breadcrumb-item">
                <a href="{{ route('dashboard') }}" class="d-flex align-items-center text-decoration-none">
                    <i class="ri-home-4-line fs-18 text-primary me-1"></i>
                    <span class="text-secondary fw-medium hover">Dashboard</span>
                </a>
            </li>
            <li class="breadcrumb-item">
                <a href="{{ route('admin.charging.index') }}" class="text-decoration-none">Charging</a>
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
                <h4 class="mb-4">Charging Request Details</h4>

                <form action="{{ route('admin.charging.store') }}" method="POST" enctype="multipart/form-data">
                    @csrf

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="driver_id" class="form-label fw-semibold">Driver <span class="text-danger">*</span></label>
                            <select class="form-select @error('driver_id') is-invalid @enderror" 
                                    id="driver_id" 
                                    name="driver_id" 
                                    required>
                                <option value="">Select driver...</option>
                                @foreach($drivers as $driver)
                                <option value="{{ $driver->id }}" {{ old('driver_id') == $driver->id ? 'selected' : '' }}>
                                    {{ $driver->full_name }}
                                </option>
                                @endforeach
                            </select>
                            @error('driver_id')
                            <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-6 mb-3">
                            <label for="vehicle_id" class="form-label fw-semibold">Vehicle <span class="text-danger">*</span></label>
                            <select class="form-select @error('vehicle_id') is-invalid @enderror" 
                                    id="vehicle_id" 
                                    name="vehicle_id" 
                                    required>
                                <option value="">Select vehicle...</option>
                                @foreach($vehicles as $vehicle)
                                <option value="{{ $vehicle->id }}" {{ old('vehicle_id') == $vehicle->id ? 'selected' : '' }}>
                                    {{ $vehicle->plate_number }} ({{ $vehicle->make }} {{ $vehicle->model }})
                                </option>
                                @endforeach
                            </select>
                            @error('vehicle_id')
                            <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="location" class="form-label fw-semibold">Charging Location</label>
                            <input type="text" 
                                   class="form-control @error('location') is-invalid @enderror" 
                                   id="location" 
                                   name="location" 
                                   value="{{ old('location') }}" 
                                   placeholder="e.g., Main Depot, Station A">
                            @error('location')
                            <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-6 mb-3">
                            <label for="battery_level_before" class="form-label fw-semibold">Battery Level Before (%)</label>
                            <input type="number" 
                                   class="form-control @error('battery_level_before') is-invalid @enderror" 
                                   id="battery_level_before" 
                                   name="battery_level_before" 
                                   value="{{ old('battery_level_before') }}" 
                                   min="0" 
                                   max="100" 
                                   step="0.01"
                                   placeholder="e.g., 25">
                            @error('battery_level_before')
                            <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="charging_cost" class="form-label fw-semibold">Charging Cost (₦) <span class="text-danger">*</span></label>
                        <input type="number" 
                               class="form-control @error('charging_cost') is-invalid @enderror" 
                               id="charging_cost" 
                               name="charging_cost" 
                               value="{{ old('charging_cost', $defaultCost) }}" 
                               min="0" 
                               step="0.01"
                               required>
                        @error('charging_cost')
                        <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                        <small class="text-muted">Default cost from settings: ₦{{ number_format($defaultCost, 2) }}</small>
                    </div>

                    <div class="mb-3">
                        <label for="payment_receipt" class="form-label fw-semibold">Payment Receipt <span class="text-danger">*</span></label>
                        <input type="file" 
                               class="form-control @error('payment_receipt') is-invalid @enderror" 
                               id="payment_receipt" 
                               name="payment_receipt" 
                               accept=".jpg,.jpeg,.png,.pdf"
                               required>
                        @error('payment_receipt')
                        <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                        <small class="text-muted">Upload payment receipt (JPG, PNG, PDF - Max 5MB)</small>
                    </div>

                    <div class="mb-3">
                        <label for="notes" class="form-label fw-semibold">Notes</label>
                        <textarea class="form-control @error('notes') is-invalid @enderror" 
                                  id="notes" 
                                  name="notes" 
                                  rows="3">{{ old('notes') }}</textarea>
                        @error('notes')
                        <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="d-flex gap-2 mt-4">
                        <button type="submit" class="btn btn-primary">
                            <span class="material-symbols-outlined me-2" style="vertical-align: middle;">save</span>
                            Create Request
                        </button>
                        <a href="{{ route('admin.charging.index') }}" class="btn btn-secondary">
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
                <h5 class="mb-3">
                    <span class="material-symbols-outlined me-2" style="vertical-align: middle;">ev_station</span>
                    Information
                </h5>
                <p class="text-secondary mb-3">
                    <strong>Charging Process:</strong>
                </p>
                <ol class="text-secondary mb-0">
                    <li>Create charging request</li>
                    <li>Start charging session</li>
                    <li>Monitor charging progress</li>
                    <li>Complete session with final battery level</li>
                    <li>Payment automatically deducted from driver wallet</li>
                </ol>
            </div>
        </div>

        <div class="card bg-white border-0 rounded-3 mb-4">
            <div class="card-body p-4">
                <h5 class="mb-3">
                    <span class="material-symbols-outlined me-2" style="vertical-align: middle;">settings</span>
                    Default Settings
                </h5>
                <div class="mb-2">
                    <span class="text-secondary">Charging Cost:</span>
                    <span class="fw-semibold">₦{{ number_format($defaultCost, 2) }}</span>
                </div>
                <small class="text-muted">You can modify this in System Settings</small>
            </div>
        </div>
    </div>
</div>
@endsection
