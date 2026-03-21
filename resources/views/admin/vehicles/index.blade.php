@extends('layouts.app')

@section('title', 'Vehicles Management')

@push('styles')
<link rel="stylesheet" href="{{ asset('assets/css/data-table.css') }}">
@endpush

@section('content')
<div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-4">
    <h3 class="mb-0">Vehicles Management</h3>

    <nav style="--bs-breadcrumb-divider: '>';" aria-label="breadcrumb">
        <ol class="breadcrumb align-items-center mb-0 lh-1">
            <li class="breadcrumb-item">
                <a href="{{ route('dashboard') }}" class="d-flex align-items-center text-decoration-none">
                    <i class="ri-home-4-line fs-18 text-primary me-1"></i>
                    <span class="text-secondary fw-medium hover">Dashboard</span>
                </a>
            </li>
            <li class="breadcrumb-item active" aria-current="page">
                <span class="fw-medium">Vehicles</span>
            </li>
        </ol>
    </nav>
</div>

<div class="card bg-white border-0 rounded-3 mb-4">
    <div class="card-body p-4">
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-4">
            <h4 class="mb-0">All Vehicles</h4>
            @can('create vehicles')
            <a href="{{ route('admin.vehicles.create') }}" class="btn btn-primary">
                <span class="material-symbols-outlined me-2" style="vertical-align: middle;">add</span>
                Add New Vehicle
            </a>
            @endcan
        </div>

        <div class="default-table-area">
            <div class="table-responsive">
                <table class="table align-middle" id="vehiclesTable">
                    <thead>
                        <tr>
                            <th scope="col">#</th>
                            <th scope="col">Plate Number</th>
                            <th scope="col">Make & Model</th>
                            <th scope="col">Branch</th>
                            <th scope="col">Status</th>
                            <th scope="col">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($vehicles as $vehicle)
                        <tr>
                            <td>{{ $loop->iteration }}</td>
                            <td>
                                <span class="fw-bold text-primary">{{ $vehicle->plate_number }}</span>
                            </td>
                            <td>
                                <div>
                                    <strong>{{ $vehicle->make }}</strong><br>
                                    <small class="text-muted">{{ $vehicle->model }}</small>
                                </div>
                            </td>
                            <td>{{ $vehicle->branch->name }}</td>
                            <td>
                                @if($vehicle->currentAssignment)
                                <span class="badge bg-success">Assigned</span>
                                @else
                                <span class="badge bg-secondary">Available</span>
                                @endif
                            </td>
                            <td>
                                <div class="d-flex align-items-center gap-1">
                                    <a href="{{ route('admin.vehicles.show', $vehicle) }}" class="btn btn-sm btn-primary" title="View">
                                        <i class="ri-eye-line"></i>
                                    </a>
                                    @can('edit vehicles')
                                    <a href="{{ route('admin.vehicles.edit', $vehicle) }}" class="btn btn-sm btn-secondary" title="Edit">
                                        <i class="ri-edit-line"></i>
                                    </a>
                                    @endcan
                                    @can('delete vehicles')
                                    <form action="{{ route('admin.vehicles.destroy', $vehicle) }}" method="POST" class="d-inline" onsubmit="return confirm('Are you sure you want to delete this vehicle?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-danger" title="Delete">
                                            <i class="ri-delete-bin-line"></i>
                                        </button>
                                    </form>
                                    @endcan
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="6" class="text-center py-4">
                                <div class="text-muted">
                                    <span class="material-symbols-outlined fs-48">directions_car_off</span>
                                    <p class="mt-2">No vehicles found</p>
                                </div>
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="mt-3">
                {{ $vehicles->links() }}
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="{{ asset('assets/js/data-table.js') }}"></script>
<script>
    $(document).ready(function() {
        $('#vehiclesTable').DataTable({
            "paging": false,
            "searching": true,
            "info": false
        });
    });
</script>
@endpush
