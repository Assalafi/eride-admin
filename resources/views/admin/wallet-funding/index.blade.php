@extends('layouts.app')

@section('title', 'Wallet Funding Requests')

@section('content')
<div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-4">
    <h3 class="mb-0">Wallet Funding Requests</h3>

    <nav style="--bs-breadcrumb-divider: '>';" aria-label="breadcrumb">
        <ol class="breadcrumb align-items-center mb-0 lh-1">
            <li class="breadcrumb-item">
                <a href="{{ route('dashboard') }}" class="d-flex align-items-center text-decoration-none">
                    <i class="ri-home-4-line fs-18 text-primary me-1"></i>
                    <span class="text-secondary fw-medium hover">Dashboard</span>
                </a>
            </li>
            <li class="breadcrumb-item active" aria-current="page">
                <span class="fw-medium">Wallet Funding</span>
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
                        <span class="text-secondary d-block mb-1">Pending Requests</span>
                        <h3 class="mb-0 text-warning">{{ $stats['pending'] }}</h3>
                    </div>
                    <div class="rounded-circle wh-60 bg-warning-div d-flex align-items-center justify-content-center">
                        <span class="material-symbols-outlined text-warning fs-32">pending</span>
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
                        <span class="text-secondary d-block mb-1">Approved</span>
                        <h3 class="mb-0 text-success">{{ $stats['approved'] }}</h3>
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
                        <span class="text-secondary d-block mb-1">Rejected</span>
                        <h3 class="mb-0 text-danger">{{ $stats['rejected'] }}</h3>
                    </div>
                    <div class="rounded-circle wh-60 bg-danger-div d-flex align-items-center justify-content-center">
                        <span class="material-symbols-outlined text-danger fs-32">cancel</span>
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
                        <span class="text-secondary d-block mb-1">Pending Amount</span>
                        <h3 class="mb-0 text-info">₦{{ number_format($stats['total_amount_pending'], 2) }}</h3>
                    </div>
                    <div class="rounded-circle wh-60 bg-info-div d-flex align-items-center justify-content-center">
                        <span class="material-symbols-outlined text-info fs-32">account_balance_wallet</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Filters -->
<div class="card bg-white border-0 rounded-3 mb-4">
    <div class="card-body p-4">
        <form method="GET" action="{{ route('admin.wallet-funding.index') }}" id="filterForm">
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
                        <option value="approved" {{ $status == 'approved' ? 'selected' : '' }}>Approved</option>
                        <option value="rejected" {{ $status == 'rejected' ? 'selected' : '' }}>Rejected</option>
                    </select>
                </div>

                <div class="col-lg-2 col-md-4 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary me-2">
                        <i class="ri-filter-line me-1"></i> Apply
                    </button>
                    <a href="{{ route('admin.wallet-funding.index') }}" class="btn btn-outline-secondary">
                        <i class="ri-refresh-line"></i>
                    </a>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Requests Table -->
<div class="card bg-white border-0 rounded-3">
    <div class="card-body p-4">
        <div class="default-table-area">
            <div class="table-responsive">
                <table class="table align-middle">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Driver</th>
                            <th>Branch</th>
                            <th>Amount</th>
                            <th>Status</th>
                            <th>Date Submitted</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($requests as $request)
                        <tr>
                            <td>{{ ($requests->currentPage() - 1) * $requests->perPage() + $loop->iteration }}</td>
                            <td><strong>{{ $request->driver->full_name }}</strong></td>
                            <td>{{ $request->driver->branch->name }}</td>
                            <td class="fw-bold text-primary">₦{{ number_format($request->amount, 2) }}</td>
                            <td>
                                @if($request->status === 'pending')
                                <span class="badge bg-warning">Pending</span>
                                @elseif($request->status === 'approved')
                                <span class="badge bg-success">Approved</span>
                                @else
                                <span class="badge bg-danger">Rejected</span>
                                @endif
                            </td>
                            <td>{{ $request->created_at->format('M d, Y H:i') }}</td>
                            <td>
                                <div class="d-flex gap-1">
                                    <a href="{{ route('admin.wallet-funding.show', $request) }}" class="btn btn-sm btn-primary" title="View Details">
                                        <i class="ri-eye-line"></i>
                                    </a>
                                    
                                    @if($request->isPending())
                                    <button type="button" class="btn btn-sm btn-success" data-bs-toggle="modal" data-bs-target="#approveModal{{ $request->id }}" title="Approve">
                                        <i class="ri-check-line"></i>
                                    </button>
                                    <button type="button" class="btn btn-sm btn-danger" data-bs-toggle="modal" data-bs-target="#rejectModal{{ $request->id }}" title="Reject">
                                        <i class="ri-close-line"></i>
                                    </button>
                                    @endif
                                </div>
                            </td>
                        </tr>

                        <!-- Approve Modal -->
                        <div class="modal fade" id="approveModal{{ $request->id }}" tabindex="-1">
                            <div class="modal-dialog">
                                <div class="modal-content">
                                    <div class="modal-header">
                                        <h5 class="modal-title">Approve Wallet Funding</h5>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                    </div>
                                    <form action="{{ route('admin.wallet-funding.approve', $request) }}" method="POST">
                                        @csrf
                                        <div class="modal-body">
                                            <p>Are you sure you want to approve this funding request?</p>
                                            <div class="alert alert-info">
                                                <strong>Driver:</strong> {{ $request->driver->full_name }}<br>
                                                <strong>Amount:</strong> ₦{{ number_format($request->amount, 2) }}<br>
                                                <strong>Current Wallet Balance:</strong> ₦{{ number_format($request->driver->wallet->balance ?? 0, 2) }}
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label">Admin Notes (Optional)</label>
                                                <textarea class="form-control" name="admin_notes" rows="3" placeholder="Add any notes..."></textarea>
                                            </div>
                                        </div>
                                        <div class="modal-footer">
                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                            <button type="submit" class="btn btn-success">Approve & Fund Wallet</button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>

                        <!-- Reject Modal -->
                        <div class="modal fade" id="rejectModal{{ $request->id }}" tabindex="-1">
                            <div class="modal-dialog">
                                <div class="modal-content">
                                    <div class="modal-header">
                                        <h5 class="modal-title">Reject Wallet Funding</h5>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                    </div>
                                    <form action="{{ route('admin.wallet-funding.reject', $request) }}" method="POST">
                                        @csrf
                                        <div class="modal-body">
                                            <p>Are you sure you want to reject this funding request?</p>
                                            <div class="alert alert-warning">
                                                <strong>Driver:</strong> {{ $request->driver->full_name }}<br>
                                                <strong>Amount:</strong> ₦{{ number_format($request->amount, 2) }}
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label">Reason for Rejection <span class="text-danger">*</span></label>
                                                <textarea class="form-control" name="admin_notes" rows="3" placeholder="Explain why this request is being rejected..." required></textarea>
                                            </div>
                                        </div>
                                        <div class="modal-footer">
                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                            <button type="submit" class="btn btn-danger">Reject Request</button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                        @empty
                        <tr>
                            <td colspan="7" class="text-center py-4 text-muted">
                                <span class="material-symbols-outlined fs-48 d-block">account_balance_wallet</span>
                                <p class="mt-2">No wallet funding requests found</p>
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($requests->hasPages())
            <div class="mt-3">
                {{ $requests->links() }}
            </div>
            @endif
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
