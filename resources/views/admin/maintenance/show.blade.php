@extends('layouts.app')

@section('title', 'Maintenance Request Details')

@section('content')
<div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-4">
    <h3 class="mb-0">Maintenance Request Details</h3>

    <nav style="--bs-breadcrumb-divider: '>';" aria-label="breadcrumb">
        <ol class="breadcrumb align-items-center mb-0 lh-1">
            <li class="breadcrumb-item">
                <a href="{{ route('dashboard') }}" class="d-flex align-items-center text-decoration-none">
                    <i class="ri-home-4-line fs-18 text-primary me-1"></i>
                    <span class="text-secondary fw-medium hover">Dashboard</span>
                </a>
            </li>
            <li class="breadcrumb-item">
                <a href="{{ route('admin.maintenance.index') }}" class="text-decoration-none">Maintenance</a>
            </li>
            <li class="breadcrumb-item active" aria-current="page">
                <span class="fw-medium">Details</span>
            </li>
        </ol>
    </nav>
</div>

<div class="row">
    <div class="col-lg-4">
        <div class="card bg-white border-0 rounded-3 mb-4">
            <div class="card-body p-4">
                <h5 class="mb-3">Request Status</h5>
                <div class="text-center py-3">
                    @if($maintenanceRequest->status == 'pending_manager_approval')
                    <span class="badge bg-warning fs-16 mb-2">Pending Manager Approval</span>
                    @elseif($maintenanceRequest->status == 'manager_denied')
                    <span class="badge bg-danger fs-16 mb-2">Denied by Manager</span>
                    @elseif($maintenanceRequest->status == 'pending_store_approval')
                    <span class="badge bg-info fs-16 mb-2">Pending Store Approval</span>
                    @else
                    <span class="badge bg-success fs-16 mb-2">Completed</span>
                    @endif
                </div>
            </div>
        </div>

        <div class="card bg-white border-0 rounded-3 mb-4">
            <div class="card-body p-4">
                <h5 class="mb-3">Cost Information</h5>
                <div class="mb-3">
                    <p class="text-secondary mb-1">Total Cost</p>
                    <h4 class="text-primary mb-0">₦{{ number_format($maintenanceRequest->total_cost, 2) }}</h4>
                </div>
                <div class="mb-3">
                    <p class="text-secondary mb-1">Driver Wallet Balance</p>
                    <h5 class="text-success mb-0">₦{{ number_format($maintenanceRequest->driver->wallet->balance ?? 0, 2) }}</h5>
                </div>
                @if($maintenanceRequest->driver->wallet && $maintenanceRequest->driver->wallet->balance < $maintenanceRequest->total_cost)
                <div class="alert alert-danger">
                    <i class="ri-error-warning-line"></i> Insufficient wallet balance
                </div>
                @endif
            </div>
        </div>

        <div class="card bg-white border-0 rounded-3 mb-4">
            <div class="card-body p-4">
                <h5 class="mb-3">Actions</h5>
                
                @if($maintenanceRequest->status == 'pending_manager_approval')
                @can('approve maintenance requests')
                <form action="{{ route('admin.maintenance.approve', $maintenanceRequest) }}" method="POST" class="mb-2">
                    @csrf
                    <button type="submit" class="btn btn-success w-100" onclick="return confirm('Approve this maintenance request?')">
                        <i class="ri-check-line"></i> Approve Request
                    </button>
                </form>
                <form action="{{ route('admin.maintenance.deny', $maintenanceRequest) }}" method="POST">
                    @csrf
                    <button type="submit" class="btn btn-danger w-100" onclick="return confirm('Deny this maintenance request?')">
                        <i class="ri-close-line"></i> Deny Request
                    </button>
                </form>
                @endcan
                @endif
                
                @if($maintenanceRequest->status == 'pending_store_approval')
                @can('complete maintenance requests')
                <form action="{{ route('admin.maintenance.complete', $maintenanceRequest) }}" method="POST">
                    @csrf
                    <button type="submit" class="btn btn-success w-100" onclick="return confirm('Mark as complete and dispense parts?')">
                        <i class="ri-check-double-line"></i> Complete & Dispense
                    </button>
                </form>
                @endcan
                @endif
                
                <a href="{{ route('admin.maintenance.index') }}" class="btn btn-secondary w-100 mt-2">
                    <i class="ri-arrow-left-line"></i> Back to List
                </a>
            </div>
        </div>
    </div>

    <div class="col-lg-8">
        <div class="card bg-white border-0 rounded-3 mb-4">
            <div class="card-body p-4">
                <h5 class="mb-4">Request Information</h5>
                
                <div class="row mb-3">
                    <div class="col-md-6">
                        <p class="text-secondary mb-1">Driver</p>
                        <p class="fw-semibold">{{ $maintenanceRequest->driver->full_name }}</p>
                    </div>
                    <div class="col-md-6">
                        <p class="text-secondary mb-1">Phone</p>
                        <p class="fw-semibold">{{ $maintenanceRequest->driver->phone_number }}</p>
                    </div>
                </div>

                <div class="row mb-3">
                    <div class="col-md-6">
                        <p class="text-secondary mb-1">Mechanic</p>
                        <p class="fw-semibold">{{ $maintenanceRequest->mechanic->name }}</p>
                    </div>
                    <div class="col-md-6">
                        <p class="text-secondary mb-1">Request Date</p>
                        <p class="fw-semibold">{{ $maintenanceRequest->created_at->format('M d, Y H:i') }}</p>
                    </div>
                </div>

                @if($maintenanceRequest->approved_by_id)
                <div class="row mb-3">
                    <div class="col-md-6">
                        <p class="text-secondary mb-1">Approved By</p>
                        <p class="fw-semibold">{{ $maintenanceRequest->approver->name }}</p>
                    </div>
                    <div class="col-md-6">
                        <p class="text-secondary mb-1">Approved At</p>
                        <p class="fw-semibold">{{ $maintenanceRequest->updated_at->format('M d, Y H:i') }}</p>
                    </div>
                </div>
                @endif
            </div>
        </div>

        <div class="card bg-white border-0 rounded-3 mb-4">
            <div class="card-body p-4">
                <h5 class="mb-4">Requested Parts</h5>
                
                <div class="table-responsive">
                    <table class="table align-middle">
                        <thead>
                            <tr>
                                <th>Part Name</th>
                                <th>SKU</th>
                                <th>Unit Cost</th>
                                <th>Quantity</th>
                                <th>Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($maintenanceRequest->parts as $part)
                            <tr>
                                <td>{{ $part->name }}</td>
                                <td><span class="badge bg-secondary">{{ $part->sku }}</span></td>
                                <td>₦{{ number_format($part->cost, 2) }}</td>
                                <td>{{ $part->pivot->quantity }}</td>
                                <td class="fw-semibold">₦{{ number_format($part->cost * $part->pivot->quantity, 2) }}</td>
                            </tr>
                            @endforeach
                        </tbody>
                        <tfoot>
                            <tr class="table-light">
                                <td colspan="4" class="text-end fw-bold">Total Cost:</td>
                                <td class="fw-bold text-primary">₦{{ number_format($maintenanceRequest->total_cost, 2) }}</td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
