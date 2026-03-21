@extends('layouts.app')

@section('title', 'Create Role')

@section('content')
    <div class="container-fluid">
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">
                            <i class="ri-shield-user-line me-2"></i>Create New Role
                        </h5>
                    </div>
                    <div class="card-body">
                        @if (session('error'))
                            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                {{ session('error') }}
                                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                            </div>
                        @endif

                        <form action="{{ route('admin.roles.store') }}" method="POST">
                            @csrf

                            <!-- Role Information -->
                            <div class="row mb-4">
                                <div class="col-md-6">
                                    <h6 class="text-primary mb-3">
                                        <i class="ri-information-line me-1"></i>Role Information
                                    </h6>

                                    <div class="mb-3">
                                        <label for="name" class="form-label">Role Name <span
                                                class="text-danger">*</span></label>
                                        <input type="text" class="form-control @error('name') is-invalid @enderror"
                                            id="name" name="name" value="{{ old('name') }}"
                                            placeholder="e.g., Branch Manager" required>
                                        @error('name')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                        <div class="form-text">Use a descriptive name for the role (e.g., Branch Manager,
                                            Accountant)</div>
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <h6 class="text-info mb-3">
                                        <i class="ri-shield-check-line me-1"></i>Quick Tips
                                    </h6>
                                    <div class="alert alert-info">
                                        <ul class="mb-0">
                                            <li>Role names should be descriptive and unique</li>
                                            <li>Consider the responsibilities when assigning permissions</li>
                                            <li>System roles like 'Super Admin' cannot be edited</li>
                                            <li>You can assign multiple permissions to each role</li>
                                        </ul>
                                    </div>
                                </div>
                            </div>

                            <!-- Permissions by Category -->
                            <div class="mb-4">
                                <div class="d-flex justify-content-between align-items-center mb-3">
                                    <h6 class="text-primary mb-0">
                                        <i class="ri-key-line me-1"></i>Assign Permissions
                                    </h6>
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" id="selectAll"
                                            onchange="toggleAllPermissions()">
                                        <label class="form-check-label fw-bold" for="selectAll">
                                            Select All Permissions
                                        </label>
                                    </div>
                                </div>

                                <!-- Permissions Tabs -->
                                <ul class="nav nav-tabs mb-3" id="permissionsTabs" role="tablist">
                                    <li class="nav-item" role="presentation">
                                        <button class="nav-link active" id="drivers-tab" data-bs-toggle="tab" data-bs-target="#drivers" type="button" role="tab">
                                            <i class="ri-group-line me-1"></i>Drivers
                                        </button>
                                    </li>
                                    <li class="nav-item" role="presentation">
                                        <button class="nav-link" id="vehicles-tab" data-bs-toggle="tab" data-bs-target="#vehicles" type="button" role="tab">
                                            <i class="ri-directions-car-line me-1"></i>Vehicles
                                        </button>
                                    </li>
                                    <li class="nav-item" role="presentation">
                                        <button class="nav-link" id="assignments-tab" data-bs-toggle="tab" data-bs-target="#assignments" type="button" role="tab">
                                            <i class="ri-swap-horiz-line me-1"></i>Assignments
                                        </button>
                                    </li>
                                    <li class="nav-item" role="presentation">
                                        <button class="nav-link" id="payments-tab" data-bs-toggle="tab" data-bs-target="#payments" type="button" role="tab">
                                            <i class="ri-payments-line me-1"></i>Payments
                                        </button>
                                    </li>
                                    <li class="nav-item" role="presentation">
                                        <button class="nav-link" id="maintenance-tab" data-bs-toggle="tab" data-bs-target="#maintenance" type="button" role="tab">
                                            <i class="ri-tools-line me-1"></i>Maintenance
                                        </button>
                                    </li>
                                    <li class="nav-item" role="presentation">
                                        <button class="nav-link" id="charging-tab" data-bs-toggle="tab" data-bs-target="#charging" type="button" role="tab">
                                            <i class="ri-ev-station-line me-1"></i>Charging
                                        </button>
                                    </li>
                                    <li class="nav-item" role="presentation">
                                        <button class="nav-link" id="inventory-tab" data-bs-toggle="tab" data-bs-target="#inventory" type="button" role="tab">
                                            <i class="ri-inventory-line me-1"></i>Inventory
                                        </button>
                                    </li>
                                    <li class="nav-item" role="presentation">
                                        <button class="nav-link" id="accounting-tab" data-bs-toggle="tab" data-bs-target="#accounting" type="button" role="tab">
                                            <i class="ri-account-balance-line me-1"></i>Accounting
                                        </button>
                                    </li>
                                    <li class="nav-item" role="presentation">
                                        <button class="nav-link" id="hire-purchase-tab" data-bs-toggle="tab" data-bs-target="#hire-purchase" type="button" role="tab">
                                            <i class="ri-hand-coin-line me-1"></i>Hire Purchase
                                        </button>
                                    </li>
                                    <li class="nav-item" role="presentation">
                                        <button class="nav-link" id="users-tab" data-bs-toggle="tab" data-bs-target="#users" type="button" role="tab">
                                            <i class="ri-manage-accounts-line me-1"></i>Users & System
                                        </button>
                                    </li>
                                </ul>

                                <!-- Tab Content -->
                                <div class="tab-content" id="permissionsTabContent">
                                    <!-- Drivers Tab -->
                                    <div class="tab-pane fade show active" id="drivers" role="tabpanel">
                                        <div class="row">
                                            @foreach($permissions->filter(function($p) { return str_contains($p->name, 'driver'); }) as $permission)
                                                <div class="col-md-4 mb-2">
                                                    <div class="form-check">
                                                        <input class="form-check-input permission-checkbox" type="checkbox"
                                                            name="permissions[]" value="{{ $permission->id }}"
                                                            id="permission_{{ $permission->id }}"
                                                            onchange="updateSelectAll()">
                                                        <label class="form-check-label" for="permission_{{ $permission->id }}">
                                                            {{ ucfirst(str_replace('_', ' ', $permission->name)) }}
                                                        </label>
                                                    </div>
                                                </div>
                                            @endforeach
                                        </div>
                                    </div>

                                    <!-- Vehicles Tab -->
                                    <div class="tab-pane fade" id="vehicles" role="tabpanel">
                                        <div class="row">
                                            @foreach($permissions->filter(function($p) { return str_contains($p->name, 'vehicle'); }) as $permission)
                                                <div class="col-md-4 mb-2">
                                                    <div class="form-check">
                                                        <input class="form-check-input permission-checkbox" type="checkbox"
                                                            name="permissions[]" value="{{ $permission->id }}"
                                                            id="permission_{{ $permission->id }}"
                                                            onchange="updateSelectAll()">
                                                        <label class="form-check-label" for="permission_{{ $permission->id }}">
                                                            {{ ucfirst(str_replace('_', ' ', $permission->name)) }}
                                                        </label>
                                                    </div>
                                                </div>
                                            @endforeach
                                        </div>
                                    </div>

                                    <!-- Assignments Tab -->
                                    <div class="tab-pane fade" id="assignments" role="tabpanel">
                                        <div class="row">
                                            @foreach($permissions->filter(function($p) { return str_contains($p->name, 'assign') || str_contains($p->name, 'return'); }) as $permission)
                                                <div class="col-md-4 mb-2">
                                                    <div class="form-check">
                                                        <input class="form-check-input permission-checkbox" type="checkbox"
                                                            name="permissions[]" value="{{ $permission->id }}"
                                                            id="permission_{{ $permission->id }}"
                                                            onchange="updateSelectAll()">
                                                        <label class="form-check-label" for="permission_{{ $permission->id }}">
                                                            {{ ucfirst(str_replace('_', ' ', $permission->name)) }}
                                                        </label>
                                                    </div>
                                                </div>
                                            @endforeach
                                        </div>
                                    </div>

                                    <!-- Payments Tab -->
                                    <div class="tab-pane fade" id="payments" role="tabpanel">
                                        <div class="row">
                                            @foreach($permissions->filter(function($p) { return str_contains($p->name, 'payment') || str_contains($p->name, 'wallet'); }) as $permission)
                                                <div class="col-md-4 mb-2">
                                                    <div class="form-check">
                                                        <input class="form-check-input permission-checkbox" type="checkbox"
                                                            name="permissions[]" value="{{ $permission->id }}"
                                                            id="permission_{{ $permission->id }}"
                                                            onchange="updateSelectAll()">
                                                        <label class="form-check-label" for="permission_{{ $permission->id }}">
                                                            {{ ucfirst(str_replace('_', ' ', $permission->name)) }}
                                                        </label>
                                                    </div>
                                                </div>
                                            @endforeach
                                        </div>
                                    </div>

                                    <!-- Maintenance Tab -->
                                    <div class="tab-pane fade" id="maintenance" role="tabpanel">
                                        <div class="row">
                                            @foreach($permissions->filter(function($p) { return str_contains($p->name, 'maintenance'); }) as $permission)
                                                <div class="col-md-4 mb-2">
                                                    <div class="form-check">
                                                        <input class="form-check-input permission-checkbox" type="checkbox"
                                                            name="permissions[]" value="{{ $permission->id }}"
                                                            id="permission_{{ $permission->id }}"
                                                            onchange="updateSelectAll()">
                                                        <label class="form-check-label" for="permission_{{ $permission->id }}">
                                                            {{ ucfirst(str_replace('_', ' ', $permission->name)) }}
                                                        </label>
                                                    </div>
                                                </div>
                                            @endforeach
                                        </div>
                                    </div>

                                    <!-- Charging Tab -->
                                    <div class="tab-pane fade" id="charging" role="tabpanel">
                                        <div class="row">
                                            @foreach($permissions->filter(function($p) { return str_contains($p->name, 'charging'); }) as $permission)
                                                <div class="col-md-4 mb-2">
                                                    <div class="form-check">
                                                        <input class="form-check-input permission-checkbox" type="checkbox"
                                                            name="permissions[]" value="{{ $permission->id }}"
                                                            id="permission_{{ $permission->id }}"
                                                            onchange="updateSelectAll()">
                                                        <label class="form-check-label" for="permission_{{ $permission->id }}">
                                                            {{ ucfirst(str_replace('_', ' ', $permission->name)) }}
                                                        </label>
                                                    </div>
                                                </div>
                                            @endforeach
                                        </div>
                                    </div>

                                    <!-- Inventory Tab -->
                                    <div class="tab-pane fade" id="inventory" role="tabpanel">
                                        <div class="row">
                                            @foreach($permissions->filter(function($p) { return str_contains($p->name, 'inventory'); }) as $permission)
                                                <div class="col-md-4 mb-2">
                                                    <div class="form-check">
                                                        <input class="form-check-input permission-checkbox" type="checkbox"
                                                            name="permissions[]" value="{{ $permission->id }}"
                                                            id="permission_{{ $permission->id }}"
                                                            onchange="updateSelectAll()">
                                                        <label class="form-check-label" for="permission_{{ $permission->id }}">
                                                            {{ ucfirst(str_replace('_', ' ', $permission->name)) }}
                                                        </label>
                                                    </div>
                                                </div>
                                            @endforeach
                                        </div>
                                    </div>

                                    <!-- Accounting Tab -->
                                    <div class="tab-pane fade" id="accounting" role="tabpanel">
                                        <div class="row">
                                            @php
                                                $accountingPermissions = $permissions->filter(function($p) { 
                                                    return (str_contains($p->name, 'account') || str_contains($p->name, 'company') || str_contains($p->name, 'debit')) && !str_contains($p->name, 'hire'); 
                                                });
                                            @endphp
                                            @foreach($accountingPermissions as $permission)
                                                <div class="col-md-4 mb-2">
                                                    <div class="form-check">
                                                        <input class="form-check-input permission-checkbox" type="checkbox"
                                                            name="permissions[]" value="{{ $permission->id }}"
                                                            id="permission_{{ $permission->id }}"
                                                            onchange="updateSelectAll()">
                                                        <label class="form-check-label" for="permission_{{ $permission->id }}">
                                                            {{ ucfirst(str_replace('_', ' ', $permission->name)) }}
                                                        </label>
                                                    </div>
                                                </div>
                                            @endforeach
                                        </div>
                                    </div>

                                    <!-- Hire Purchase Tab -->
                                    <div class="tab-pane fade" id="hire-purchase" role="tabpanel">
                                        <div class="row">
                                            @foreach($permissions->filter(function($p) { return str_contains($p->name, 'hire'); }) as $permission)
                                                <div class="col-md-4 mb-2">
                                                    <div class="form-check">
                                                        <input class="form-check-input permission-checkbox" type="checkbox"
                                                            name="permissions[]" value="{{ $permission->id }}"
                                                            id="permission_{{ $permission->id }}"
                                                            onchange="updateSelectAll()">
                                                        <label class="form-check-label" for="permission_{{ $permission->id }}">
                                                            {{ ucfirst(str_replace('_', ' ', $permission->name)) }}
                                                        </label>
                                                    </div>
                                                </div>
                                            @endforeach
                                        </div>
                                    </div>

                                    <!-- Users & System Tab -->
                                    <div class="tab-pane fade" id="users" role="tabpanel">
                                        <div class="row">
                                            @foreach($permissions->filter(function($p) { return str_contains($p->name, 'user') || str_contains($p->name, 'branch') || str_contains($p->name, 'role') || str_contains($p->name, 'export') || str_contains($p->name, 'manage'); }) as $permission)
                                                <div class="col-md-4 mb-2">
                                                    <div class="form-check">
                                                        <input class="form-check-input permission-checkbox" type="checkbox"
                                                            name="permissions[]" value="{{ $permission->id }}"
                                                            id="permission_{{ $permission->id }}"
                                                            onchange="updateSelectAll()">
                                                        <label class="form-check-label" for="permission_{{ $permission->id }}">
                                                            {{ ucfirst(str_replace('_', ' ', $permission->name)) }}
                                                        </label>
                                                    </div>
                                                </div>
                                            @endforeach
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Form Actions -->
                            <div class="d-flex justify-content-between">
                                <a href="{{ route('admin.roles.index') }}" class="btn btn-secondary">
                                    <i class="ri-arrow-left-line me-1"></i>Back to Roles
                                </a>
                                <div>
                                    <button type="reset" class="btn btn-outline-warning me-2">
                                        <i class="ri-refresh-line me-1"></i>Reset
                                    </button>
                                    <button type="submit" class="btn btn-primary">
                                        <i class="ri-save-line me-1"></i>Create Role
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        function toggleAllPermissions() {
            const selectAll = document.getElementById('selectAll');
            const checkboxes = document.querySelectorAll('.permission-checkbox');

            checkboxes.forEach(checkbox => {
                checkbox.checked = selectAll.checked;
            });
        }

        function updateSelectAll() {
            const selectAll = document.getElementById('selectAll');
            const checkboxes = document.querySelectorAll('.permission-checkbox');
            const checkedCount = document.querySelectorAll('.permission-checkbox:checked').length;

            selectAll.checked = checkedCount === checkboxes.length;
            selectAll.indeterminate = checkedCount > 0 && checkedCount < checkboxes.length;
        }

        // Initialize select all state on page load
        document.addEventListener('DOMContentLoaded', function() {
            updateSelectAll();
        });
    </script>
@endpush
