@extends('layouts.app')

@section('title', 'Users Management')

@section('content')
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-4">
        <h3 class="mb-0">Users Management</h3>

        <nav style="--bs-breadcrumb-divider: '>';" aria-label="breadcrumb">
            <ol class="breadcrumb align-items-center mb-0 lh-1">
                <li class="breadcrumb-item">
                    <a href="{{ route('dashboard') }}" class="d-flex align-items-center text-decoration-none">
                        <i class="ri-home-4-line fs-18 text-primary me-1"></i>
                        <span class="text-secondary fw-medium hover">Dashboard</span>
                    </a>
                </li>
                <li class="breadcrumb-item active" aria-current="page">
                    <span class="fw-medium">Users</span>
                </li>
            </ol>
        </nav>
    </div>

    <div class="card bg-white border-0 rounded-3 mb-4">
        <div class="card-body p-0">
            <div class="p-4 d-flex justify-content-between align-items-center border-bottom">
                <h4 class="mb-0">System Users</h4>
                <a href="{{ route('admin.users.create') }}" class="btn btn-primary">
                    <span class="material-symbols-outlined me-2" style="vertical-align: middle;">add</span>
                    Add New User
                </a>
            </div>

            <div class="default-table-area">
                <div class="table-responsive">
                    <table class="table align-middle" id="usersTable">
                        <thead>
                            <tr>
                                <th scope="col">#</th>
                                <th scope="col">Name</th>
                                <th scope="col">Email</th>
                                <th scope="col">Role</th>
                                <th scope="col">Branch</th>
                                <th scope="col">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($users as $user)
                                <tr>
                                    <td>{{ $loop->iteration }}</td>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <div class="flex-shrink-0">
                                                <div
                                                    class="rounded-circle wh-44 bg-primary-div d-flex align-items-center justify-content-center text-white fw-bold">
                                                    {{ strtoupper(substr($user->name, 0, 2)) }}
                                                </div>
                                            </div>
                                            <div class="flex-grow-1 ms-2">
                                                <h6 class="mb-0 fw-semibold">{{ $user->name }}</h6>
                                            </div>
                                        </div>
                                    </td>
                                    <td>{{ $user->email }}</td>
                                    <td>
                                        @foreach ($user->roles as $role)
                                            <span
                                                class="badge 
                                    @if ($role->name == 'Super Admin') bg-danger
                                    @elseif($role->name == 'Branch Manager') bg-primary
                                    @elseif($role->name == 'Mechanic') bg-info
                                    @elseif($role->name == 'Storekeeper') bg-success
                                    @else bg-secondary @endif">
                                                {{ $role->name }}
                                            </span>
                                        @endforeach
                                    </td>
                                    <td>{{ $user->branch->name ?? 'N/A' }}</td>
                                    <td>
                                        <div class="d-flex gap-1">
                                            <a href="{{ route('admin.users.show', $user) }}" class="btn btn-sm btn-primary"
                                                title="View">
                                                <i class="ri-eye-line"></i>
                                            </a>
                                            <a href="{{ route('admin.users.edit', $user) }}"
                                                class="btn btn-sm btn-secondary" title="Edit">
                                                <i class="ri-edit-line"></i>
                                            </a>
                                            @if ($user->id !== auth()->id())
                                                {{-- <form action="{{ route('admin.users.destroy', $user) }}" method="POST" class="d-inline" onsubmit="return confirm('Are you sure you want to delete this user?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-danger" title="Delete">
                                            <i class="ri-delete-bin-line"></i>
                                        </button>
                                    </form> --}}
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="text-center py-4">
                                        <div class="text-muted">
                                            <span class="material-symbols-outlined fs-48">person_off</span>
                                            <p class="mt-2">No users found</p>
                                        </div>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="mt-3 px-4 pb-4">
                    {{ $users->links() }}
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script src="{{ asset('assets/js/data-table.js') }}"></script>
    <script>
        $(document).ready(function() {
            $('#usersTable').DataTable({
                "paging": false,
                "searching": true,
                "info": false
            });
        });
    </script>
@endpush
