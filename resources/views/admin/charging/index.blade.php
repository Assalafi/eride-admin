@extends('layouts.app')

@section('title', 'Charging Requests')

@section('content')
<div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-4">
    <h3 class="mb-0">Charging Requests</h3>

    <nav style="--bs-breadcrumb-divider: '>';" aria-label="breadcrumb">
        <ol class="breadcrumb align-items-center mb-0 lh-1">
            <li class="breadcrumb-item">
                <a href="{{ route('dashboard') }}" class="d-flex align-items-center text-decoration-none">
                    <i class="ri-home-4-line fs-18 text-primary me-1"></i>
                    <span class="text-secondary fw-medium hover">Dashboard</span>
                </a>
            </li>
            <li class="breadcrumb-item active" aria-current="page">
                <span class="fw-medium">Charging</span>
            </li>
        </ol>
    </nav>
</div>

<!-- Statistics Cards -->
<div class="row mb-4">
    <div class="col-lg-3 col-sm-6">
        <div class="card bg-white border-0 rounded-3 mb-4">
            <div class="card-body p-4">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <span class="text-secondary d-block mb-1">Pending</span>
                        <h3 class="mb-0 text-warning">{{ $stats['pending'] }}</h3>
                    </div>
                    <div class="rounded-circle wh-60 bg-warning-div d-flex align-items-center justify-content-center">
                        <span class="material-symbols-outlined text-warning fs-32">schedule</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-lg-3 col-sm-6">
        <div class="card bg-white border-0 rounded-3 mb-4">
            <div class="card-body p-4">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <span class="text-secondary d-block mb-1">In Progress</span>
                        <h3 class="mb-0 text-info">{{ $stats['in_progress'] }}</h3>
                    </div>
                    <div class="rounded-circle wh-60 bg-info-div d-flex align-items-center justify-content-center">
                        <span class="material-symbols-outlined text-info fs-32">ev_station</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-lg-3 col-sm-6">
        <div class="card bg-white border-0 rounded-3 mb-4">
            <div class="card-body p-4">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <span class="text-secondary d-block mb-1">Completed</span>
                        <h3 class="mb-0 text-success">{{ $stats['completed'] }}</h3>
                    </div>
                    <div class="rounded-circle wh-60 bg-success-div d-flex align-items-center justify-content-center">
                        <span class="material-symbols-outlined text-success fs-32">check_circle</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-lg-3 col-sm-6">
        <div class="card bg-white border-0 rounded-3 mb-4">
            <div class="card-body p-4">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <span class="text-secondary d-block mb-1">Total Cost</span>
                        <h3 class="mb-0 text-primary">₦{{ number_format($stats['total_cost'], 2) }}</h3>
                    </div>
                    <div class="rounded-circle wh-60 bg-primary-div d-flex align-items-center justify-content-center">
                        <span class="material-symbols-outlined text-primary fs-32">payments</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Filters -->
<div class="card bg-white border-0 rounded-3 mb-4">
    <div class="card-body p-4">
        <form method="GET" action="{{ route('admin.charging.index') }}" id="filterForm">
            <div class="row g-3">
                <div class="col-lg-2 col-md-4">
                    <label class="form-label">Time Period</label>
                    <select class="form-select" name="time_filter" id="timeFilter">
                        <option value="daily" {{ $timeFilter == 'daily' ? 'selected' : '' }}>Today</option>
                        <option value="weekly" {{ $timeFilter == 'weekly' ? 'selected' : '' }}>This Week</option>
                        <option value="monthly" {{ $timeFilter == 'monthly' ? 'selected' : '' }}>This Month</option>
                        <option value="yearly" {{ $timeFilter == 'yearly' ? 'selected' : '' }}>This Year</option>
                        <option value="custom" {{ $timeFilter == 'custom' ? 'selected' : '' }}>Custom Range</option>
                    </select>
                </div>

                <div class="col-lg-2 col-md-4" id="startDateDiv" style="display: {{ $timeFilter == 'custom' ? 'block' : 'none' }};">
                    <label class="form-label">Start Date</label>
                    <input type="date" class="form-control" name="start_date" value="{{ $startDate }}">
                </div>

                <div class="col-lg-2 col-md-4" id="endDateDiv" style="display: {{ $timeFilter == 'custom' ? 'block' : 'none' }};">
                    <label class="form-label">End Date</label>
                    <input type="date" class="form-control" name="end_date" value="{{ $endDate }}">
                </div>

                <div class="col-lg-2 col-md-4">
                    <label class="form-label">Driver</label>
                    <select class="form-select" name="driver_id">
                        <option value="">All Drivers</option>
                        @foreach($drivers as $driver)
                        <option value="{{ $driver->id }}" {{ $driverId == $driver->id ? 'selected' : '' }}>
                            {{ $driver->full_name }}
                        </option>
                        @endforeach
                    </select>
                </div>

                <div class="col-lg-2 col-md-4">
                    <label class="form-label">Status</label>
                    <select class="form-select" name="status">
                        <option value="">All Status</option>
                        <option value="pending" {{ $status == 'pending' ? 'selected' : '' }}>Pending</option>
                        <option value="in_progress" {{ $status == 'in_progress' ? 'selected' : '' }}>In Progress</option>
                        <option value="completed" {{ $status == 'completed' ? 'selected' : '' }}>Completed</option>
                        <option value="cancelled" {{ $status == 'cancelled' ? 'selected' : '' }}>Cancelled</option>
                    </select>
                </div>

                <div class="col-lg-2 col-md-4 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary me-2">
                        <i class="ri-filter-line me-1"></i> Apply
                    </button>
                    <a href="{{ route('admin.charging.index') }}" class="btn btn-outline-secondary">
                        <i class="ri-refresh-line"></i>
                    </a>
                </div>
            </div>
        </form>
    </div>
