@extends('layouts.app')

@section('title', 'Drivers Management')

@push('styles')
    <link rel="stylesheet" href="{{ asset('assets/css/data-table.css') }}">
@endpush

@section('content')
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-4">
        <h3 class="mb-0">Drivers Management</h3>

        <nav style="--bs-breadcrumb-divider: '>';" aria-label="breadcrumb">
            <ol class="breadcrumb align-items-center mb-0 lh-1">
                <li class="breadcrumb-item">
                    <a href="{{ route('dashboard') }}" class="d-flex align-items-center text-decoration-none">
                        <i class="ri-home-4-line fs-18 text-primary me-1"></i>
                        <span class="text-secondary fw-medium hover">Dashboard</span>
                    </a>
                </li>
                <li class="breadcrumb-item active" aria-current="page">
                    <span class="fw-medium">Drivers</span>
                </li>
            </ol>
        </nav>
    </div>

    <div class="card bg-white border-0 rounded-3 mb-4">
        <div class="card-body p-4">
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-4">
                <h4 class="mb-0">All Drivers</h4>
                @can('create drivers')
                    <a href="{{ route('admin.drivers.create') }}" class="btn btn-primary">
                        <span class="material-symbols-outlined me-2" style="vertical-align: middle;">add</span>
                        Add New Driver
                    </a>
                @endcan
            </div>

            <div class="default-table-area">
                <div class="table-responsive">
                    <table class="table align-middle" id="driversTable">
                        <thead>
                            <tr>
                                <th scope="col">#</th>
                                <th scope="col">Name</th>
                                <th scope="col">Phone</th>
                                <th scope="col">Email</th>
                                <th scope="col">Branch</th>
                                <th scope="col">Wallet Balance</th>
                                <th scope="col">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($drivers as $driver)
                                <tr>
                                    <td>{{ $loop->iteration }}</td>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <div class="flex-shrink-0">
                                                <div
                                                    class="rounded-circle wh-44 bg-primary-div d-flex align-items-center justify-content-center text-white fw-bold">
                                                    {{ strtoupper(substr($driver->first_name, 0, 1)) }}{{ strtoupper(substr($driver->last_name, 0, 1)) }}
                                                </div>
                                            </div>
                                            <div class="flex-grow-1 ms-2">
                                                <h6 class="mb-0 fw-semibold">{{ $driver->full_name }}</h6>
                                            </div>
                                        </div>
                                    </td>
                                    <td>{{ $driver->phone_number }}</td>
                                    <td>{{ $driver->user->email }}</td>
                                    <td>{{ $driver->branch->name }}</td>
                                    <td>
                                        <span
                                            class="badge bg-success">₦{{ number_format($driver->wallet->balance ?? 0, 2) }}</span>
                                    </td>
                                    <td>
                                        <div class="d-flex gap-1">
                                            <a href="{{ route('admin.drivers.show', $driver) }}"
                                                class="btn btn-sm btn-primary" title="View">
                                                <i class="ri-eye-line"></i>
                                            </a>
                                            <a href="{{ route('admin.drivers.show', $driver) }}#fundWallet"
                                                class="btn btn-sm btn-success" title="Fund Wallet"
                                                onclick="event.preventDefault(); window.location='{{ route('admin.drivers.show', $driver) }}'; setTimeout(() => { document.querySelector('[data-bs-target=\'#fundWalletModal\']')?.click(); }, 500);">
                                                <span class="material-symbols-outlined"
                                                    style="font-size: 16px;">account_balance_wallet</span>
                                            </a>
                                            @can('edit drivers')
                                                <a href="{{ route('admin.drivers.edit', $driver) }}"
                                                    class="btn btn-sm btn-secondary" title="Edit">
                                                    <i class="ri-edit-line"></i>
                                                </a>
                                            @endcan
                                            {{-- @can('delete drivers')
                                    <form action="{{ route('admin.drivers.destroy', $driver) }}" method="POST" class="d-inline" onsubmit="return confirm('Are you sure you want to delete this driver?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-danger" title="Delete">
                                            <i class="ri-delete-bin-line"></i>
                                        </button>
                                    </form>
                                    @endcan --}}
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="text-center py-4">
                                        <div class="text-muted">
                                            <span class="material-symbols-outlined fs-48">group_off</span>
                                            <p class="mt-2">No drivers found</p>
                                        </div>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="mt-3">
                    {{ $drivers->links() }}
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script src="{{ asset('assets/js/data-table.js') }}"></script>
    <script>
        $(document).ready(function() {
            $('#driversTable').DataTable({
                "paging": false,
                "searching": true,
                "info": false
            });
        });
    </script>
@endpush
