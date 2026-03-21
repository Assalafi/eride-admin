@extends('layouts.app')

@section('title', 'Branches Management')

@push('styles')
<link rel="stylesheet" href="{{ asset('assets/css/data-table.css') }}">
@endpush

@section('content')
<div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-4">
    <h3 class="mb-0">Branches Management</h3>

    <nav style="--bs-breadcrumb-divider: '>';" aria-label="breadcrumb">
        <ol class="breadcrumb align-items-center mb-0 lh-1">
            <li class="breadcrumb-item">
                <a href="{{ route('dashboard') }}" class="d-flex align-items-center text-decoration-none">
                    <i class="ri-home-4-line fs-18 text-primary me-1"></i>
                    <span class="text-secondary fw-medium hover">Dashboard</span>
                </a>
            </li>
            <li class="breadcrumb-item active" aria-current="page">
                <span class="fw-medium">Branches</span>
            </li>
        </ol>
    </nav>
</div>

<div class="card bg-white border-0 rounded-3 mb-4">
    <div class="card-body p-4">
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-4">
            <h4 class="mb-0">All Branches</h4>
            @can('manage branches')
            <a href="{{ route('admin.branches.create') }}" class="btn btn-primary">
                <span class="material-symbols-outlined me-2" style="vertical-align: middle;">add</span>
                Add New Branch
            </a>
            @endcan
        </div>

        <div class="default-table-area">
            <div class="table-responsive">
                <table class="table align-middle">
                    <thead>
                        <tr>
                            <th scope="col">#</th>
                            <th scope="col">Branch Name</th>
                            <th scope="col">Location</th>
                            <th scope="col">Drivers</th>
                            <th scope="col">Vehicles</th>
                            <th scope="col">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($branches as $branch)
                        <tr>
                            <td>{{ $loop->iteration }}</td>
                            <td>
                                <div class="d-flex align-items-center">
                                    <div class="flex-shrink-0">
                                        <div class="rounded-circle wh-44 bg-primary-div d-flex align-items-center justify-content-center text-white fw-bold">
                                            <span class="material-symbols-outlined">business</span>
                                        </div>
                                    </div>
                                    <div class="flex-grow-1 ms-2">
                                        <h6 class="mb-0 fw-semibold">{{ $branch->name }}</h6>
                                    </div>
                                </div>
                            </td>
                            <td>{{ $branch->location }}</td>
                            <td>
                                <span class="badge bg-info">{{ $branch->drivers->count() }} drivers</span>
                            </td>
                            <td>
                                <span class="badge bg-success">{{ $branch->vehicles->count() }} vehicles</span>
                            </td>
                            <td>
                                <div class="d-flex align-items-center gap-1">
                                    <a href="{{ route('admin.branches.show', $branch) }}" class="btn btn-sm btn-primary" title="View">
                                        <i class="ri-eye-line"></i>
                                    </a>
                                    @can('manage branches')
                                    <a href="{{ route('admin.branches.edit', $branch) }}" class="btn btn-sm btn-secondary" title="Edit">
                                        <i class="ri-edit-line"></i>
                                    </a>
                                    <form action="{{ route('admin.branches.destroy', $branch) }}" method="POST" class="d-inline" onsubmit="return confirm('Are you sure you want to delete this branch?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-danger" title="Delete">
                                            <i class="ri-delete-bin-line"></i>
                                        </button>
                                    </form>
                                    @endcan
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="6" class="text-center py-4">
                                <div class="text-muted">
                                    <span class="material-symbols-outlined fs-48">business_center</span>
                                    <p class="mt-2">No branches found</p>
                                </div>
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="mt-3">
                {{ $branches->links() }}
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="{{ asset('assets/js/data-table.js') }}"></script>
@endpush
