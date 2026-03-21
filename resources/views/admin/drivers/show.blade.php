@extends('layouts.app')

@section('title', 'Driver Details')

@section('content')
<div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-4">
    <h3 class="mb-0">Driver Details</h3>

    <nav style="--bs-breadcrumb-divider: '>';" aria-label="breadcrumb">
        <ol class="breadcrumb align-items-center mb-0 lh-1">
            <li class="breadcrumb-item">
                <a href="{{ route('dashboard') }}" class="d-flex align-items-center text-decoration-none">
                    <i class="ri-home-4-line fs-18 text-primary me-1"></i>
                    <span class="text-secondary fw-medium hover">Dashboard</span>
                </a>
            </li>
            <li class="breadcrumb-item">
                <a href="{{ route('admin.drivers.index') }}" class="text-decoration-none">Drivers</a>
            </li>
            <li class="breadcrumb-item active" aria-current="page">
                <span class="fw-medium">{{ $driver->full_name }}</span>
            </li>
        </ol>
    </nav>
</div>

<div class="row">
    <div class="col-lg-4">
        <div class="card bg-white border-0 rounded-3 mb-4">
            <div class="card-body p-4 text-center">
                <div class="rounded-circle wh-100 bg-primary-div d-flex align-items-center justify-content-center text-white fw-bold fs-32 mx-auto mb-3">
                    {{ strtoupper(substr($driver->first_name, 0, 1)) }}{{ strtoupper(substr($driver->last_name, 0, 1)) }}
                </div>
                <h4 class="mb-1">{{ $driver->full_name }}</h4>
                <p class="text-muted mb-3">Driver</p>
                
                <div class="d-flex gap-2 justify-content-center">
                    @can('edit drivers')
                    <a href="{{ route('admin.drivers.edit', $driver) }}" class="btn btn-primary btn-sm">
                        <i class="ri-edit-line"></i> Edit
                    </a>
                    @endcan
                    <a href="{{ route('admin.drivers.index') }}" class="btn btn-secondary btn-sm">
                        <i class="ri-arrow-left-line"></i> Back
                    </a>
                </div>
            </div>
        </div>

        <div class="card bg-white border-0 rounded-3 mb-4">
            <div class="card-body p-4">
                <h5 class="mb-3">Contact Information</h5>
                <div class="mb-3">
                    <p class="text-secondary mb-1">Email</p>
                    <p class="fw-semibold">{{ $driver->user->email }}</p>
                </div>
                <div class="mb-3">
                    <p class="text-secondary mb-1">Phone</p>
                    <p class="fw-semibold">{{ $driver->phone_number }}</p>
                </div>
                <div class="mb-0">
                    <p class="text-secondary mb-1">Branch</p>
                    <p class="fw-semibold">{{ $driver->branch->name }}</p>
                </div>
            </div>
        </div>

        <div class="card bg-white border-0 rounded-3 mb-4">
            <div class="card-body p-4">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h5 class="mb-0">Wallet</h5>
                    <button type="button" class="btn btn-success btn-sm" data-bs-toggle="modal" data-bs-target="#fundWalletModal">
                        <span class="material-symbols-outlined me-1" style="vertical-align: middle; font-size: 18px;">add_circle</span>
                        Fund Wallet
                    </button>
                </div>
                <div class="text-center py-3">
                    <p class="text-secondary mb-2">Current Balance</p>
                    <h2 class="text-success mb-0">₦{{ number_format($driver->wallet->balance ?? 0, 2) }}</h2>
                </div>
            </div>
        </div>
    </div>

    <div class="col-lg-8">
        <div class="card bg-white border-0 rounded-3 mb-4">
            <div class="card-body p-4">
                <h5 class="mb-4">Vehicle Assignments</h5>
                <div class="table-responsive">
                    <table class="table align-middle">
                        <thead>
                            <tr>
                                <th>Vehicle</th>
                                <th>Assigned Date</th>
                                <th>Returned Date</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($driver->vehicleAssignments as $assignment)
                            <tr>
                                <td>
                                    <strong>{{ $assignment->vehicle->plate_number }}</strong><br>
                                    <small class="text-muted">{{ $assignment->vehicle->make }} {{ $assignment->vehicle->model }}</small>
                                </td>
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
                                <td colspan="4" class="text-center text-muted py-4">No assignments yet</td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="card bg-white border-0 rounded-3 mb-4">
            <div class="card-body p-4">
                <h5 class="mb-4">Recent Transactions</h5>
                <div class="table-responsive">
                    <table class="table align-middle">
                        <thead>
                            <tr>
                                <th>Type</th>
                                <th>Amount</th>
                                <th>Status</th>
                                <th>Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($driver->transactions()->latest()->limit(10)->get() as $transaction)
                            <tr>
                                <td>
                                    <span class="badge bg-secondary">
                                        {{ str_replace('_', ' ', ucwords($transaction->type)) }}
                                    </span>
                                </td>
                                <td>₦{{ number_format($transaction->amount, 2) }}</td>
                                <td>
                                    @if($transaction->status == 'successful')
                                    <span class="badge bg-success">Success</span>
                                    @elseif($transaction->status == 'pending')
                                    <span class="badge bg-warning">Pending</span>
                                    @else
                                    <span class="badge bg-danger">Rejected</span>
                                    @endif
                                </td>
                                <td>{{ $transaction->created_at->format('M d, Y H:i') }}</td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="4" class="text-center text-muted py-4">No transactions yet</td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="card bg-white border-0 rounded-3 mb-4">
            <div class="card-body p-4">
                <h5 class="mb-4">Daily Ledgers (Last 10 Days)</h5>
                <div class="table-responsive">
                    <table class="table align-middle">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Required</th>
                                <th>Paid</th>
                                <th>Balance</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($driver->dailyLedgers()->latest('date')->limit(10)->get() as $ledger)
                            <tr>
                                <td>{{ $ledger->date->format('M d, Y') }}</td>
                                <td>₦{{ number_format($ledger->required_payment, 2) }}</td>
                                <td>₦{{ number_format($ledger->amount_paid, 2) }}</td>
                                <td>₦{{ number_format($ledger->balance, 2) }}</td>
                                <td>
                                    @if($ledger->status == 'paid')
                                    <span class="badge bg-success">Paid</span>
                                    @elseif($ledger->status == 'partially_paid')
                                    <span class="badge bg-warning">Partial</span>
                                    @else
                                    <span class="badge bg-danger">Due</span>
                                    @endif
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="5" class="text-center text-muted py-4">No ledger entries yet</td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Fund Wallet Modal -->
<div class="modal fade" id="fundWalletModal" tabindex="-1" aria-labelledby="fundWalletModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="{{ route('admin.drivers.fund-wallet', $driver) }}" method="POST">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title" id="fundWalletModalLabel">
                        <span class="material-symbols-outlined me-2" style="vertical-align: middle;">account_balance_wallet</span>
                        Fund Driver Wallet
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-info">
                        <i class="ri-information-line me-2"></i>
                        <strong>Current Balance:</strong> ₦{{ number_format($driver->wallet->balance ?? 0, 2) }}
                    </div>

                    <div class="mb-3">
                        <label for="amount" class="form-label fw-semibold">Amount (₦) <span class="text-danger">*</span></label>
                        <input type="number" 
                               class="form-control @error('amount') is-invalid @enderror" 
                               id="amount" 
                               name="amount" 
                               step="0.01" 
                               min="0.01"
                               placeholder="0.00"
                               required
                               autofocus>
                        @error('amount')
                        <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                        <small class="text-muted">Enter the amount you want to add to the driver's wallet</small>
                    </div>

                    <div class="mb-3">
                        <label for="description" class="form-label fw-semibold">Description (Optional)</label>
                        <textarea class="form-control @error('description') is-invalid @enderror" 
                                  id="description" 
                                  name="description" 
                                  rows="3"
                                  placeholder="e.g., Bonus payment, Initial funding, etc."></textarea>
                        @error('description')
                        <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="alert alert-warning mb-0">
                        <i class="ri-alert-line me-2"></i>
                        This action will immediately credit the driver's wallet and create a transaction record.
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <span class="material-symbols-outlined me-1" style="vertical-align: middle; font-size: 18px;">close</span>
                        Cancel
                    </button>
                    <button type="submit" class="btn btn-success">
                        <span class="material-symbols-outlined me-1" style="vertical-align: middle; font-size: 18px;">add_circle</span>
                        Fund Wallet
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
