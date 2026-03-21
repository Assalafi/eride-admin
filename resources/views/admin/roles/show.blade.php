@extends('layouts.app')

@section('title', 'Role Details')

@section('content')
    <div class="container-fluid">
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">
                            <i class="ri-shield-user-line me-2"></i>Role Details: {{ $role->name }}
                        </h5>
                        <div>
                            @if ($role->name !== 'Super Admin')
                                <a href="{{ route('admin.roles.edit', $role) }}" class="btn btn-warning">
                                    <i class="ri-edit-line me-1"></i>Edit Role
                                </a>
                            @endif
                            <a href="{{ route('admin.roles.index') }}" class="btn btn-secondary">
                                <i class="ri-arrow-left-line me-1"></i>Back to Roles
                            </a>
                        </div>
                    </div>
                    <div class="card-body">
                        <!-- Role Information -->
                        <div class="row mb-4">
                            <div class="col-md-6">
                                <h6 class="text-primary mb-3">
                                    <i class="ri-information-line me-1"></i>Role Information
                                </h6>
                                <table class="table table-borderless">
                                    <tr>
                                        <td><strong>Role Name:</strong></td>
                                        <td>
                                            <span
                                                class="badge bg-{{ $role->name === 'Super Admin' ? 'danger' : 'primary' }} fs-6">
                                                {{ $role->name }}
                                            </span>
                                            @if ($role->name === 'Super Admin')
                                                <span class="badge bg-warning ms-1">System</span>
                                            @endif
                                        </td>
                                    </tr>
                                    <tr>
                                        <td><strong>Guard Name:</strong></td>
                                        <td>{{ $role->guard_name }}</td>
                                    </tr>
                                    <tr>
                                        <td><strong>Created At:</strong></td>
                                        <td>{{ $role->created_at->format('M d, Y H:i') }}</td>
                                    </tr>
                                    <tr>
                                        <td><strong>Last Updated:</strong></td>
                                        <td>{{ $role->updated_at->format('M d, Y H:i') }}</td>
                                    </tr>
                                </table>
                            </div>

                            <div class="col-md-6">
                                <h6 class="text-info mb-3">
                                    <i class="ri-bar-chart-line me-1"></i>Statistics
                                </h6>
                                <div class="row">
                                    <div class="col-6">
                                        <div class="card bg-primary text-white">
                                            <div class="card-body text-center">
                                                <h4>{{ $role->users->count() }}</h4>
                                                <p class="mb-0">Users</p>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-6">
                                        <div class="card bg-success text-white">
                                            <div class="card-body text-center">
                                                <h4>{{ $role->permissions->count() }}</h4>
                                                <p class="mb-0">Permissions</p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Permissions -->
                        <div class="mb-4">
                            <h6 class="text-primary mb-3">
                                <i class="ri-key-line me-1"></i>Assigned Permissions ({{ $role->permissions->count() }})
                            </h6>

                            @if ($role->permissions->count() > 0)
                                <div class="row">
                                    @foreach ($role->permissions->groupBy(function ($permission) {
            return explode(' ', $permission->name)[0];
        }) as $group => $permissions)
                                        <div class="col-md-4 mb-3">
                                            <div class="card">
                                                <div class="card-header bg-light">
                                                    <h6 class="mb-0">{{ ucfirst($group) }}</h6>
                                                </div>
                                                <div class="card-body py-2">
                                                    @foreach ($permissions as $permission)
                                                        <span class="badge bg-secondary me-1 mb-1">
                                                            {{ ucfirst(str_replace('_', ' ', $permission->name)) }}
                                                        </span>
                                                    @endforeach
                                                </div>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            @else
                                <div class="alert alert-warning">
                                    <i class="ri-alert-line me-2"></i>
                                    No permissions assigned to this role.
                                </div>
                            @endif
                        </div>

                        <!-- Users -->
                        <div class="mb-4">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <h6 class="text-primary mb-0">
                                    <i class="ri-group-line me-1"></i>Users with this Role ({{ $role->users->count() }})
                                </h6>
                                <a href="{{ route('admin.roles.users', $role) }}" class="btn btn-sm btn-outline-primary">
                                    <i class="ri-eye-line me-1"></i>View All Users
                                </a>
                            </div>

                            @if ($role->users->count() > 0)
                                <div class="table-responsive">
                                    <table class="table table-striped">
                                        <thead>
                                            <tr>
                                                <th>Name</th>
                                                <th>Email</th>
                                                <th>Branch</th>
                                                <th>Status</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach ($role->users->take(5) as $user)
                                                <tr>
                                                    <td>{{ $user->name }}</td>
                                                    <td>{{ $user->email }}</td>
                                                    <td>{{ $user->branch ? $user->branch->name : 'N/A' }}</td>
                                                    <td>
                                                        @if ($user->email_verified_at)
                                                            <span class="badge bg-success">Active</span>
                                                        @else
                                                            <span class="badge bg-warning">Pending</span>
                                                        @endif
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>

                                @if ($role->users->count() > 5)
                                    <div class="text-center mt-2">
                                        <small class="text-muted">Showing 5 of {{ $role->users->count() }} users</small>
                                    </div>
                                @endif
                            @else
                                <div class="alert alert-info">
                                    <i class="ri-user-unfollow-line me-2"></i>
                                    No users assigned to this role.
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
