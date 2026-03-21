@extends('layouts.app')

@section('title', 'Branch Details')

@section('content')
<div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-4">
    <h3 class="mb-0">Branch Details</h3>

    <nav style="--bs-breadcrumb-divider: '>';" aria-label="breadcrumb">
        <ol class="breadcrumb align-items-center mb-0 lh-1">
            <li class="breadcrumb-item">
                <a href="{{ route('dashboard') }}" class="d-flex align-items-center text-decoration-none">
                    <i class="ri-home-4-line fs-18 text-primary me-1"></i>
                    <span class="text-secondary fw-medium hover">Dashboard</span>
                </a>
            </li>
            <li class="breadcrumb-item">
                <a href="{{ route('admin.branches.index') }}" class="text-decoration-none">Branches</a>
            </li>
            <li class="breadcrumb-item active" aria-current="page">
                <span class="fw-medium">{{ $branch->name }}</span>
            </li>
        </ol>
    </nav>
</div>

<div class="row">
    <div class="col-lg-4">
        <div class="card bg-white border-0 rounded-3 mb-4">
            <div class="card-body p-4 text-center">
                <div class="rounded-circle wh-100 bg-primary-div d-flex align-items-center justify-content-center text-white fw-bold fs-32 mx-auto mb-3">
                    <span class="material-symbols-outlined fs-48">business</span>
                </div>
                <h4 class="mb-1">{{ $branch->name }}</h4>
                <p class="text-muted mb-3">{{ $branch->location }}</p>
                
                <div class="d-flex gap-2 justify-content-center">
                    @can('manage branches')
                    <a href="{{ route('admin.branches.edit', $branch) }}" class="btn btn-primary btn-sm">
                        <i class="ri-edit-line"></i> Edit
                    </a>
                    @endcan
                    <a href="{{ route('admin.branches.index') }}" class="btn btn-secondary btn-sm">
                        <i class="ri-arrow-left-line"></i> Back
                    </a>
                </div>
            </div>
        </div>

        <div class="card bg-white border-0 rounded-3 mb-4">
            <div class="card-body p-4">
                <h5 class="mb-3">Statistics</h5>
                <div class="mb-3">
                    <p class="text-secondary mb-1">Total Drivers</p>
                    <h4 class="text-primary mb-0">{{ $branch->drivers->count() }}</h4>
                </div>
                <div class="mb-3">
                    <p class="text-secondary mb-1">Total Vehicles</p>
                    <h4 class="text-success mb-0">{{ $branch->vehicles->count() }}</h4>
                </div>
                <div class="mb-0">
                    <p class="text-secondary mb-1">Staff Members</p>
                    <h4 class="text-info mb-0">{{ $branch->users->count() }}</h4>
                </div>
            </div>
        </div>
    </div>

    <div class="col-lg-8">
        <div class="card bg-white border-0 rounded-3 mb-4">
            <div class="card-body p-4">
                <h5 class="mb-4">Drivers at {{ $branch->name }}</h5>
                <div class="table-responsive">
                    <table class="table align-middle">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Phone</th>
                                <th>Wallet Balance</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($branch->drivers as $driver)
                            <tr>
                                <td>{{ $driver->full_name }}</td>
                                <td>{{ $driver->phone_number }}</td>
                                <td>
                                    <span class="badge bg-success">₦{{ number_format($driver->wallet->balance ?? 0, 2) }}</span>
                                </td>
                                <td>
                                    <a href="{{ route('admin.drivers.show', $driver) }}" class="btn btn-sm btn-primary">
                                        <i class="ri-eye-line"></i>
                                    </a>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="4" class="text-center text-muted py-4">No drivers in this branch</td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="card bg-white border-0 rounded-3 mb-4">
            <div class="card-body p-4">
                <h5 class="mb-4">Vehicles at {{ $branch->name }}</h5>
                <div class="table-responsive">
                    <table class="table align-middle">
                        <thead>
                            <tr>
                                <th>Plate Number</th>
                                <th>Make & Model</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($branch->vehicles as $vehicle)
                            <tr>
                                <td><strong class="text-primary">{{ $vehicle->plate_number }}</strong></td>
                                <td>{{ $vehicle->make }} {{ $vehicle->model }}</td>
                                <td>
                                    @if($vehicle->currentAssignment)
                                    <span class="badge bg-success">Assigned</span>
                                    @else
                                    <span class="badge bg-secondary">Available</span>
                                    @endif
                                </td>
                                <td>
                                    <a href="{{ route('admin.vehicles.show', $vehicle) }}" class="btn btn-sm btn-primary">
                                        <i class="ri-eye-line"></i>
                                    </a>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="4" class="text-center text-muted py-4">No vehicles in this branch</td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="card bg-white border-0 rounded-3 mb-4">
            <div class="card-body p-4">
                <h5 class="mb-4">Staff Members at {{ $branch->name }}</h5>
                <div class="table-responsive">
                    <table class="table align-middle">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Email</th>
                                <th>Role</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($branch->users as $user)
                            <tr>
                                <td>{{ $user->name }}</td>
                                <td>{{ $user->email }}</td>
                                <td>
                                    @foreach($user->roles as $role)
                                    <span class="badge bg-primary">{{ $role->name }}</span>
                                    @endforeach
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="3" class="text-center text-muted py-4">No staff members in this branch</td>
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
