@extends('layouts.app')

@section('title', 'Debit Requests')

@section('content')
<div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-4">
    <h3 class="mb-0">Debit Requests</h3>

    <nav style="--bs-breadcrumb-divider: '>';" aria-label="breadcrumb">
        <ol class="breadcrumb align-items-center mb-0 lh-1">
            <li class="breadcrumb-item">
                <a href="{{ route('dashboard') }}" class="d-flex align-items-center text-decoration-none">
                    <i class="ri-home-4-line fs-18 text-primary me-1"></i>
                    <span class="text-secondary fw-medium hover">Dashboard</span>
                </a>
            </li>
            <li class="breadcrumb-item">
                <a href="{{ route('accounts.index') }}" class="text-decoration-none">Account Management</a>
            </li>
            <li class="breadcrumb-item active" aria-current="page">
                <span class="fw-medium">Debit Requests</span>
            </li>
        </ol>
    </nav>
</div>

<div class="row">
    <div class="col-lg-12">
        <div class="card bg-white border-0 rounded-3 mb-4">
            <div class="card-body p-4">
                <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-4">
                    <h3 class="mb-0">All Debit Requests</h3>
                    @can('create debit request')
                    <a href="{{ route('accounts.debit-requests.create') }}" class="btn btn-primary btn-sm">
                        <span class="material-symbols-outlined me-1" style="vertical-align: middle; font-size: 18px;">add</span>
                        New Request
                    </a>
                    @endcan
                </div>

                <!-- Filters -->
                <form method="GET" action="{{ route('accounts.debit-requests.index') }}" class="row g-3 mb-4">
                    <!-- Status Filter -->
                    <div class="col-md-3">
                        <label class="form-label fw-semibold">Status</label>
                        <select name="status" class="form-select" onchange="this.form.submit()">
                            <option value="">All Statuses</option>
                            <option value="pending" {{ request('status') == 'pending' ? 'selected' : '' }}>
                                Pending
                            </option>
                            <option value="approved" {{ request('status') == 'approved' ? 'selected' : '' }}>
                                Approved
                            </option>
                            <option value="rejected" {{ request('status') == 'rejected' ? 'selected' : '' }}>
                                Rejected
                            </option>
                        </select>
                    </div>

                    @can('approve debit requests')
                    <!-- Branch Filter (for admins) -->
                    <div class="col-md-3">
                        <label class="form-label fw-semibold">Branch</label>
                        <select name="branch_id" class="form-select" onchange="this.form.submit()">
                            <option value="">All Branches</option>
                            @foreach(\App\Models\Branch::all() as $branch)
                            <option value="{{ $branch->id }}" {{ request('branch_id') == $branch->id ? 'selected' : '' }}>
                                {{ $branch->name }}
                            </option>
                            @endforeach
                        </select>
                    </div>
                    @endcan

                    <!-- Clear Filters -->
                    <div class="col-md-3 d-flex align-items-end">
                        <a href="{{ route('accounts.debit-requests.index') }}" class="btn btn-outline-secondary w-100">
                            <span class="material-symbols-outlined me-1" style="vertical-align: middle; font-size: 18px;">refresh</span>
                            Clear Filters
                        </a>
                    </div>
                </form>

                <div class="default-table-area all-projects">
                    <div class="table-responsive">
                        <table class="table align-middle">
                            <thead>
                                <tr>
                                    <th scope="col">ID</th>
                                    <th scope="col">Date</th>
                                    <th scope="col">Branch</th>
                                    <th scope="col">Requested By</th>
                                    <th scope="col">Amount</th>
                                    <th scope="col">Description</th>
                                    <th scope="col">Status</th>
                                    <th scope="col" class="text-end">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($requests as $request)
                                <tr>
                                    <td>
                                        <span class="badge bg-secondary">
                                            #{{ $request->id }}
                                        </span>
                                    </td>
                                    <td class="text-body">{{ $request->created_at->format('M d, Y') }}</td>
                                    <td>{{ $request->branch->name }}</td>
                                    <td class="text-body">{{ $request->requester->name }}</td>
                                    <td>
                                        <span class="fw-semibold text-dark">
                                            ₦{{ number_format($request->amount, 2) }}
                                        </span>
                                        @if($request->amount >= $threshold)
                                        <span class="material-symbols-outlined text-warning ms-1" 
                                           title="Requires approval (above threshold)" style="font-size: 18px; vertical-align: middle;">warning</span>
                                        @endif
                                    </td>
                                    <td class="text-secondary">
                                        {{ Str::limit($request->description, 40) }}
                                    </td>
                                    <td>
                                        @if($request->status === 'pending')
                                        <span class="badge bg-warning">Pending</span>
                                        @elseif($request->status === 'approved')
                                        <span class="badge bg-success">Approved</span>
                                        @else
                                        <span class="badge bg-danger">Rejected</span>
                                        @endif
                                    </td>
                                    <td class="text-end">
                                        <a href="{{ route('accounts.debit-requests.show', $request) }}" 
                                           class="btn btn-outline-primary btn-sm">
                                            <span class="material-symbols-outlined" style="font-size: 18px; vertical-align: middle;">visibility</span>
                                        </a>
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="8" class="text-center py-5 text-muted">
                                        <span class="material-symbols-outlined d-block mb-2" style="font-size: 48px;">description</span>
                                        No debit requests found
                                    </td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Pagination -->
                @if($requests->hasPages())
                <div class="mt-4">
                    {{ $requests->links() }}
                </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection
