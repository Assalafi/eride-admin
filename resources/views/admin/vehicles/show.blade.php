@extends('layouts.app')

@section('title', 'Vehicle Details')

@section('content')
<div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-4">
    <h3 class="mb-0">Vehicle Details</h3>

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
                <span class="fw-medium">{{ $vehicle->plate_number }}</span>
            </li>
        </ol>
    </nav>
</div>

<div class="row">
    <div class="col-lg-4">
        <div class="card bg-white border-0 rounded-3 mb-4">
            <div class="card-body p-4">
                <div class="text-center mb-4">
                    <div class="rounded-circle wh-100 bg-primary-div d-flex align-items-center justify-content-center text-white fw-bold fs-32 mx-auto">
                        <span class="material-symbols-outlined fs-48">directions_car</span>
                    </div>
                </div>
                <h4 class="text-center mb-1">{{ $vehicle->plate_number }}</h4>
                <p class="text-center text-muted mb-4">{{ $vehicle->make }} {{ $vehicle->model }}</p>
                
                <div class="d-flex gap-2 justify-content-center">
                    @can('edit vehicles')
                    <a href="{{ route('admin.vehicles.edit', $vehicle) }}" class="btn btn-primary btn-sm">
                        <i class="ri-edit-line"></i> Edit
                    </a>
                    @endcan
                    <a href="{{ route('admin.vehicles.index') }}" class="btn btn-secondary btn-sm">
                        <i class="ri-arrow-left-line"></i> Back
                    </a>
                </div>
            </div>
        </div>

        <div class="card bg-white border-0 rounded-3 mb-4">
            <div class="card-body p-4">
                <h5 class="mb-3">Vehicle Information</h5>
                <div class="mb-3">
                    <p class="text-secondary mb-1">Branch</p>
                    <p class="fw-semibold">{{ $vehicle->branch->name }}</p>
                </div>
                <div class="mb-3">
                    <p class="text-secondary mb-1">Make</p>
                    <p class="fw-semibold">{{ $vehicle->make }}</p>
                </div>
                <div class="mb-3">
                    <p class="text-secondary mb-1">Model</p>
                    <p class="fw-semibold">{{ $vehicle->model }}</p>
                </div>
                <div class="mb-0">
                    <p class="text-secondary mb-1">Status</p>
                    @if($vehicle->currentAssignment)
                    <span class="badge bg-success">Currently Assigned</span>
                    @else
                    <span class="badge bg-secondary">Available</span>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <div class="col-lg-8">
        @if($vehicle->currentAssignment)
        <div class="card bg-white border-0 rounded-3 mb-4">
            <div class="card-body p-4">
                <h5 class="mb-3">Current Assignment</h5>
                <div class="d-flex align-items-center">
                    <div class="flex-shrink-0">
                        <div class="rounded-circle wh-54 bg-primary-div d-flex align-items-center justify-content-center text-white fw-bold">
                            {{ strtoupper(substr($vehicle->currentAssignment->driver->first_name, 0, 1)) }}{{ strtoupper(substr($vehicle->currentAssignment->driver->last_name, 0, 1)) }}
                        </div>
                    </div>
                    <div class="flex-grow-1 ms-3">
                        <h6 class="mb-1">{{ $vehicle->currentAssignment->driver->full_name }}</h6>
                        <p class="text-muted mb-0">Assigned on {{ $vehicle->currentAssignment->assigned_at->format('M d, Y H:i') }}</p>
                    </div>
                </div>
            </div>
        </div>
        @endif

        <div class="card bg-white border-0 rounded-3 mb-4">
            <div class="card-body p-4">
                <h5 class="mb-4">Assignment History</h5>
                <div class="table-responsive">
                    <table class="table align-middle">
                        <thead>
                            <tr>
                                <th>Driver</th>
                                <th>Assigned</th>
                                <th>Returned</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($vehicle->assignments as $assignment)
                            <tr>
                                <td>{{ $assignment->driver->full_name }}</td>
                                <td>{{ $assignment->assigned_at->format('M d, Y H:i') }}</td>
                                <td>{{ $assignment->returned_at ? $assignment->returned_at->format('M d, Y H:i') : '-' }}</td>
                                <td>
                                    @if($assignment->isActive())
                                    <span class="badge bg-success">Active</span>
                                    @else
                                    <span class="badge bg-secondary">Returned</span>
                                    @endif
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="4" class="text-center text-muted py-4">No assignment history</td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
