@extends('layouts.app')

@section('title', 'Add New Driver')

@section('content')
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-4">
        <h3 class="mb-0">Add New Driver</h3>

        <nav style="--bs-breadcrumb-divider: '>';" aria-label="breadcrumb">
            <ol class="breadcrumb align-items-center mb-0 lh-1">
                <li class="breadcrumb-item">
                    <a href="{{ route('dashboard') }}" class="d-flex align-items-center text-decoration-none">
                        <i class="ri-home-4-line fs-18 text-primary me-1"></i>
                        <span class="text-secondary fw-medium hover">Dashboard</span>
                    </a>
                </li>
                <li class="breadcrumb-item">
                    <a href="{{ route('admin.drivers.index') }}" class="text-decoration-none">Drivers</a>
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
                    <h4 class="mb-4">Driver Information</h4>

                    <form action="{{ route('admin.drivers.store') }}" method="POST">
                        @csrf

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="first_name" class="form-label fw-semibold">First Name <span
                                        class="text-danger">*</span></label>
                                <input type="text" class="form-control @error('first_name') is-invalid @enderror"
                                    id="first_name" name="first_name" value="{{ old('first_name') }}" required>
                                @error('first_name')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-6 mb-3">
                                <label for="last_name" class="form-label fw-semibold">Last Name <span
                                        class="text-danger">*</span></label>
                                <input type="text" class="form-control @error('last_name') is-invalid @enderror"
                                    id="last_name" name="last_name" value="{{ old('last_name') }}" required>
                                @error('last_name')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="email" class="form-label fw-semibold">Email Address <span
                                        class="text-danger">*</span></label>
                                <input type="email" class="form-control @error('email') is-invalid @enderror"
                                    id="email" name="email" value="{{ old('email') }}" required>
                                @error('email')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-6 mb-3">
                                <label for="phone_number" class="form-label fw-semibold">Phone Number <span
                                        class="text-danger">*</span></label>
                                <input type="text" class="form-control @error('phone_number') is-invalid @enderror"
                                    id="phone_number" name="phone_number" value="{{ old('phone_number') }}"
                                    placeholder="080XXXXXXXX" required>
                                @error('phone_number')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="password" class="form-label fw-semibold">Password <span
                                        class="text-danger">*</span></label>
                                <input type="password" class="form-control @error('password') is-invalid @enderror"
                                    id="password" name="password" required>
                                @error('password')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-6 mb-3">
                                <label for="password_confirmation" class="form-label fw-semibold">Confirm Password <span
                                        class="text-danger">*</span></label>
                                <input type="password" class="form-control" id="password_confirmation"
                                    name="password_confirmation" required>
                            </div>
                        </div>

                        @if (auth()->user()->hasRole('Super Admin'))
                            <div class="mb-3">
                                <label for="branch_id" class="form-label fw-semibold">Select Branch <span
                                        class="text-danger">*</span></label>
                                <select class="form-select @error('branch_id') is-invalid @enderror" id="branch_id"
                                    name="branch_id" required>
                                    <option value="">Choose a branch...</option>
                                    @foreach ($branches as $branch)
                                        <option value="{{ $branch->id }}"
                                            {{ old('branch_id') == $branch->id ? 'selected' : '' }}>
                                            {{ $branch->name }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('branch_id')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        @endif

                        <!-- Hire Purchase Option -->
                        <div class="card bg-light border-0 rounded-3 mb-4">
                            <div class="card-body p-3">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="is_hire_purchase"
                                        name="is_hire_purchase" value="1"
                                        {{ old('is_hire_purchase') ? 'checked' : '' }}>
                                    <label class="form-check-label fw-semibold" for="is_hire_purchase">
                                        <i class="ri-car-line me-1"></i>Hire Purchase Driver
                                    </label>
                                </div>
                                <small class="text-muted d-block mt-2">
                                    Check this if the driver is acquiring a vehicle through hire purchase.
                                    You can set up the hire purchase contract after creating the driver.
                                </small>
                            </div>
                        </div>

                        <div class="d-flex gap-2 mt-4">
                            <button type="submit" class="btn btn-primary">
                                <span class="material-symbols-outlined me-2" style="vertical-align: middle;">save</span>
                                Create Driver
                            </button>
                            <a href="{{ route('admin.drivers.index') }}" class="btn btn-secondary">
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
                    <h5 class="mb-3">Information</h5>
                    <div class="d-flex align-items-start mb-3">
                        <span class="material-symbols-outlined text-primary me-2">info</span>
                        <p class="mb-0 text-secondary">A user account will be automatically created for this driver with
                            the Driver role.</p>
                    </div>
                    <div class="d-flex align-items-start mb-3">
                        <span class="material-symbols-outlined text-primary me-2">account_balance_wallet</span>
                        <p class="mb-0 text-secondary">A wallet will be automatically created with zero balance.</p>
                    </div>
                    <div class="d-flex align-items-start">
                        <span class="material-symbols-outlined text-primary me-2">business</span>
                        @if (auth()->user()->hasRole('Super Admin'))
                            <p class="mb-0 text-secondary">Please select a branch for this driver from the dropdown above.
                            </p>
                        @else
                            <p class="mb-0 text-secondary">Driver will be assigned to your branch:
                                <strong>{{ auth()->user()->branch->name }}</strong></p>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
