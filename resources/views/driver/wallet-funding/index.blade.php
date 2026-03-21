@extends('layouts.app')

@section('title', 'My Wallet Funding Requests')

@section('content')
<div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-4">
    <h3 class="mb-0">My Wallet Funding Requests</h3>

    <div>
        <a href="{{ route('driver.wallet-funding.create') }}" class="btn btn-primary">
            <i class="ri-add-line me-2"></i> Request Wallet Funding
        </a>
    </div>
</div>

<!-- Current Wallet Balance -->
<div class="row mb-4">
    <div class="col-lg-12">
        <div class="card bg-gradient-primary border-0 rounded-3 text-white">
            <div class="card-body p-4">
                <div class="row align-items-center">
                    <div class="col-md-6">
                        <h6 class="text-white-50 mb-2">CURRENT WALLET BALANCE</h6>
                        <h2 class="mb-0 text-white">₦{{ number_format($driver->wallet->balance ?? 0, 2) }}</h2>
                    </div>
                    <div class="col-md-6 text-md-end">
                        <span class="material-symbols-outlined" style="font-size: 64px; opacity: 0.3;">account_balance_wallet</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Requests Table -->
<div class="card bg-white border-0 rounded-3">
    <div class="card-body p-4">
        <h5 class="mb-4">My Funding Requests</h5>

        <div class="table-responsive">
            <table class="table align-middle">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Amount</th>
                        <th>Status</th>
                        <th>Submitted</th>
                        <th>Processed</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($requests as $request)
                    <tr>
                        <td>{{ ($requests->currentPage() - 1) * $requests->perPage() + $loop->iteration }}</td>
                        <td class="fw-bold text-primary">₦{{ number_format($request->amount, 2) }}</td>
                        <td>
                            @if($request->status === 'pending')
                            <span class="badge bg-warning">
                                <i class="ri-time-line me-1"></i> Pending
                            </span>
                            @elseif($request->status === 'approved')
                            <span class="badge bg-success">
                                <i class="ri-check-line me-1"></i> Approved
                            </span>
                            @else
                            <span class="badge bg-danger">
                                <i class="ri-close-line me-1"></i> Rejected
                            </span>
                            @endif
                        </td>
                        <td>{{ $request->created_at->format('M d, Y') }}</td>
                        <td>
                            @if($request->approved_at)
                            {{ $request->approved_at->format('M d, Y') }}
                            @else
                            <span class="text-muted">—</span>
                            @endif
                        </td>
                        <td>
                            <a href="{{ route('driver.wallet-funding.show', $request) }}" class="btn btn-sm btn-primary">
                                <i class="ri-eye-line"></i> View
                            </a>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="text-center py-5">
                            <span class="material-symbols-outlined fs-48 d-block text-muted mb-2">receipt_long</span>
                            <p class="text-muted mb-3">You haven't submitted any funding requests yet</p>
                            <a href="{{ route('driver.wallet-funding.create') }}" class="btn btn-primary">
                                <i class="ri-add-line me-2"></i> Request Wallet Funding
                            </a>
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
@endsection
