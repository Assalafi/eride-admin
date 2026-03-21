@extends('layouts.app')

@section('title', 'Add New Branch')

@section('content')
<div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-4">
    <h3 class="mb-0">Add New Branch</h3>

    <nav style="--bs-breadcrumb-divider: '>';" aria-label="breadcrumb">
        <ol class="breadcrumb align-items-center mb-0 lh-1">
            <li class="breadcrumb-item">
                <a href="{{ route('dashboard') }}" class="d-flex align-items-center text-decoration-none">
                    <i class="ri-home-4-line fs-18 text-primary me-1"></i>
                    <span class="text-secondary fw-medium hover">Dashboard</span>
                </a>
            </li>
            <li class="breadcrumb-item">
                <a href="{{ route('admin.branches.index') }}" class="text-decoration-none">Branches</a>
            </li>
            <li class="breadcrumb-item active" aria-current="page">
                <span class="fw-medium">Add New</span>
            </li>
        </ol>
    </nav>
</div>

<div class="row justify-content-center">
    <div class="col-lg-8">
        <div class="card bg-white border-0 rounded-3 mb-4">
            <div class="card-body p-4">
                <h4 class="mb-4">Branch Information</h4>

                <form action="{{ route('admin.branches.store') }}" method="POST">
                    @csrf

                    <div class="mb-3">
                        <label for="name" class="form-label fw-semibold">Branch Name <span class="text-danger">*</span></label>
                        <input type="text" 
                               class="form-control @error('name') is-invalid @enderror" 
                               id="name" 
                               name="name" 
                               value="{{ old('name') }}" 
                               placeholder="e.g., Maiduguri Branch"
                               required>
                        @error('name')
                        <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <label for="location" class="form-label fw-semibold">Location <span class="text-danger">*</span></label>
                        <input type="text" 
                               class="form-control @error('location') is-invalid @enderror" 
                               id="location" 
                               name="location" 
                               value="{{ old('location') }}" 
                               placeholder="e.g., Maiduguri, Borno State"
                               required>
                        @error('location')
                        <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="d-flex gap-2 mt-4">
                        <button type="submit" class="btn btn-primary">
                            <span class="material-symbols-outlined me-2" style="vertical-align: middle;">save</span>
                            Create Branch
                        </button>
                        <a href="{{ route('admin.branches.index') }}" class="btn btn-secondary">
                            <span class="material-symbols-outlined me-2" style="vertical-align: middle;">cancel</span>
                            Cancel
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
