@extends('layouts.app')

@section('title', 'Parts & Inventory')

@push('styles')
<link rel="stylesheet" href="{{ asset('assets/css/data-table.css') }}">
@endpush

@section('content')
<div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-4">
    <h3 class="mb-0">Parts & Inventory Management</h3>

    <nav style="--bs-breadcrumb-divider: '>';" aria-label="breadcrumb">
        <ol class="breadcrumb align-items-center mb-0 lh-1">
            <li class="breadcrumb-item">
                <a href="{{ route('dashboard') }}" class="d-flex align-items-center text-decoration-none">
                    <i class="ri-home-4-line fs-18 text-primary me-1"></i>
                    <span class="text-secondary fw-medium hover">Dashboard</span>
                </a>
            </li>
            <li class="breadcrumb-item active" aria-current="page">
                <span class="fw-medium">Parts & Inventory</span>
            </li>
        </ol>
    </nav>
</div>

<div class="row mb-4">
    <div class="col-xxl-3 col-sm-6">
        <div class="card bg-white border-0 rounded-3 mb-4">
            <div class="card-body p-4">
                <div class="d-flex align-items-center justify-content-between">
                    <div class="flex-grow-1">
                        <span class="d-block fw-normal text-secondary mb-1">Total Parts</span>
                        <h3 class="mb-0 fs-20 text-primary">{{ $totalParts }}</h3>
                    </div>
                    <div class="flex-shrink-0">
                        <div class="d-flex align-items-center justify-content-center wh-77 rounded-circle" style="background-color: rgba(116, 66, 162, 0.1);">
                            <span class="material-symbols-outlined fs-32 text-primary">inventory_2</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-xxl-3 col-sm-6">
        <div class="card bg-white border-0 rounded-3 mb-4">
            <div class="card-body p-4">
                <div class="d-flex align-items-center justify-content-between">
                    <div class="flex-grow-1">
                        <span class="d-block fw-normal text-secondary mb-1">In Stock</span>
                        <h3 class="mb-0 fs-20 text-success">{{ $inStockParts }}</h3>
                    </div>
                    <div class="flex-shrink-0">
                        <div class="d-flex align-items-center justify-content-center wh-77 rounded-circle" style="background-color: rgba(76, 175, 80, 0.1);">
                            <span class="material-symbols-outlined fs-32 text-success">check_circle</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-xxl-3 col-sm-6">
        <div class="card bg-white border-0 rounded-3 mb-4">
            <div class="card-body p-4">
                <div class="d-flex align-items-center justify-content-between">
                    <div class="flex-grow-1">
                        <span class="d-block fw-normal text-secondary mb-1">Low Stock</span>
                        <h3 class="mb-0 fs-20 text-warning">{{ $lowStockParts }}</h3>
                    </div>
                    <div class="flex-shrink-0">
                        <div class="d-flex align-items-center justify-content-center wh-77 rounded-circle" style="background-color: rgba(255, 193, 7, 0.1);">
                            <span class="material-symbols-outlined fs-32 text-warning">error</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-xxl-3 col-sm-6">
        <div class="card bg-white border-0 rounded-3 mb-4">
            <div class="card-body p-4">
                <div class="d-flex align-items-center justify-content-between">
                    <div class="flex-grow-1">
                        <span class="d-block fw-normal text-secondary mb-1">Out of Stock</span>
                        <h3 class="mb-0 fs-20 text-danger">{{ $outOfStockParts }}</h3>
                    </div>
                    <div class="flex-shrink-0">
                        <div class="d-flex align-items-center justify-content-center wh-77 rounded-circle" style="background-color: rgba(220, 53, 69, 0.1);">
                            <span class="material-symbols-outlined fs-32 text-danger">cancel</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="card bg-white border-0 rounded-3 mb-4">
    <div class="card-body p-4">
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-4">
            <h4 class="mb-0">All Parts</h4>
            <div class="d-flex gap-2">
                @can('manage inventory')
                <a href="{{ route('admin.parts.create') }}" class="btn btn-primary">
                    <span class="material-symbols-outlined me-2" style="vertical-align: middle;">add</span>
                    Add New Part
                </a>
                @endcan
            </div>
        </div>

        <div class="default-table-area">
            <div class="table-responsive">
                <table class="table align-middle" id="partsTable">
                    <thead>
                        <tr>
                            <th scope="col">#</th>
                            <th scope="col">Picture</th>
                            <th scope="col">Part Name</th>
                            <th scope="col">SKU</th>
                            <th scope="col">Branch</th>
                            <th scope="col">Cost</th>
                            <th scope="col">Stock (Branch)</th>
                            <th scope="col">Status</th>
                            <th scope="col">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($parts as $part)
                        @php
                            // For Super Admin: show part's own branch stock
                            // For Branch users: show their branch stock
                            $userBranchId = auth()->user()->branch_id ?? $part->branch_id;
                            $stock = $part->stock->where('branch_id', $userBranchId)->first();
                            $quantity = $stock ? $stock->quantity : 0;
                        @endphp
                        <tr>
                            <td>{{ ($parts->currentPage() - 1) * $parts->perPage() + $loop->iteration }}</td>
                            <td>
                                @if($part->picture)
                                <img src="{{ asset('storage/' . $part->picture) }}" alt="{{ $part->name }}" class="rounded" style="width: 50px; height: 50px; object-fit: cover;">
                                @else
                                <div class="bg-light rounded d-flex align-items-center justify-content-center" style="width: 50px; height: 50px;">
                                    <span class="material-symbols-outlined text-muted">image</span>
                                </div>
                                @endif
                            </td>
                            <td><strong>{{ $part->name }}</strong></td>
                            <td><span class="badge bg-secondary">{{ $part->sku }}</span></td>
                            <td>{{ $part->branch->name ?? 'N/A' }}</td>
                            <td class="fw-semibold">₦{{ number_format($part->cost, 2) }}</td>
                            <td>
                                <span class="badge {{ $quantity >= 3 ? 'bg-success' : ($quantity > 0 ? 'bg-warning' : 'bg-danger') }}">
                                    {{ $quantity }} units
                                </span>
                            </td>
                            <td>
                                @if($quantity >= 3)
                                <span class="badge bg-success">In Stock</span>
                                @elseif($quantity > 0)
                                <span class="badge bg-warning">Low Stock</span>
                                @else
                                <span class="badge bg-danger">Out of Stock</span>
                                @endif
                            </td>
                            <td>
                                <div class="d-flex gap-1">
                                    <a href="{{ route('admin.parts.show', $part) }}" class="btn btn-sm btn-primary" title="View Details">
                                        <i class="ri-eye-line"></i>
                                    </a>
                                    @can('manage inventory')
                                    <a href="{{ route('admin.parts.edit', $part) }}" class="btn btn-sm btn-secondary" title="Edit">
                                        <i class="ri-edit-line"></i>
                                    </a>
                                    <button type="button" class="btn btn-sm btn-success" data-bs-toggle="modal" data-bs-target="#stockInModal{{ $part->id }}" title="Stock In">
                                        <i class="ri-add-line"></i>
                                    </button>
                                    @endcan
                                </div>
                            </td>
                        </tr>

                        <!-- Stock In Modal -->
                        <div class="modal fade" id="stockInModal{{ $part->id }}" tabindex="-1">
                            <div class="modal-dialog">
                                <div class="modal-content">
                                    <div class="modal-header">
                                        <h5 class="modal-title">
                                            <span class="material-symbols-outlined me-2" style="vertical-align: middle;">add_circle</span>
                                            Stock In: {{ $part->name }}
                                        </h5>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                    </div>
                                    <form action="{{ route('admin.parts.stock-in', $part) }}" method="POST">
                                        @csrf
                                        <div class="modal-body">
                                            @php
                                                $isSuperAdmin = auth()->user()->hasRole('Super Admin');
                                                $targetBranch = auth()->user()->branch ?? $part->branch;
                                            @endphp
                                            
                                            @if($isSuperAdmin)
                                                <!-- Super Admin: Can select target branch -->
                                                <div class="mb-3">
                                                    <label for="branch_id{{ $part->id }}" class="form-label fw-semibold">Target Branch <span class="text-danger">*</span></label>
                                                    <select class="form-select" id="branch_id{{ $part->id }}" name="branch_id" required>
                                                        <option value="">Select branch...</option>
                                                        @foreach(\App\Models\Branch::all() as $branch)
                                                            <option value="{{ $branch->id }}" {{ $branch->id == $part->branch_id ? 'selected' : '' }}>
                                                                {{ $branch->name }}
                                                                @php
                                                                    $branchStock = $part->stock->where('branch_id', $branch->id)->first();
                                                                    $branchQty = $branchStock ? $branchStock->quantity : 0;
                                                                @endphp
                                                                (Current: {{ $branchQty }} units)
                                                            </option>
                                                        @endforeach
                                                    </select>
                                                    <small class="text-muted">Choose which branch to add stock to</small>
                                                </div>
                                            @else
                                                <!-- Branch User: Shows their branch only -->
                                                <div class="alert alert-info mb-3">
                                                    <i class="ri-information-line me-2"></i>
                                                    <strong>Branch:</strong> {{ $targetBranch->name }}
                                                </div>
                                            @endif
                                            
                                            <div class="mb-3">
                                                <p class="mb-2">
                                                    <span class="text-secondary">Current Stock:</span> 
                                                    <strong class="text-primary fs-20">{{ $quantity }} units</strong>
                                                </p>
                                            </div>
                                            
                                            <div class="mb-3">
                                                <label for="quantity{{ $part->id }}" class="form-label fw-semibold">Quantity to Add <span class="text-danger">*</span></label>
                                                <input type="number" 
                                                       class="form-control" 
                                                       id="quantity{{ $part->id }}" 
                                                       name="quantity" 
                                                       min="1" 
                                                       placeholder="Enter quantity to add"
                                                       required
                                                       autofocus>
                                                @if(!$isSuperAdmin)
                                                <small class="text-muted">Stock will be added to {{ $targetBranch->name }}</small>
                                                @endif
                                            </div>
                                        </div>
                                        <div class="modal-footer">
                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                                                <i class="ri-close-line me-1"></i> Cancel
                                            </button>
                                            <button type="submit" class="btn btn-success">
                                                <i class="ri-check-line me-1"></i> Add to Stock
                                            </button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                        @empty
                        <tr>
                            <td colspan="9" class="text-center py-4">
                                <div class="text-muted">
                                    <span class="material-symbols-outlined fs-48">inventory</span>
                                    <p class="mt-2">No parts found. Click "Add New Part" to get started.</p>
                                </div>
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="mt-3">
                {{ $parts->links() }}
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="{{ asset('assets/js/data-table.js') }}"></script>
<script>
    $(document).ready(function() {
        $('#partsTable').DataTable({
            "paging": false,
            "searching": true,
            "info": false
        });
    });
</script>
@endpush
