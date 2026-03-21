@extends('layouts.app')

@section('title', 'Role Management')

@section('content')
    <div class="container-fluid">
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">
                            <i class="ri-shield-user-line me-2"></i>Role Management
                        </h5>
                        <a href="{{ route('admin.roles.create') }}" class="btn btn-primary">
                            <i class="ri-add-line me-1"></i>Add New Role
                        </a>
                    </div>
                    <div class="card-body">
                        @if (session('success'))
                            <div class="alert alert-success alert-dismissible fade show" role="alert">
                                {{ session('success') }}
                                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                            </div>
                        @endif

                        @if (session('error'))
                            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                {{ session('error') }}
                                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                            </div>
                        @endif

                        <!-- Statistics Cards -->
                        <div class="row mb-4">
                            <div class="col-md-4">
                                <div class="card bg-primary text-white">
                                    <div class="card-body">
                                        <div class="d-flex justify-content-between">
                                            <div>
                                                <h4 class="card-title">{{ $roles->total() }}</h4>
                                                <p class="card-text">Total Roles</p>
                                            </div>
                                            <div class="align-self-center">
                                                <i class="ri-shield-user-line fs-1"></i>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="card bg-success text-white">
                                    <div class="card-body">
                                        <div class="d-flex justify-content-between">
                                            <div>
                                                <h4 class="card-title">{{ $roles->sum('users_count') }}</h4>
                                                <p class="card-text">Total Users</p>
                                            </div>
                                            <div class="align-self-center">
                                                <i class="ri-user-line fs-1"></i>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="card bg-info text-white">
                                    <div class="card-body">
                                        <div class="d-flex justify-content-between">
                                            <div>
                                                <h4 class="card-title">{{ $roles->where('users_count', '>', 0)->count() }}
                                                </h4>
                                                <p class="card-text">Active Roles</p>
                                            </div>
                                            <div class="align-self-center">
                                                <i class="ri-checkbox-circle-line fs-1"></i>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Roles Table -->
                        <div class="table-responsive">
                            <table class="table table-striped table-hover">
                                <thead>
                                    <tr>
                                        <th>Role Name</th>
                                        <th>Users Count</th>
                                        <th>Permissions</th>
                                        <th>Created At</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($roles as $role)
                                        <tr>
                                            <td>
                                                <span
                                                    class="badge bg-{{ $role->name === 'Super Admin' ? 'danger' : 'primary' }} fs-6">
                                                    {{ $role->name }}
                                                </span>
                                                @if ($role->name === 'Super Admin')
                                                    <span class="badge bg-warning ms-1">System</span>
                                                @endif
                                            </td>
                                            <td>
                                                <span class="badge bg-info">{{ $role->users_count }}</span>
                                            </td>
                                            <td>
                                                <span class="badge bg-secondary">{{ $role->permissions->count() }}
                                                    permissions</span>
                                            </td>
                                            <td>{{ $role->created_at->format('M d, Y') }}</td>
                                            <td>
                                                <div class="btn-group" role="group">
                                                    <a href="{{ route('admin.roles.show', $role) }}"
                                                        class="btn btn-sm btn-outline-primary" data-bs-toggle="tooltip"
                                                        title="View">
                                                        <i class="ri-eye-line"></i>
                                                    </a>

                                                    @if ($role->name !== 'Super Admin')
                                                        <a href="{{ route('admin.roles.edit', $role) }}"
                                                            class="btn btn-sm btn-outline-warning" data-bs-toggle="tooltip"
                                                            title="Edit">
                                                            <i class="ri-edit-line"></i>
                                                        </a>

                                                        <a href="{{ route('admin.roles.users', $role) }}"
                                                            class="btn btn-sm btn-outline-info" data-bs-toggle="tooltip"
                                                            title="View Users">
                                                            <i class="ri-group-line"></i>
                                                        </a>

                                                        @if ($role->users_count == 0)
                                                            <button type="button" class="btn btn-sm btn-outline-danger"
                                                                data-bs-toggle="modal"
                                                                data-bs-target="#deleteModal{{ $role->id }}"
                                                                data-bs-toggle="tooltip" title="Delete">
                                                                <i class="ri-delete-bin-line"></i>
                                                            </button>
                                                        @endif
                                                    @endif
                                                </div>
                                            </td>
                                        </tr>

                                        <!-- Delete Confirmation Modal -->
                                        @if ($role->name !== 'Super Admin' && $role->users_count == 0)
                                            <div class="modal fade" id="deleteModal{{ $role->id }}" tabindex="-1">
                                                <div class="modal-dialog">
                                                    <div class="modal-content">
                                                        <div class="modal-header">
                                                            <h5 class="modal-title">Delete Role</h5>
                                                            <button type="button" class="btn-close"
                                                                data-bs-dismiss="modal"></button>
                                                        </div>
                                                        <div class="modal-body">
                                                            <p>Are you sure you want to delete the role
                                                                <strong>{{ $role->name }}</strong>?
                                                            </p>
                                                            <p class="text-danger">This action cannot be undone.</p>
                                                        </div>
                                                        <div class="modal-footer">
                                                            <button type="button" class="btn btn-secondary"
                                                                data-bs-dismiss="modal">Cancel</button>
                                                            <form action="{{ route('admin.roles.destroy', $role) }}"
                                                                method="POST">
                                                                @csrf
                                                                @method('DELETE')
                                                                <button type="submit"
                                                                    class="btn btn-danger">Delete</button>
                                                            </form>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        @endif
                                    @empty
                                        <tr>
                                            <td colspan="5" class="text-center py-4">
                                                <i class="ri-shield-user-line fs-1 text-muted"></i>
                                                <p class="text-muted mt-2">No roles found</p>
                                                <a href="{{ route('admin.roles.create') }}" class="btn btn-primary">
                                                    <i class="ri-add-line me-1"></i>Create First Role
                                                </a>
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>

                        <!-- Pagination -->
                        <div class="d-flex justify-content-between align-items-center mt-3">
                            <div class="text-muted">
                                Showing {{ $roles->firstItem() }} to {{ $roles->lastItem() }} of {{ $roles->total() }}
                                roles
                            </div>
                            {{ $roles->links() }}
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        // Initialize tooltips
        var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'))
        var tooltipList = tooltipTriggerList.map(function(tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl)
        });
    </script>
@endpush
