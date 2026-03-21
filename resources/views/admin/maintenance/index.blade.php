@extends('layouts.app')

@section('title', 'Maintenance Requests')

@push('styles')
<link rel="stylesheet" href="{{ asset('assets/css/data-table.css') }}">
@endpush

@section('content')
<div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-4">
    <h3 class="mb-0">Maintenance Requests</h3>

    <nav style="--bs-breadcrumb-divider: '>';" aria-label="breadcrumb">
        <ol class="breadcrumb align-items-center mb-0 lh-1">
            <li class="breadcrumb-item">
                <a href="{{ route('dashboard') }}" class="d-flex align-items-center text-decoration-none">
                    <i class="ri-home-4-line fs-18 text-primary me-1"></i>
                    <span class="text-secondary fw-medium hover">Dashboard</span>
                </a>
            </li>
            <li class="breadcrumb-item active" aria-current="page">
                <span class="fw-medium">Maintenance</span>
            </li>
        </ol>
    </nav>
</div>

<!-- Filters -->
<div class="card bg-white border-0 rounded-3 mb-4">
    <div class="card-body p-4">
        <form method="GET" action="{{ route('admin.maintenance.index') }}" id="filterForm">
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
                        <option value="pending_manager_approval" {{ $status == 'pending_manager_approval' ? 'selected' : '' }}>Pending Manager</option>
                        <option value="pending_store_approval" {{ $status == 'pending_store_approval' ? 'selected' : '' }}>Pending Store</option>
                        <option value="completed" {{ $status == 'completed' ? 'selected' : '' }}>Completed</option>
                        <option value="manager_denied" {{ $status == 'manager_denied' ? 'selected' : '' }}>Denied</option>
                    </select>
                </div>

                <div class="col-lg-2 col-md-4 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary me-2">
                        <i class="ri-filter-line me-1"></i> Apply
                    </button>
                    <a href="{{ route('admin.maintenance.index') }}" class="btn btn-outline-secondary">
                        <i class="ri-refresh-line"></i>
                    </a>
                </div>
            </div>
        </form>
    </div>
</div>

<div class="card bg-white border-0 rounded-3 mb-4">
    <div class="card-body p-4">
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-4">
            <h4 class="mb-0">All Maintenance Requests</h4>
            @can('create maintenance requests')
            <a href="{{ route('admin.maintenance.create') }}" class="btn btn-primary">
                <span class="material-symbols-outlined me-2" style="vertical-align: middle;">add</span>
                New Request
            </a>
            @endcan
        </div>

        <div class="default-table-area">
            <div class="table-responsive">
                <table class="table align-middle">
                    <thead>
                        <tr>
                            <th scope="col">#</th>
                            <th scope="col">Driver</th>
                            <th scope="col">Mechanic</th>
                            <th scope="col">Total Cost</th>
                            <th scope="col">Status</th>
                            <th scope="col">Date</th>
                            <th scope="col">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($requests as $request)
                        <tr>
                            <td>{{ ($requests->currentPage() - 1) * $requests->perPage() + $loop->iteration }}</td>
                            <td>
                                <div>
                                    <strong>{{ $request->driver->full_name }}</strong><br>
                                    <small class="text-muted">Wallet: ₦{{ number_format($request->driver->wallet->balance ?? 0, 2) }}</small>
                                </div>
                            </td>
                            <td>{{ $request->mechanic->name }}</td>
                            <td class="fw-semibold text-primary">₦{{ number_format($request->total_cost, 2) }}</td>
                            <td>
                                @if($request->status == 'pending_manager_approval')
                                <span class="badge bg-warning">Pending Manager</span>
                                @elseif($request->status == 'manager_denied')
                                <span class="badge bg-danger">Denied</span>
                                @elseif($request->status == 'pending_store_approval')
                                <span class="badge bg-info">Pending Store</span>
                                @else
                                <span class="badge bg-success">Completed</span>
                                @endif
                            </td>
                            <td>{{ $request->created_at->format('M d, Y H:i') }}</td>
                            <td>
                                <div class="d-flex gap-1">
                                    <a href="{{ route('admin.maintenance.show', $request) }}" class="btn btn-sm btn-primary" title="View">
                                        <i class="ri-eye-line"></i>
                                    </a>
                                    
                                    @if($request->status == 'pending_manager_approval')
                                    @can('approve maintenance requests')
                                    <form action="{{ route('admin.maintenance.approve', $request) }}" method="POST" class="d-inline">
                                        @csrf
                                        <button type="submit" class="btn btn-sm btn-success" title="Approve" onclick="return confirm('Approve this maintenance request?')">
                                            <i class="ri-check-line"></i>
                                        </button>
                                    </form>
                                    <form action="{{ route('admin.maintenance.deny', $request) }}" method="POST" class="d-inline">
                                        @csrf
                                        <button type="submit" class="btn btn-sm btn-danger" title="Deny" onclick="return confirm('Deny this maintenance request?')">
                                            <i class="ri-close-line"></i>
                                        </button>
                                    </form>
                                    @endcan
                                    @endif
                                    
                                    @if($request->status == 'pending_store_approval')
                                    @can('complete maintenance requests')
                                    <form action="{{ route('admin.maintenance.complete', $request) }}" method="POST" class="d-inline">
                                        @csrf
                                        <button type="submit" class="btn btn-sm btn-success" title="Complete" onclick="return confirm('Mark as complete and dispense parts?')">
                                            <i class="ri-check-double-line"></i> Complete
                                        </button>
                                    </form>
                                    @endcan
                                    @endif
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="7" class="text-center py-4">
                                <div class="text-muted">
                                    <span class="material-symbols-outlined fs-48">build</span>
                                    <p class="mt-2">No maintenance requests found</p>
                                </div>
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="mt-3">
                {{ $requests->links() }}
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="{{ asset('assets/js/data-table.js') }}"></script>
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
