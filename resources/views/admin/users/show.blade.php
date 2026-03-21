@extends('layouts.app')

@section('title', 'User Details')

@section('content')
<div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-4">
    <h3 class="mb-0">User Details</h3>

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
                <span class="fw-medium">{{ $user->name }}</span>
            </li>
        </ol>
    </nav>
</div>

<div class="row">
    <div class="col-lg-4">
        <div class="card bg-white border-0 rounded-3 mb-4">
            <div class="card-body p-4 text-center">
                <div class="rounded-circle wh-100 bg-primary-div d-flex align-items-center justify-content-center text-white fw-bold fs-32 mx-auto mb-3">
                    {{ strtoupper(substr($user->name, 0, 2)) }}
                </div>
                <h4 class="mb-1">{{ $user->name }}</h4>
                <p class="text-muted mb-3">
                    @foreach($user->roles as $role)
                    <span class="badge 
                        @if($role->name == 'Super Admin') bg-danger
                        @elseif($role->name == 'Branch Manager') bg-primary
                        @elseif($role->name == 'Mechanic') bg-info
                        @elseif($role->name == 'Storekeeper') bg-success
                        @else bg-secondary
                        @endif">
                        {{ $role->name }}
                    </span>
                    @endforeach
                </p>
                
                <div class="d-flex gap-2 justify-content-center">
                    <a href="{{ route('admin.users.edit', $user) }}" class="btn btn-primary btn-sm">
                        <i class="ri-edit-line"></i> Edit
                    </a>
                    <a href="{{ route('admin.users.index') }}" class="btn btn-secondary btn-sm">
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
                    <p class="fw-semibold">{{ $user->email }}</p>
                </div>
                <div class="mb-0">
                    <p class="text-secondary mb-1">Branch</p>
                    <p class="fw-semibold">{{ $user->branch->name ?? 'N/A' }}</p>
                </div>
            </div>
        </div>

        <div class="card bg-white border-0 rounded-3 mb-4">
            <div class="card-body p-4">
                <h5 class="mb-3">Account Information</h5>
                <div class="mb-3">
                    <p class="text-secondary mb-1">Created At</p>
                    <p class="fw-semibold">{{ $user->created_at->format('M d, Y') }}</p>
                </div>
                <div class="mb-0">
                    <p class="text-secondary mb-1">Last Updated</p>
                    <p class="fw-semibold">{{ $user->updated_at->format('M d, Y') }}</p>
                </div>
            </div>
        </div>
    </div>

    <div class="col-lg-8">
        <div class="card bg-white border-0 rounded-3 mb-4">
            <div class="card-body p-4">
                <h5 class="mb-4">Permissions & Capabilities</h5>
                
                @foreach($user->roles as $role)
                <div class="mb-4">
                    <h6 class="text-primary mb-3">{{ $role->name }} Permissions:</h6>
                    <div class="row">
                        @forelse($role->permissions as $permission)
                        <div class="col-md-6 mb-2">
                            <div class="d-flex align-items-center">
                                <span class="material-symbols-outlined text-success me-2" style="font-size: 18px;">check_circle</span>
                                <span>{{ ucfirst(str_replace('-', ' ', $permission->name)) }}</span>
                            </div>
                        </div>
                        @empty
                        <div class="col-12">
                            <p class="text-muted mb-0">No specific permissions assigned</p>
                        </div>
                        @endforelse
                    </div>
                </div>
                @endforeach
            </div>
        </div>

        @if($user->branch)
        <div class="card bg-white border-0 rounded-3 mb-4">
            <div class="card-body p-4">
                <h5 class="mb-4">Branch Details</h5>
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <p class="text-secondary mb-1">Branch Name</p>
                        <p class="fw-semibold">{{ $user->branch->name }}</p>
                    </div>
                    <div class="col-md-6 mb-3">
                        <p class="text-secondary mb-1">Location</p>
                        <p class="fw-semibold">{{ $user->branch->location }}</p>
                    </div>
                    <div class="col-md-4 mb-3">
                        <p class="text-secondary mb-1">Total Drivers</p>
                        <p class="fw-semibold">{{ $user->branch->drivers->count() }}</p>
                    </div>
                    <div class="col-md-4 mb-3">
                        <p class="text-secondary mb-1">Total Vehicles</p>
                        <p class="fw-semibold">{{ $user->branch->vehicles->count() }}</p>
                    </div>
                    <div class="col-md-4 mb-3">
                        <p class="text-secondary mb-1">Staff Members</p>
                        <p class="fw-semibold">{{ $user->branch->users->count() }}</p>
                    </div>
                </div>
                <a href="{{ route('admin.branches.show', $user->branch) }}" class="btn btn-sm btn-primary">
                    <i class="ri-eye-line"></i> View Branch Details
                </a>
            </div>
        </div>
        @endif
    </div>
</div>
@endsection
