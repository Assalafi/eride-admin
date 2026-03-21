@extends('layouts.app')

@section('title', 'Edit User')

@section('content')
<div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-4">
    <h3 class="mb-0">Edit User</h3>

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
                <span class="fw-medium">Edit</span>
            </li>
        </ol>
    </nav>
</div>

<div class="row">
    <div class="col-lg-8">
        <div class="card bg-white border-0 rounded-3 mb-4">
            <div class="card-body p-4">
                <h4 class="mb-4">Edit User Information</h4>

                <form action="{{ route('admin.users.update', $user) }}" method="POST">
                    @csrf
                    @method('PUT')

                    <div class="mb-3">
                        <label for="name" class="form-label fw-semibold">Full Name <span class="text-danger">*</span></label>
                        <input type="text" 
                               class="form-control @error('name') is-invalid @enderror" 
                               id="name" 
                               name="name" 
                               value="{{ old('name', $user->name) }}" 
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
                               value="{{ old('email', $user->email) }}" 
                               required>
                        @error('email')
                        <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="password" class="form-label fw-semibold">New Password</label>
                            <input type="password" 
                                   class="form-control @error('password') is-invalid @enderror" 
                                   id="password" 
                                   name="password">
                            @error('password')
                            <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <small class="text-muted">Leave blank to keep current password</small>
                        </div>

                        <div class="col-md-6 mb-3">
                            <label for="password_confirmation" class="form-label fw-semibold">Confirm New Password</label>
                            <input type="password" 
                                   class="form-control" 
                                   id="password_confirmation" 
                                   name="password_confirmation">
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
                            <option value="{{ $role->name }}" {{ old('role', $user->roles->first()?->name) == $role->name ? 'selected' : '' }}>
                                {{ $role->name }}
                            </option>
                            @endforeach
                        </select>
                        @error('role')
                        <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-3" id="branchField">
                        <label for="branch_id" class="form-label fw-semibold">Branch <span class="text-danger">*</span></label>
                        <select class="form-select @error('branch_id') is-invalid @enderror" 
                                id="branch_id" 
                                name="branch_id">
                            <option value="">Choose a branch...</option>
                            @foreach($branches as $branch)
                            <option value="{{ $branch->id }}" {{ old('branch_id', $user->branch_id) == $branch->id ? 'selected' : '' }}>
                                {{ $branch->name }}
                            </option>
                            @endforeach
                        </select>
                        @error('branch_id')
                        <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                        <small class="text-muted">Branch is required for branch-level roles (not required for Super Admin or Accountant)</small>
                    </div>

                    <div class="d-flex gap-2 mt-4">
                        <button type="submit" class="btn btn-primary">
                            <span class="material-symbols-outlined me-2" style="vertical-align: middle;">save</span>
                            Update User
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
                <h5 class="mb-3">Current Information</h5>
                <div class="mb-3">
                    <p class="text-secondary mb-1">Current Role</p>
                    <p class="fw-semibold">
                        @foreach($user->roles as $role)
                        <span class="badge 
                            @if($role->name == 'Super Admin') bg-danger
                            @elseif($role->name == 'Accountant') bg-primary
                            @elseif($role->name == 'Branch Manager') bg-info
                            @elseif($role->name == 'Mechanic') bg-secondary
                            @elseif($role->name == 'Storekeeper') bg-success
                            @elseif($role->name == 'Charging Station Operator') bg-warning
                            @else bg-secondary
                            @endif">
                            {{ $role->name }}
                        </span>
                        @endforeach
                    </p>
                </div>
                <div class="mb-0">
                    <p class="text-secondary mb-1">Current Branch</p>
                    <p class="fw-semibold">{{ $user->branch->name ?? 'N/A' }}</p>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
function toggleBranchField() {
    const role = document.getElementById('role').value;
    const branchField = document.getElementById('branchField');
    const branchSelect = document.getElementById('branch_id');
    
    // Company-level roles (Super Admin and Accountant) don't need branch
    const companyLevelRoles = ['Super Admin', 'Accountant'];
    
    if (companyLevelRoles.includes(role)) {
        branchField.style.display = 'none';
        branchSelect.removeAttribute('required');
        branchSelect.value = '';
    } else if (role !== '') {
        branchField.style.display = 'block';
        branchSelect.setAttribute('required', 'required');
    } else {
        branchField.style.display = 'none';
        branchSelect.removeAttribute('required');
    }
}

// Check on page load
document.addEventListener('DOMContentLoaded', function() {
    toggleBranchField();
});
</script>
@endpush
@endsection
