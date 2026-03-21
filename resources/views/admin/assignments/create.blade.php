@extends('layouts.app')

@section('title', 'New Assignment')

@section('content')
<div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-4">
    <h3 class="mb-0">New Vehicle Assignment</h3>

    <nav style="--bs-breadcrumb-divider: '>';" aria-label="breadcrumb">
        <ol class="breadcrumb align-items-center mb-0 lh-1">
            <li class="breadcrumb-item">
                <a href="{{ route('dashboard') }}" class="d-flex align-items-center text-decoration-none">
                    <i class="ri-home-4-line fs-18 text-primary me-1"></i>
                    <span class="text-secondary fw-medium hover">Dashboard</span>
                </a>
            </li>
            <li class="breadcrumb-item">
                <a href="{{ route('admin.assignments.index') }}" class="text-decoration-none">Assignments</a>
            </li>
            <li class="breadcrumb-item active" aria-current="page">
                <span class="fw-medium">New Assignment</span>
            </li>
        </ol>
    </nav>
</div>

<div class="row justify-content-center">
    <div class="col-lg-8">
        <div class="card bg-white border-0 rounded-3 mb-4">
            <div class="card-body p-4">
                <h4 class="mb-4">Assign Vehicle to Driver</h4>

                <form action="{{ route('admin.assignments.store') }}" method="POST">
                    @csrf

                    <div class="mb-3">
                        <label for="driver_id" class="form-label fw-semibold">Select Driver <span class="text-danger">*</span></label>
                        <select class="form-select @error('driver_id') is-invalid @enderror" id="driver_id" name="driver_id" required>
                            <option value="">Choose a driver...</option>
                            @foreach($drivers as $driver)
                            @php
                                $currentAssignment = $driver->vehicleAssignments->where('returned_at', null)->first();
                            @endphp
                            <option value="{{ $driver->id }}" 
                                    {{ old('driver_id') == $driver->id ? 'selected' : '' }}
                                    data-has-vehicle="{{ $currentAssignment ? 'true' : 'false' }}"
                                    data-current-vehicle="{{ $currentAssignment ? $currentAssignment->vehicle->plate_number : '' }}">
                                {{ $driver->full_name }} - {{ $driver->phone_number }}
                                @if($currentAssignment)
                                    (Currently has: {{ $currentAssignment->vehicle->plate_number }})
                                @endif
                            </option>
                            @endforeach
                        </select>
                        @error('driver_id')
                        <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                        <div id="driverWarning" class="alert alert-warning mt-2" style="display: none;">
                            <i class="ri-alert-line me-2"></i>
                            <span id="driverWarningText"></span>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="vehicle_id" class="form-label fw-semibold">Select Vehicle <span class="text-danger">*</span></label>
                        <select class="form-select @error('vehicle_id') is-invalid @enderror" id="vehicle_id" name="vehicle_id" required>
                            <option value="">Choose a vehicle...</option>
                            @foreach($vehicles as $vehicle)
                            @php
                                $vehicleAssignment = $vehicle->assignments()->whereNull('returned_at')->with('driver')->first();
                                $isAssigned = $vehicleAssignment ? true : false;
                            @endphp
                            <option value="{{ $vehicle->id }}" 
                                    {{ old('vehicle_id') == $vehicle->id ? 'selected' : '' }}
                                    {{ $isAssigned ? 'disabled' : '' }}
                                    data-is-assigned="{{ $isAssigned ? 'true' : 'false' }}"
                                    data-assigned-to="{{ $isAssigned ? $vehicleAssignment->driver->full_name : '' }}">
                                {{ $vehicle->plate_number }} - {{ $vehicle->make }} {{ $vehicle->model }}
                                @if($isAssigned)
                                    (Assigned to: {{ $vehicleAssignment->driver->full_name }})
                                @else
                                    (Available ✓)
                                @endif
                            </option>
                            @endforeach
                        </select>
                        @error('vehicle_id')
                        <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <!-- Auto-return previous vehicle checkbox -->
                    <div id="autoReturnContainer" class="mb-3" style="display: none;">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="auto_return_previous" name="auto_return_previous" value="1">
                            <label class="form-check-label" for="auto_return_previous">
                                <strong>Automatically return driver's current vehicle</strong>
                                <small class="d-block text-muted">Enable this to automatically return the driver's current vehicle before assigning the new one.</small>
                            </label>
                        </div>
                    </div>

                    <div class="alert alert-info">
                        <i class="ri-information-line me-2"></i>
                        <strong>Smart Assignment Rules:</strong>
                        <ul class="mb-0 mt-2">
                            <li>Each driver can only have ONE active vehicle at a time</li>
                            <li>Each vehicle can only be assigned to ONE driver at a time</li>
                            <li>Driver and vehicle must be in the same branch</li>
                            <li>Already assigned vehicles are disabled in the list</li>
                        </ul>
                    </div>

                    <div class="d-flex gap-2 mt-4">
                        <button type="submit" class="btn btn-primary">
                            <span class="material-symbols-outlined me-2" style="vertical-align: middle;">save</span>
                            Create Assignment
                        </button>
                        <a href="{{ route('admin.assignments.index') }}" class="btn btn-secondary">
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

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const driverSelect = document.getElementById('driver_id');
    const driverWarning = document.getElementById('driverWarning');
    const driverWarningText = document.getElementById('driverWarningText');
    const autoReturnContainer = document.getElementById('autoReturnContainer');
    const autoReturnCheckbox = document.getElementById('auto_return_previous');
    
    // Handle driver selection
    driverSelect.addEventListener('change', function() {
        const selectedOption = this.options[this.selectedIndex];
        const hasVehicle = selectedOption.getAttribute('data-has-vehicle') === 'true';
        const currentVehicle = selectedOption.getAttribute('data-current-vehicle');
        
        if (hasVehicle && currentVehicle) {
            // Show warning
            driverWarning.style.display = 'block';
            driverWarningText.textContent = 'This driver currently has vehicle: ' + currentVehicle + 
                '. You must either return it first or enable auto-return below.';
            
            // Show auto-return option
            autoReturnContainer.style.display = 'block';
        } else {
            // Hide warning and auto-return
            driverWarning.style.display = 'none';
            autoReturnContainer.style.display = 'none';
            autoReturnCheckbox.checked = false;
        }
    });
    
    // Trigger on page load if driver is already selected (for validation errors)
    if (driverSelect.value) {
        driverSelect.dispatchEvent(new Event('change'));
    }
    
    // Add visual indicators for available vs assigned vehicles
    const vehicleSelect = document.getElementById('vehicle_id');
    const vehicleOptions = vehicleSelect.querySelectorAll('option');
    
    vehicleOptions.forEach(option => {
        const isAssigned = option.getAttribute('data-is-assigned') === 'true';
        if (isAssigned) {
            option.style.color = '#999';
            option.style.fontStyle = 'italic';
        } else {
            option.style.color = '#28a745';
            option.style.fontWeight = '500';
        }
    });
});
</script>
@endpush
