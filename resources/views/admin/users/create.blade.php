@extends('layouts.app')

@section('title', 'Add New User')

@section('content')
<div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-4">
    <h3 class="mb-0">Add New User</h3>

    <nav style="--bs-breadcrumb-divider: '>';" aria-label="breadcrumb">
        <ol class="breadcrumb align-items-center mb-0 lh-1">
            <li class="breadcrumb-item">
                <a href="{{ route('dashboard') }}" class="d-flex align-items-center text-decoration-none">
                    <i class="ri-home-4-line fs-18 text-primary me-1"></i>
                    <span class="text-secondary fw-medium hover">Dashboard</span>
                </a>
            </li>
            <li class="breadcrumb-item">
                <a href="{{ route('admin.users.index') }}" class="text-decoration-none">Users</a>
            </li>
            <li class="breadcrumb-item active" aria-current="page">
                <span class="fw-medium">Add New</span>
            </li>
        </ol>
    </nav>
</div>

<div class="row">
    <div class="col-lg-8">
        <div class="card bg-white border-0 rounded-3 mb-4">
            <div class="card-body p-4">
                <h4 class="mb-4">User Information</h4>

                <form action="{{ route('admin.users.store') }}" method="POST">
                    @csrf

                    <div class="mb-3">
                        <label for="name" class="form-label fw-semibold">Full Name <span class="text-danger">*</span></label>
                        <input type="text" 
                               class="form-control @error('name') is-invalid @enderror" 
                               id="name" 
                               name="name" 
                               value="{{ old('name') }}" 
                               required>
                        @error('name')
                        <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <label for="email" class="form-label fw-semibold">Email Address <span class="text-danger">*</span></label>
                        <input type="email" 
                               class="form-control @error('email') is-invalid @enderror" 
                               id="email" 
                               name="email" 
                               value="{{ old('email') }}" 
                               required>
                        @error('email')
                        <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="password" class="form-label fw-semibold">Password <span class="text-danger">*</span></label>
                            <input type="password" 
                                   class="form-control @error('password') is-invalid @enderror" 
                                   id="password" 
                                   name="password" 
                                   required>
                            @error('password')
                            <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-6 mb-3">
                            <label for="password_confirmation" class="form-label fw-semibold">Confirm Password <span class="text-danger">*</span></label>
                            <input type="password" 
                                   class="form-control" 
                                   id="password_confirmation" 
                                   name="password_confirmation" 
                                   required>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="role" class="form-label fw-semibold">Role <span class="text-danger">*</span></label>
                        <select class="form-select @error('role') is-invalid @enderror" 
                                id="role" 
                                name="role" 
                                required
                                onchange="toggleBranchField()">
                            <option value="">Choose a role...</option>
                            @foreach($roles as $role)
                            <option value="{{ $role->name }}" {{ old('role') == $role->name ? 'selected' : '' }}>
                                {{ $role->name }}
                            </option>
                            @endforeach
                        </select>
                        @error('role')
                        <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-3" id="branchField" style="display: none;">
                        <label for="branch_id" class="form-label fw-semibold">Branch <span class="text-danger">*</span></label>
                        <select class="form-select @error('branch_id') is-invalid @enderror" 
                                id="branch_id" 
                                name="branch_id">
                            <option value="">Choose a branch...</option>
                            @foreach($branches as $branch)
                            <option value="{{ $branch->id }}" {{ old('branch_id') == $branch->id ? 'selected' : '' }}>
                                {{ $branch->name }}
                            </option>
                            @endforeach
                        </select>
                        @error('branch_id')
                        <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                        <small class="text-muted">Branch is required for branch-level roles (not required for Super Admin or Accountant)</small>
                    </div>

                    <div class="mb-3" id="multiBranchField" style="display: none;">
                        <label class="form-label fw-semibold">Branch Assignments <span class="text-danger">*</span></label>
                        <div class="mb-3">
                            <label for="primary_branch" class="form-label text-primary">Primary Branch <span class="text-danger">*</span></label>
                            <select class="form-select @error('primary_branch') is-invalid @enderror" 
                                    id="primary_branch" 
                                    name="primary_branch" required>
                                <option value="">Choose primary branch...</option>
                                @foreach($branches as $branch)
                                <option value="{{ $branch->id }}" {{ old('primary_branch') == $branch->id ? 'selected' : '' }}>
                                    {{ $branch->name }}
                                </option>
                                @endforeach
                            </select>
                            @error('primary_branch')
                            <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label text-info">Additional Branches (Optional)</label>
                            <div class="border rounded p-3 bg-light">
                                @foreach($branches as $branch)
                                <div class="form-check mb-2">
                                    <input class="form-check-input" 
                                           type="checkbox" 
                                           name="branches[]" 
                                           value="{{ $branch->id }}" 
                                           id="branch_{{ $branch->id }}"
                                           {{ in_array($branch->id, old('branches', [])) ? 'checked' : '' }}>
                                    <label class="form-check-label" for="branch_{{ $branch->id }}">
                                        {{ $branch->name }}
                                    </label>
                                </div>
                                @endforeach
                            </div>
                            <small class="text-muted">Select additional branches this Branch Manager can access. Primary branch will be automatically included.</small>
                        </div>
                    </div>

                    <div class="d-flex gap-2 mt-4">
                        <button type="submit" class="btn btn-primary">
                            <span class="material-symbols-outlined me-2" style="vertical-align: middle;">save</span>
                            Create User
                        </button>
                        <a href="{{ route('admin.users.index') }}" class="btn btn-secondary">
                            <span class="material-symbols-outlined me-2" style="vertical-align: middle;">cancel</span>
                            Cancel
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        <div class="card bg-white border-0 rounded-3 mb-4">
            <div class="card-body p-4">
                <h5 class="mb-3">Role Descriptions</h5>
                
                <div class="mb-3">
                    <span class="badge bg-danger mb-2">Super Admin</span>
                    <p class="text-secondary mb-0 small">Full system access. No branch assignment.</p>
                </div>
                
                <div class="mb-3">
                    <span class="badge bg-primary mb-2">Accountant</span>
                    <p class="text-secondary mb-0 small">Manage company account and debit requests across all branches. No branch assignment.</p>
                </div>
                
                <div class="mb-3">
                    <span class="badge bg-info mb-2">Branch Manager</span>
                    <p class="text-secondary mb-0 small">Manage branch operations, approve payments & maintenance.</p>
                </div>
                
                <div class="mb-3">
                    <span class="badge bg-secondary mb-2">Mechanic</span>
                    <p class="text-secondary mb-0 small">Create and view maintenance requests.</p>
                </div>
                
                <div class="mb-3">
                    <span class="badge bg-success mb-2">Storekeeper</span>
                    <p class="text-secondary mb-0 small">Manage inventory and dispense parts.</p>
                </div>
                
                <div class="mb-0">
                    <span class="badge bg-warning mb-2">Charging Station Operator</span>
                    <p class="text-secondary mb-0 small">Complete approved charging requests.</p>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
