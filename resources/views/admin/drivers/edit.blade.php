@extends('layouts.app')

@section('title', 'Edit Driver')

@section('content')
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-4">
        <h3 class="mb-0">Edit Driver</h3>

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
                    <span class="fw-medium">Edit</span>
                </li>
            </ol>
        </nav>
    </div>

    <div class="row">
        <div class="col-lg-8">
            <div class="card bg-white border-0 rounded-3 mb-4">
                <div class="card-body p-4">
                    <h4 class="mb-4">Edit Driver Information</h4>

                    <form action="{{ route('admin.drivers.update', $driver) }}" method="POST">
                        @csrf
                        @method('PUT')

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="first_name" class="form-label fw-semibold">First Name <span
                                        class="text-danger">*</span></label>
                                <input type="text" class="form-control @error('first_name') is-invalid @enderror"
                                    id="first_name" name="first_name" value="{{ old('first_name', $driver->first_name) }}"
                                    required>
                                @error('first_name')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-6 mb-3">
                                <label for="last_name" class="form-label fw-semibold">Last Name <span
                                        class="text-danger">*</span></label>
                                <input type="text" class="form-control @error('last_name') is-invalid @enderror"
                                    id="last_name" name="last_name" value="{{ old('last_name', $driver->last_name) }}"
                                    required>
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
                                    id="email" name="email" value="{{ old('email', $driver->user->email) }}" required>
                                @error('email')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-6 mb-3">
                                <label for="phone_number" class="form-label fw-semibold">Phone Number <span
                                        class="text-danger">*</span></label>
                                <input type="text" class="form-control @error('phone_number') is-invalid @enderror"
                                    id="phone_number" name="phone_number"
                                    value="{{ old('phone_number', $driver->phone_number) }}" required>
                                @error('phone_number')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="password" class="form-label fw-semibold">New Password</label>
                                <input type="password" class="form-control @error('password') is-invalid @enderror"
                                    id="password" name="password">
                                <small class="text-muted">Leave blank to keep current password</small>
                                @error('password')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-6 mb-3">
                                <label for="password_confirmation" class="form-label fw-semibold">Confirm New
                                    Password</label>
                                <input type="password" class="form-control" id="password_confirmation"
                                    name="password_confirmation">
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-12 mb-3">
                                <div class="card bg-light border-0">
                                    <div class="card-body py-3">
                                        <div class="form-check">
                                            <input type="checkbox"
                                                class="form-check-input @error('is_hire_purchase') is-invalid @enderror"
                                                id="is_hire_purchase" name="is_hire_purchase" value="1"
                                                {{ old('is_hire_purchase', $driver->is_hire_purchase) ? 'checked' : '' }}>
                                            <label class="form-check-label fw-semibold" for="is_hire_purchase">
                                                <i class="ri-car-line me-1"></i>Hire Purchase Driver
                                            </label>
                                            <small class="d-block text-muted mt-1">
                                                Mark this driver as a hire purchase driver. This enables hire purchase
                                                contract management and payment tracking.
                                            </small>
                                            @error('is_hire_purchase')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                        @if ($driver->is_hire_purchase && $driver->activeHirePurchaseContract)
                                            <div class="alert alert-info mt-3 mb-0 py-2">
                                                <i class="ri-information-line me-1"></i>
                                                <strong>Active Contract:</strong>
                                                {{ $driver->activeHirePurchaseContract->contract_number }}
                                                - Progress:
                                                {{ number_format($driver->activeHirePurchaseContract->progress_percentage, 1) }}%
                                                <a href="{{ route('admin.hire-purchase.show', $driver->activeHirePurchaseContract) }}"
                                                    class="ms-2">View Contract</a>
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="d-flex gap-2 mt-4">
                            <button type="submit" class="btn btn-primary">
                                <span class="material-symbols-outlined me-2" style="vertical-align: middle;">save</span>
                                Update Driver
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
                    <h5 class="mb-3">Driver Details</h5>
                    <div class="mb-3">
                        <p class="text-secondary mb-1">Branch</p>
                        <p class="fw-semibold">{{ $driver->branch->name }}</p>
                    </div>
                    <div class="mb-3">
                        <p class="text-secondary mb-1">Wallet Balance</p>
                        <p class="fw-semibold text-success">₦{{ number_format($driver->wallet->balance ?? 0, 2) }}</p>
                    </div>
                    <div class="mb-3">
                        <p class="text-secondary mb-1">Created At</p>
                        <p class="fw-semibold">{{ $driver->created_at->format('M d, Y') }}</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
