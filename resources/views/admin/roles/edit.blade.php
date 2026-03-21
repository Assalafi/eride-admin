@extends('layouts.app')

@section('title', 'Edit Role')

@section('content')
    <div class="container-fluid">
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">
                            <i class="ri-shield-user-line me-2"></i>Edit Role: {{ $role->name }}
                        </h5>
                    </div>
                    <div class="card-body">
                        @if (session('error'))
                            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                {{ session('error') }}
                                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                            </div>
                        @endif

                        @if ($role->name === 'Super Admin')
                            <div class="alert alert-warning">
                                <i class="ri-alert-line me-2"></i>
                                <strong>System Role:</strong> The Super Admin role cannot be modified for security reasons.
                            </div>
                        @endif

                        <form action="{{ route('admin.roles.update', $role) }}" method="POST">
                            @csrf
                            @method('PUT')

                            <!-- Role Information -->
                            <div class="row mb-4">
                                <div class="col-md-6">
                                    <h6 class="text-primary mb-3">
                                        <i class="ri-information-line me-1"></i>Role Information
                                    </h6>

                                    <div class="mb-3">
                                        <label for="name" class="form-label">Role Name <span
                                                class="text-danger">*</span></label>
                                        <input type="text"
                                            class="form-control @error('name') is-invalid @enderror {{ $role->name === 'Super Admin' ? 'bg-light' : '' }}"
                                            id="name" name="name" value="{{ old('name', $role->name) }}"
                                            placeholder="e.g., Branch Manager"
                                            {{ $role->name === 'Super Admin' ? 'readonly' : 'required' }}>
                                        @error('name')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                        @if ($role->name === 'Super Admin')
                                            <div class="form-text">System roles cannot be modified.</div>
                                        @endif
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <h6 class="text-info mb-3">
                                        <i class="ri-shield-check-line me-1"></i>Role Statistics
                                    </h6>
                                    <div class="alert alert-info">
                                        <div class="d-flex justify-content-between mb-2">
                                            <span>Users with this role:</span>
                                            <strong>{{ $role->users->count() }}</strong>
                                        </div>
                                        <div class="d-flex justify-content-between mb-2">
                                            <span>Permissions assigned:</span>
                                            <strong>{{ $role->permissions->count() }}</strong>
                                        </div>
                                        <div class="d-flex justify-content-between">
                                            <span>Created:</span>
                                            <strong>{{ $role->created_at->format('M d, Y') }}</strong>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Permissions by Category -->
                            <div class="mb-4">
                                <div class="d-flex justify-content-between align-items-center mb-3">
                                    <h6 class="text-primary mb-0">
                                        <i class="ri-key-line me-1"></i>Assign Permissions
                                    </h6>
                                    @if ($role->name !== 'Super Admin')
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" id="selectAll"
                                                onchange="toggleAllPermissions()">
                                            <label class="form-check-label fw-bold" for="selectAll">
                                                Select All Permissions
                                            </label>
                                        </div>
                                    @endif
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
                                                            {{ in_array($permission->id, $rolePermissions) ? 'checked' : '' }}
                                                            {{ $role->name === 'Super Admin' ? 'disabled' : '' }}
                                                            onchange="updateSelectAll()">
                                                        <label class="form-check-label {{ $role->name === 'Super Admin' ? 'text-muted' : '' }}" for="permission_{{ $permission->id }}">
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
                                                            {{ in_array($permission->id, $rolePermissions) ? 'checked' : '' }}
                                                            {{ $role->name === 'Super Admin' ? 'disabled' : '' }}
                                                            onchange="updateSelectAll()">
                                                        <label class="form-check-label {{ $role->name === 'Super Admin' ? 'text-muted' : '' }}" for="permission_{{ $permission->id }}">
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
                                                            {{ in_array($permission->id, $rolePermissions) ? 'checked' : '' }}
                                                            {{ $role->name === 'Super Admin' ? 'disabled' : '' }}
                                                            onchange="updateSelectAll()">
                                                        <label class="form-check-label {{ $role->name === 'Super Admin' ? 'text-muted' : '' }}" for="permission_{{ $permission->id }}">
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
                                                            {{ in_array($permission->id, $rolePermissions) ? 'checked' : '' }}
                                                            {{ $role->name === 'Super Admin' ? 'disabled' : '' }}
                                                            onchange="updateSelectAll()">
                                                        <label class="form-check-label {{ $role->name === 'Super Admin' ? 'text-muted' : '' }}" for="permission_{{ $permission->id }}">
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
                                                            {{ in_array($permission->id, $rolePermissions) ? 'checked' : '' }}
                                                            {{ $role->name === 'Super Admin' ? 'disabled' : '' }}
                                                            onchange="updateSelectAll()">
                                                        <label class="form-check-label {{ $role->name === 'Super Admin' ? 'text-muted' : '' }}" for="permission_{{ $permission->id }}">
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
                                                            {{ in_array($permission->id, $rolePermissions) ? 'checked' : '' }}
                                                            {{ $role->name === 'Super Admin' ? 'disabled' : '' }}
                                                            onchange="updateSelectAll()">
                                                        <label class="form-check-label {{ $role->name === 'Super Admin' ? 'text-muted' : '' }}" for="permission_{{ $permission->id }}">
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
                                                            {{ in_array($permission->id, $rolePermissions) ? 'checked' : '' }}
                                                            {{ $role->name === 'Super Admin' ? 'disabled' : '' }}
                                                            onchange="updateSelectAll()">
                                                        <label class="form-check-label {{ $role->name === 'Super Admin' ? 'text-muted' : '' }}" for="permission_{{ $permission->id }}">
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
                                                            {{ in_array($permission->id, $rolePermissions) ? 'checked' : '' }}
                                                            {{ $role->name === 'Super Admin' ? 'disabled' : '' }}
                                                            onchange="updateSelectAll()">
                                                        <label class="form-check-label {{ $role->name === 'Super Admin' ? 'text-muted' : '' }}" for="permission_{{ $permission->id }}">
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
                                                            {{ in_array($permission->id, $rolePermissions) ? 'checked' : '' }}
                                                            {{ $role->name === 'Super Admin' ? 'disabled' : '' }}
                                                            onchange="updateSelectAll()">
                                                        <label class="form-check-label {{ $role->name === 'Super Admin' ? 'text-muted' : '' }}" for="permission_{{ $permission->id }}">
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
                                                            {{ in_array($permission->id, $rolePermissions) ? 'checked' : '' }}
                                                            {{ $role->name === 'Super Admin' ? 'disabled' : '' }}
                                                            onchange="updateSelectAll()">
                                                        <label class="form-check-label {{ $role->name === 'Super Admin' ? 'text-muted' : '' }}" for="permission_{{ $permission->id }}">
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
                                    @if ($role->name !== 'Super Admin')
                                        <button type="reset" class="btn btn-outline-warning me-2">
                                            <i class="ri-refresh-line me-1"></i>Reset
                                        </button>
                                        <button type="submit" class="btn btn-primary">
                                            <i class="ri-save-line me-1"></i>Update Role
                                        </button>
                                    @endif
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
            const checkboxes = document.querySelectorAll('.permission-checkbox:not(:disabled)');

            checkboxes.forEach(checkbox => {
                checkbox.checked = selectAll.checked;
            });
        }

        function updateSelectAll() {
            const selectAll = document.getElementById('selectAll');
            const checkboxes = document.querySelectorAll('.permission-checkbox:not(:disabled)');
            const checkedBoxes = document.querySelectorAll('.permission-checkbox:checked:not(:disabled)');

            selectAll.checked = checkboxes.length === checkedBoxes.length && checkboxes.length > 0;
            selectAll.indeterminate = checkedBoxes.length > 0 && checkedBoxes.length < checkboxes.length;
        }

        // Initialize select all state on page load
        document.addEventListener('DOMContentLoaded', function() {
            updateSelectAll();
        });
    </script>
@endpush