</div>

<div class="card bg-white border-0 rounded-3 mb-4">
    <div class="card-body p-0">
        <div class="p-4 d-flex justify-content-between align-items-center border-bottom">
            <h5 class="mb-0">Charging Requests List</h5>
            <a href="{{ route('admin.charging.create') }}" class="btn btn-primary">
                <span class="material-symbols-outlined me-2" style="vertical-align: middle;">add</span>
                New Charging Request
            </a>
        </div>

        <div class="default-table-area">
            <div class="table-responsive">
                <table class="table align-middle">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Driver</th>
                            <th>Vehicle</th>
                            <th>Location</th>
                            <th>Battery %</th>
                            <th>Cost</th>
                            <th>Duration</th>
                            <th>Status</th>
                            <th>Date</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($chargingRequests as $request)
                        <tr>
                            <td>{{ ($chargingRequests->currentPage() - 1) * $chargingRequests->perPage() + $loop->iteration }}</td>
                            <td>{{ $request->driver->full_name }}</td>
                            <td><strong>{{ $request->vehicle->plate_number }}</strong></td>
                            <td>{{ $request->location ?? 'N/A' }}</td>
                            <td>
                                @if($request->battery_level_before)
                                <span class="badge bg-warning">{{ number_format($request->battery_level_before, 0) }}%</span>
                                @endif
                                @if($request->battery_level_after)
                                → <span class="badge bg-success">{{ number_format($request->battery_level_after, 0) }}%</span>
                                @endif
                            </td>
                            <td>
                                <div>
                                    <strong>₦{{ number_format($request->charging_cost, 2) }}</strong>
                                    <br>
                                    <small class="text-muted">
                                        (¥{{ number_format($request->charging_cost / 203, 2) }})
                                    </small>
                                </div>
                            </td>
                            <td>{{ $request->duration_minutes ? $request->duration_minutes . ' min' : 'N/A' }}</td>
                            <td>
                                <span class="badge 
                                    @if($request->status === 'pending') bg-warning
                                    @elseif($request->status === 'in_progress') bg-info
                                    @elseif($request->status === 'completed') bg-success
                                    @else bg-secondary
                                    @endif">
                                    {{ ucfirst(str_replace('_', ' ', $request->status)) }}
                                </span>
                            </td>
                            <td>{{ $request->created_at->format('M d, Y') }}</td>
                            <td>
                                <a href="{{ route('admin.charging.show', $request) }}" class="btn btn-sm btn-primary">
                                    <i class="ri-eye-line"></i>
                                </a>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="10" class="text-center py-4 text-muted">No charging requests found</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="mt-3 px-4 pb-4">
                {{ $chargingRequests->links() }}
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
// Handle time filter changes
document.getElementById('timeFilter').addEventListener('change', function() {
    const customDivs = ['startDateDiv', 'endDateDiv'];
    if (this.value === 'custom') {
        customDivs.forEach(id => {
            document.getElementById(id).style.display = 'block';
        });
    } else {
        customDivs.forEach(id => {
            document.getElementById(id).style.display = 'none';
        });
    }
});
</script>
@endpush
