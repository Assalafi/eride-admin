@extends('layouts.app')

@section('title', 'Part Details')

@section('content')
<div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-4">
    <h3 class="mb-0">Part Details</h3>

    <nav style="--bs-breadcrumb-divider: '>';" aria-label="breadcrumb">
        <ol class="breadcrumb align-items-center mb-0 lh-1">
            <li class="breadcrumb-item">
                <a href="{{ route('dashboard') }}" class="d-flex align-items-center text-decoration-none">
                    <i class="ri-home-4-line fs-18 text-primary me-1"></i>
                    <span class="text-secondary fw-medium hover">Dashboard</span>
                </a>
            </li>
            <li class="breadcrumb-item">
                <a href="{{ route('admin.parts.index') }}" class="text-decoration-none">Parts & Inventory</a>
            </li>
            <li class="breadcrumb-item active" aria-current="page">
                <span class="fw-medium">{{ $part->name }}</span>
            </li>
        </ol>
    </nav>
</div>

<div class="row">
    <!-- Left Column: Part Details -->
    <div class="col-lg-8">
        <!-- Part Information -->
        <div class="card bg-white border-0 rounded-3 mb-4">
            <div class="card-body p-4">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h4 class="mb-0">
                        <span class="material-symbols-outlined me-2" style="vertical-align: middle;">inventory_2</span>
                        Part Information
                    </h4>
                    <div class="d-flex gap-2">
                        @can('manage inventory')
                        <a href="{{ route('admin.parts.edit', $part) }}" class="btn btn-secondary btn-sm">
                            <i class="ri-edit-line me-1"></i> Edit
                        </a>
                        @endcan
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-4 text-center mb-4 mb-md-0">
                        @if($part->picture)
                        <img src="{{ asset('storage/' . $part->picture) }}" 
                             alt="{{ $part->name }}" 
                             class="img-fluid rounded-3 border" 
                             style="max-height: 250px; object-fit: cover;">
                        @else
                        <div class="bg-light rounded-3 d-flex align-items-center justify-content-center border" style="height: 250px;">
                            <span class="material-symbols-outlined text-muted" style="font-size: 100px;">image</span>
                        </div>
                        @endif
                    </div>

                    <div class="col-md-8">
                        <div class="mb-3">
                            <label class="text-secondary mb-1">Part Name</label>
                            <h5 class="mb-0">{{ $part->name }}</h5>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="text-secondary mb-1">SKU</label>
                                <p class="mb-0"><span class="badge bg-secondary fs-14">{{ $part->sku }}</span></p>
                            </div>
                            <div class="col-md-6">
                                <label class="text-secondary mb-1">Cost</label>
                                <p class="mb-0 fw-bold text-primary fs-18">₦{{ number_format($part->cost, 2) }}</p>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="text-secondary mb-1">Home Branch</label>
                            <p class="mb-0">
                                <span class="badge bg-info">{{ $part->branch->name }}</span>
                            </p>
                        </div>

                        @if($part->description)
                        <div class="mb-0">
                            <label class="text-secondary mb-1">Description</label>
                            <p class="mb-0">{{ $part->description }}</p>
                        </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        <!-- Stock Across Branches -->
        <div class="card bg-white border-0 rounded-3 mb-4">
            <div class="card-body p-4">
                <h5 class="mb-4">
                    <span class="material-symbols-outlined me-2" style="vertical-align: middle;">business</span>
                    Stock Across All Branches
                </h5>

                <div class="table-responsive">
                    <table class="table align-middle">
                        <thead>
                            <tr>
                                <th>Branch</th>
                                <th>Quantity</th>
                                <th>Status</th>
                                <th>Value</th>
                                @can('manage inventory')
                                <th>Actions</th>
                                @endcan
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($allStock as $stock)
                            <tr>
                                <td><strong>{{ $stock->branch->name }}</strong></td>
                                <td>
                                    <span class="badge {{ $stock->quantity >= 3 ? 'bg-success' : ($stock->quantity > 0 ? 'bg-warning' : 'bg-danger') }} fs-14">
                                        {{ $stock->quantity }} units
                                    </span>
                                </td>
                                <td>
                                    @if($stock->quantity >= 3)
                                    <span class="badge bg-success">In Stock</span>
                                    @elseif($stock->quantity > 0)
                                    <span class="badge bg-warning">Low Stock</span>
                                    @else
                                    <span class="badge bg-danger">Out of Stock</span>
                                    @endif
                                </td>
                                <td class="text-muted">₦{{ number_format($stock->quantity * $part->cost, 2) }}</td>
                                @can('manage inventory')
                                <td>
                                    <button type="button" class="btn btn-sm btn-success" data-bs-toggle="modal" data-bs-target="#stockInModal{{ $stock->branch_id }}">
                                        <i class="ri-add-line"></i> Stock In
                                    </button>
                                </td>
                                @endcan
                            </tr>

                            <!-- Stock In Modal for each branch -->
                            <div class="modal fade" id="stockInModal{{ $stock->branch_id }}" tabindex="-1">
                                <div class="modal-dialog">
                                    <div class="modal-content">
                                        <div class="modal-header">
                                            <h5 class="modal-title">Stock In: {{ $part->name }} ({{ $stock->branch->name }})</h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                        </div>
                                        <form action="{{ route('admin.parts.stock-in', $part) }}" method="POST">
                                            @csrf
                                            <input type="hidden" name="branch_id" value="{{ $stock->branch_id }}">
                                            <div class="modal-body">
                                                <div class="alert alert-info mb-3">
                                                    <i class="ri-information-line me-2"></i>
                                                    <strong>Branch:</strong> {{ $stock->branch->name }}
                                                </div>
                                                <p>Current Stock: <strong>{{ $stock->quantity }} units</strong></p>
                                                <div class="mb-3">
                                                    <label class="form-label fw-semibold">Quantity to Add <span class="text-danger">*</span></label>
                                                    <input type="number" class="form-control" name="quantity" min="1" required>
                                                </div>
                                            </div>
                                            <div class="modal-footer">
                                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                                <button type="submit" class="btn btn-success">Add to Stock</button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>
                            @empty
                            <tr>
                                <td colspan="5" class="text-center py-3 text-muted">
                                    No stock records found. Use "Stock In" to add inventory.
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                        <tfoot>
                            <tr class="table-light">
                                <td><strong>Total</strong></td>
                                <td><strong class="text-primary">{{ $totalStock }} units</strong></td>
                                <td colspan="3"><strong class="text-primary">₦{{ number_format($totalStock * $part->cost, 2) }}</strong></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>

        <!-- Maintenance History -->
        <div class="card bg-white border-0 rounded-3 mb-4">
            <div class="card-body p-4">
                <h5 class="mb-4">
                    <span class="material-symbols-outlined me-2" style="vertical-align: middle;">build</span>
                    Maintenance Usage History
                </h5>

                @if($maintenanceHistory->count() > 0)
                <div class="table-responsive">
                    <table class="table align-middle">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Driver</th>
                                <th>Vehicle</th>
                                <th>Quantity Used</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($maintenanceHistory as $maintenance)
                            @php
                                $usedQuantity = $maintenance->parts->where('id', $part->id)->first()->pivot->quantity ?? 0;
                            @endphp
                            <tr>
                                <td>{{ $maintenance->created_at->format('M d, Y') }}</td>
                                <td>{{ $maintenance->driver ? $maintenance->driver->full_name : 'N/A' }}</td>
                                <td>
                                    @if($maintenance->vehicle)
                                        <strong>{{ $maintenance->vehicle->plate_number }}</strong>
                                    @else
                                        <span class="text-muted">N/A</span>
                                    @endif
                                </td>
                                <td><span class="badge bg-warning">{{ $usedQuantity }} units</span></td>
                                <td>
                                    <a href="{{ route('admin.maintenance.show', $maintenance) }}" class="btn btn-sm btn-primary">
                                        <i class="ri-eye-line"></i>
                                    </a>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                @else
                <div class="text-center py-4 text-muted">
                    <span class="material-symbols-outlined fs-48">build</span>
                    <p class="mt-2">No maintenance history found for this part</p>
                </div>
                @endif
            </div>
        </div>

        <!-- Stock History -->
        <div class="card bg-white border-0 rounded-3 mb-4">
            <div class="card-body p-4">
                <h5 class="mb-4">
                    <span class="material-symbols-outlined me-2" style="vertical-align: middle;">history</span>
                    Stock Movement History
                </h5>

                @if($stockHistory->count() > 0)
                <div class="table-responsive">
                    <table class="table align-middle">
                        <thead>
                            <tr>
                                <th>Date & Time</th>
                                <th>Branch</th>
                                <th>Type</th>
                                <th>Quantity</th>
                                <th>Before</th>
                                <th>After</th>
                                <th>User</th>
                                <th>Notes</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($stockHistory as $history)
                            <tr>
                                <td>
                                    <small class="text-muted d-block">{{ $history->created_at->format('M d, Y') }}</small>
                                    <small>{{ $history->created_at->format('H:i:s') }}</small>
                                </td>
                                <td><strong>{{ $history->branch->name }}</strong></td>
                                <td>
                                    @if($history->type === 'in')
                                    <span class="badge bg-success">
                                        <i class="ri-add-line"></i> Stock In
                                    </span>
                                    @elseif($history->type === 'out')
                                    <span class="badge bg-danger">
                                        <i class="ri-subtract-line"></i> Stock Out
                                    </span>
                                    @else
                                    <span class="badge bg-warning">
                                        <i class="ri-edit-line"></i> Adjustment
                                    </span>
                                    @endif
                                </td>
                                <td>
                                    <strong class="{{ $history->type === 'in' ? 'text-success' : 'text-danger' }}">
                                        {{ $history->type === 'in' ? '+' : '-' }}{{ $history->quantity }} units
                                    </strong>
                                </td>
                                <td class="text-muted">{{ $history->quantity_before }}</td>
                                <td class="text-primary fw-bold">{{ $history->quantity_after }}</td>
                                <td>
                                    @if($history->user)
                                    <small>{{ $history->user->name }}</small>
                                    @else
                                    <small class="text-muted">System</small>
                                    @endif
                                </td>
                                <td>
                                    @if($history->notes)
                                    <small class="text-muted">{{ Str::limit($history->notes, 30) }}</small>
                                    @else
                                    <small class="text-muted">—</small>
                                    @endif
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="mt-3">
                    {{ $stockHistory->links() }}
                </div>
                @else
                <div class="text-center py-4 text-muted">
                    <span class="material-symbols-outlined fs-48">history</span>
                    <p class="mt-2">No stock movement history found for this part</p>
                </div>
                @endif
            </div>
        </div>
    </div>

    <!-- Right Column: Statistics & Quick Actions -->
    <div class="col-lg-4">
        <!-- Quick Statistics -->
        <div class="card bg-white border-0 rounded-3 mb-4">
            <div class="card-body p-4">
                <h5 class="mb-4">Quick Statistics</h5>

                <div class="mb-4">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <span class="text-secondary">Total Stock</span>
                        <span class="material-symbols-outlined text-primary">inventory</span>
                    </div>
                    <h3 class="mb-0 text-primary">{{ $totalStock }} units</h3>
                    <small class="text-muted">Across {{ $allStock->count() }} branch(es)</small>
                </div>

                <hr>

                <div class="mb-4">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <span class="text-secondary">Total Value</span>
                        <span class="material-symbols-outlined text-success">payments</span>
                    </div>
                    <h3 class="mb-0 text-success">₦{{ number_format($totalStock * $part->cost, 2) }}</h3>
                    <small class="text-muted">Current inventory value</small>
                </div>

                <hr>

                <div class="mb-4">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <span class="text-secondary">Total Used</span>
                        <span class="material-symbols-outlined text-info">build</span>
                    </div>
                    <h3 class="mb-0 text-info">{{ $totalUsed }} units</h3>
                    <small class="text-muted">In completed maintenance</small>
                </div>

                <hr>

                <div class="mb-0">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <span class="text-secondary">Unit Cost</span>
                        <span class="material-symbols-outlined text-warning">attach_money</span>
                    </div>
                    <h3 class="mb-0 text-warning">₦{{ number_format($part->cost, 2) }}</h3>
                    <small class="text-muted">Per unit</small>
                </div>
            </div>
        </div>

        <!-- Quick Actions -->
        @can('manage inventory')
        <div class="card bg-white border-0 rounded-3 mb-4">
            <div class="card-body p-4">
                <h5 class="mb-3">Quick Actions</h5>

                <div class="d-grid gap-2">
                    <a href="{{ route('admin.parts.edit', $part) }}" class="btn btn-outline-secondary">
                        <i class="ri-edit-line me-2"></i> Edit Part Details
                    </a>
                    
                    <button type="button" class="btn btn-outline-success" data-bs-toggle="modal" data-bs-target="#addStockModal">
                        <i class="ri-add-line me-2"></i> Add Stock
                    </button>

                    <a href="{{ route('admin.parts.index') }}" class="btn btn-outline-primary">
                        <i class="ri-arrow-left-line me-2"></i> Back to List
                    </a>
                </div>
            </div>
        </div>
        @endcan

        <!-- Part Information Card -->
        <div class="card bg-white border-0 rounded-3 mb-4">
            <div class="card-body p-4">
                <h5 class="mb-3">Part Information</h5>

                <div class="mb-3">
                    <small class="text-secondary d-block mb-1">SKU</small>
                    <code>{{ $part->sku }}</code>
                </div>

                <div class="mb-3">
                    <small class="text-secondary d-block mb-1">Created</small>
                    <span>{{ $part->created_at->format('M d, Y') }}</span>
                </div>

                <div class="mb-0">
                    <small class="text-secondary d-block mb-1">Last Updated</small>
                    <span>{{ $part->updated_at->format('M d, Y H:i') }}</span>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Add Stock Modal (Super Admin can select branch) -->
<div class="modal fade" id="addStockModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Add Stock: {{ $part->name }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form action="{{ route('admin.parts.stock-in', $part) }}" method="POST">
                @csrf
                <div class="modal-body">
                    @if(auth()->user()->hasRole('Super Admin'))
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Select Branch <span class="text-danger">*</span></label>
                        <select class="form-select" name="branch_id" required>
                            <option value="">Choose branch...</option>
                            @foreach(\App\Models\Branch::all() as $branch)
                            <option value="{{ $branch->id }}">
                                {{ $branch->name }}
                                @php
                                    $branchStock = $allStock->where('branch_id', $branch->id)->first();
                                    $qty = $branchStock ? $branchStock->quantity : 0;
                                @endphp
                                (Current: {{ $qty }} units)
                            </option>
                            @endforeach
                        </select>
                    </div>
                    @else
                    <div class="alert alert-info mb-3">
                        <strong>Branch:</strong> {{ auth()->user()->branch->name }}
                    </div>
                    @endif

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Quantity to Add <span class="text-danger">*</span></label>
                        <input type="number" class="form-control" name="quantity" min="1" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success">Add to Stock</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