function toggleBranchField() {
    const roleSelect = document.getElementById('role');
    const branchField = document.getElementById('branchField');
    const multiBranchField = document.getElementById('multiBranchField');
    const branchSelect = document.getElementById('branch_id');
    const primaryBranchSelect = document.getElementById('primary_branch');
    
    // Hide all branch fields first
    branchField.style.display = 'none';
    multiBranchField.style.display = 'none';
    branchSelect.removeAttribute('required');
    primaryBranchSelect.removeAttribute('required');
    
    // Company-level roles (Super Admin and Accountant) don't need branch
    if (roleSelect.value === 'Super Admin' || roleSelect.value === 'Accountant') {
        // No branch assignment needed
    } else if (roleSelect.value === 'Branch Manager') {
        // Show multi-branch selection for Branch Manager
        multiBranchField.style.display = 'block';
        primaryBranchSelect.setAttribute('required', 'required');
    } else if (roleSelect.value) {
        // Show single branch selection for other roles
        branchField.style.display = 'block';
        branchSelect.setAttribute('required', 'required');
    }
}

// Auto-select primary branch when additional branches are selected
document.addEventListener('DOMContentLoaded', function() {
    const primaryBranchSelect = document.getElementById('primary_branch');
    const branchCheckboxes = document.querySelectorAll('input[name="branches[]"]');
    
    if (primaryBranchSelect && branchCheckboxes.length > 0) {
        primaryBranchSelect.addEventListener('change', function() {
            // Ensure primary branch is checked in additional branches
            branchCheckboxes.forEach(checkbox => {
                if (checkbox.value == this.value) {
                    checkbox.checked = true;
                    checkbox.disabled = true; // Prevent unchecking primary branch
                }
            });
        });
        
        // Initialize on page load
        if (primaryBranchSelect.value) {
            branchCheckboxes.forEach(checkbox => {
                if (checkbox.value == primaryBranchSelect.value) {
                    checkbox.checked = true;
                    checkbox.disabled = true;
                }
            });
        }
    }
});

// Check on page load
document.addEventListener('DOMContentLoaded', function() {
    toggleBranchField();
});
</script>
@endpush
@endsection
