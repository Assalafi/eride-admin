@extends('layouts.app')

@section('title', 'Role Users')

@section('content')
    <div class="container-fluid">
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">
                            <i class="ri-group-line me-2"></i>Users with Role: {{ $role->name }}
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <a href="{{ route('admin.roles.show', $role) }}" class="btn btn-secondary">
                                <i class="ri-arrow-left-line me-1"></i>Back to Role
                            </a>
                        </div>

                        <!-- Role Summary -->
                        <div class="alert alert-info mb-4">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <strong>Role:</strong> {{ $role->name }}<br>
                                    <strong>Total Users:</strong> {{ $users->total() }}<br>
                                    <strong>Permissions:</strong> {{ $role->permissions->count() }} assigned
                                </div>
                                <div>
                                    <span class="badge bg-{{ $role->name === 'Super Admin' ? 'danger' : 'primary' }} fs-6">
                                        {{ $role->name }}
                                    </span>
                                </div>
                            </div>
                        </div>

                        <!-- Users Table -->
                        <div class="table-responsive">
                            <table class="table table-striped table-hover">
                                <thead>
                                    <tr>
                                        <th>Name</th>
                                        <th>Email</th>
                                        <th>Branch</th>
                                        <th>Phone</th>
                                        <th>Status</th>
                                        <th>Joined</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($users as $user)
                                        <tr>
                                            <td>
                                                <div class="d-flex align-items-center">
                                                    <div
                                                        class="avatar-sm bg-primary text-white rounded-circle d-flex align-items-center justify-content-center me-2">
                                                        {{ strtoupper(substr($user->name, 0, 1)) }}
                                                    </div>
                                                    <div>
                                                        <strong>{{ $user->name }}</strong>
                                                        @if ($user->id === auth()->id())
                                                            <span class="badge bg-info ms-1">You</span>
                                                        @endif
                                                    </div>
                                                </div>
                                            </td>
                                            <td>{{ $user->email }}</td>
                                            <td>
                                                @if ($user->branch)
                                                    <span class="badge bg-success">{{ $user->branch->name }}</span>
                                                @else
                                                    <span class="badge bg-secondary">Company Level</span>
                                                @endif
                                            </td>
                                            <td>{{ $user->phone ?? 'N/A' }}</td>
                                            <td>
                                                @if ($user->email_verified_at)
                                                    <span class="badge bg-success">
                                                        <i class="ri-checkbox-circle-line me-1"></i>Active
                                                    </span>
                                                @else
                                                    <span class="badge bg-warning">
                                                        <i class="ri-time-line me-1"></i>Pending
                                                    </span>
                                                @endif
                                            </td>
                                            <td>{{ $user->created_at->format('M d, Y') }}</td>
                                            <td>
                                                <div class="btn-group" role="group">
                                                    <a href="{{ route('admin.users.show', $user) }}"
                                                        class="btn btn-sm btn-outline-primary" data-bs-toggle="tooltip"
                                                        title="View User">
                                                        <i class="ri-eye-line"></i>
                                                    </a>

                                                    @if ($user->id !== auth()->id() && $role->name !== 'Super Admin')
                                                        <a href="{{ route('admin.users.edit', $user) }}"
                                                            class="btn btn-sm btn-outline-warning" data-bs-toggle="tooltip"
                                                            title="Edit User">
                                                            <i class="ri-edit-line"></i>
                                                        </a>
                                                    @endif
                                                </div>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="7" class="text-center py-4">
                                                <i class="ri-user-unfollow-line fs-1 text-muted"></i>
                                                <p class="text-muted mt-2">No users found with this role</p>
                                                <a href="{{ route('admin.users.create') }}" class="btn btn-primary">
                                                    <i class="ri-add-line me-1"></i>Add User
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
                                Showing {{ $users->firstItem() }} to {{ $users->lastItem() }} of {{ $users->total() }}
                                users
                            </div>
                            {{ $users->links() }}
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
