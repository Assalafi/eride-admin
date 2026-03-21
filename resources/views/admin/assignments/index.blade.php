@extends('layouts.app')

@section('title', 'Vehicle Assignments')

@push('styles')
    <link rel="stylesheet" href="{{ asset('assets/css/data-table.css') }}">
    <style>
        .stat-card {
            transition: all 0.3s ease;
            border: none;
            border-radius: 12px;
        }

        .stat-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
        }

        .stat-icon {
            width: 48px;
            height: 48px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .nav-tabs .nav-link {
            border: none;
            color: #6c757d;
            font-weight: 500;
            padding: 12px 20px;
            border-radius: 8px 8px 0 0;
        }

        .nav-tabs .nav-link.active {
            color: #0d6efd;
            background-color: #fff;
            border-bottom: 3px solid #0d6efd;
        }

        .nav-tabs .nav-link:hover:not(.active) {
            color: #0d6efd;
            background-color: #f8f9fa;
        }

        .assignment-row:hover {
            background-color: #f8f9fa;
        }

        .driver-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 600;
            font-size: 14px;
        }

        .vehicle-badge {
            background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%);
            color: white;
            padding: 4px 10px;
            border-radius: 6px;
            font-weight: 600;
            font-size: 12px;
        }

        .duration-badge {
            font-size: 11px;
            padding: 3px 8px;
        }
    </style>
@endpush

@section('content')
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-4">
        <div>
            <h3 class="mb-1">Vehicle Assignments</h3>
            <p class="text-muted mb-0">Manage driver and vehicle assignments</p>
        </div>

        <div class="d-flex align-items-center gap-3">
            @can('assign vehicles')
                <a href="{{ route('admin.assignments.create') }}" class="btn btn-primary">
                    <i class="ri-add-line me-1"></i> New Assignment
                </a>
            @endcan

            <nav style="--bs-breadcrumb-divider: '>';" aria-label="breadcrumb">
                <ol class="breadcrumb align-items-center mb-0 lh-1">
                    <li class="breadcrumb-item">
                        <a href="{{ route('dashboard') }}" class="d-flex align-items-center text-decoration-none">
                            <i class="ri-home-4-line fs-18 text-primary me-1"></i>
                            <span class="text-secondary fw-medium hover">Dashboard</span>
                        </a>
                    </li>
                    <li class="breadcrumb-item active" aria-current="page">
                        <span class="fw-medium">Assignments</span>
                    </li>
                </ol>
            </nav>
        </div>
    </div>

    <!-- Summary Statistics Cards -->
    <div class="row g-3 mb-4">
        <div class="col-xl-2 col-md-4 col-sm-6">
            <div class="card stat-card bg-white h-100">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center">
                        <div class="stat-icon bg-primary bg-opacity-10 me-3">
                            <i class="ri-file-list-3-line fs-24 text-primary"></i>
                        </div>
                        <div>
                            <h3 class="mb-0 fw-bold">{{ number_format($stats['total'] ?? 0) }}</h3>
                            <small class="text-muted">Total</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-2 col-md-4 col-sm-6">
            <div class="card stat-card bg-white h-100">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center">
                        <div class="stat-icon bg-success bg-opacity-10 me-3">
                            <i class="ri-checkbox-circle-line fs-24 text-success"></i>
                        </div>
                        <div>
                            <h3 class="mb-0 fw-bold text-success">{{ number_format($stats['active'] ?? 0) }}</h3>
                            <small class="text-muted">Active</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-2 col-md-4 col-sm-6">
            <div class="card stat-card bg-white h-100">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center">
                        <div class="stat-icon bg-secondary bg-opacity-10 me-3">
                            <i class="ri-history-line fs-24 text-secondary"></i>
                        </div>
                        <div>
                            <h3 class="mb-0 fw-bold text-secondary">{{ number_format($stats['returned'] ?? 0) }}</h3>
                            <small class="text-muted">Returned</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-2 col-md-4 col-sm-6">
            <div class="card stat-card bg-white h-100">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center">
                        <div class="stat-icon bg-info bg-opacity-10 me-3">
                            <i class="ri-calendar-check-line fs-24 text-info"></i>
                        </div>
                        <div>
                            <h3 class="mb-0 fw-bold text-info">{{ number_format($stats['today'] ?? 0) }}</h3>
                            <small class="text-muted">Today</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-2 col-md-4 col-sm-6">
            <div class="card stat-card bg-white h-100">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center">
                        <div class="stat-icon bg-warning bg-opacity-10 me-3">
                            <i class="ri-calendar-line fs-24 text-warning"></i>
                        </div>
                        <div>
                            <h3 class="mb-0 fw-bold text-warning">{{ number_format($stats['this_week'] ?? 0) }}</h3>
                            <small class="text-muted">This Week</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-2 col-md-4 col-sm-6">
            <div class="card stat-card bg-white h-100">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center">
                        <div class="stat-icon bg-danger bg-opacity-10 me-3">
                            <i class="ri-calendar-2-line fs-24 text-danger"></i>
                        </div>
                        <div>
                            <h3 class="mb-0 fw-bold text-danger">{{ number_format($stats['this_month'] ?? 0) }}</h3>
                            <small class="text-muted">This Month</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Main Content Card with Tabs -->
    <div class="card bg-white border-0 rounded-3 mb-4">
        <!-- Tab Navigation -->
        <div class="card-header bg-transparent border-bottom-0 pt-3 pb-0">
            <ul class="nav nav-tabs border-0" role="tablist">
                <li class="nav-item">
                    <a class="nav-link {{ ($tab ?? 'active') === 'active' ? 'active' : '' }}"
                        href="{{ route('admin.assignments.index', ['tab' => 'active']) }}">
                        <i class="ri-checkbox-circle-line me-1"></i> Active
                        <span class="badge bg-success ms-1">{{ $stats['active'] ?? 0 }}</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link {{ ($tab ?? '') === 'returned' ? 'active' : '' }}"
                        href="{{ route('admin.assignments.index', ['tab' => 'returned']) }}">
                        <i class="ri-history-line me-1"></i> Returned
                        <span class="badge bg-secondary ms-1">{{ $stats['returned'] ?? 0 }}</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link {{ ($tab ?? '') === 'today' ? 'active' : '' }}"
                        href="{{ route('admin.assignments.index', ['tab' => 'today']) }}">
                        <i class="ri-calendar-check-line me-1"></i> Today
                        <span class="badge bg-info ms-1">{{ $stats['today'] ?? 0 }}</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link {{ ($tab ?? '') === 'week' ? 'active' : '' }}"
                        href="{{ route('admin.assignments.index', ['tab' => 'week']) }}">
                        <i class="ri-calendar-line me-1"></i> This Week
                        <span class="badge bg-warning ms-1">{{ $stats['this_week'] ?? 0 }}</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link {{ ($tab ?? '') === 'all' ? 'active' : '' }}"
                        href="{{ route('admin.assignments.index', ['tab' => 'all']) }}">
                        <i class="ri-list-check me-1"></i> All History
                        <span class="badge bg-dark ms-1">{{ $stats['total'] ?? 0 }}</span>
                    </a>
                </li>
            </ul>
        </div>

        <div class="card-body p-4">
            <div class="default-table-area">
                <div class="table-responsive">
                    <table class="table align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th scope="col" style="width: 50px;">#</th>
                                <th scope="col">Driver</th>
                                <th scope="col">Vehicle</th>
                                <th scope="col">Branch</th>
                                <th scope="col">Assigned</th>
                                <th scope="col">Duration</th>
                                <th scope="col">Status</th>
                                <th scope="col" class="text-center">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($assignments as $assignment)
                                <tr class="assignment-row">
                                    <td>
                                        <span
                                            class="text-muted fw-medium">{{ $loop->iteration + ($assignments->currentPage() - 1) * $assignments->perPage() }}</span>
                                    </td>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <div class="driver-avatar me-2">
                                                {{ strtoupper(substr($assignment->driver->first_name ?? 'D', 0, 1)) }}{{ strtoupper(substr($assignment->driver->last_name ?? '', 0, 1)) }}
                                            </div>
                                            <div>
                                                <strong>{{ $assignment->driver->full_name }}</strong>
                                                <br><small class="text-muted"><i class="ri-phone-line"></i>
                                                    {{ $assignment->driver->phone_number }}</small>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <div>
                                            <span class="vehicle-badge">{{ $assignment->vehicle->plate_number }}</span>
                                            <br><small
                                                class="text-muted mt-1 d-inline-block">{{ $assignment->vehicle->make }}
                                                {{ $assignment->vehicle->model }}</small>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="badge bg-light text-dark">
                                            <i
                                                class="ri-building-line me-1"></i>{{ $assignment->driver->branch->name ?? 'N/A' }}
                                        </span>
                                    </td>
                                    <td>
                                        <div>
                                            <strong>{{ $assignment->assigned_at->format('M d, Y') }}</strong>
                                            <br><small
                                                class="text-muted">{{ $assignment->assigned_at->format('h:i A') }}</small>
                                        </div>
                                    </td>
                                    <td>
                                        @if ($assignment->returned_at)
                                            @php
                                                $duration = $assignment->assigned_at->diff($assignment->returned_at);
                                                $durationText = '';
                                                if ($duration->days > 0) {
                                                    $durationText .= $duration->days . 'd ';
                                                }
                                                if ($duration->h > 0) {
                                                    $durationText .= $duration->h . 'h ';
                                                }
                                                if ($duration->i > 0) {
                                                    $durationText .= $duration->i . 'm';
                                                }
                                            @endphp
                                            <span class="badge bg-light text-dark duration-badge">
                                                <i class="ri-time-line me-1"></i>{{ trim($durationText) ?: '< 1m' }}
                                            </span>
                                            <br><small class="text-muted">Returned:
                                                {{ $assignment->returned_at->format('M d, Y') }}</small>
                                        @else
                                            @php
                                                $duration = $assignment->assigned_at->diffForHumans(null, true);
                                            @endphp
                                            <span class="badge bg-success bg-opacity-10 text-success duration-badge">
                                                <i class="ri-timer-line me-1"></i>{{ $duration }}
                                            </span>
                                            <br><small class="text-success">Ongoing</small>
                                        @endif
                                    </td>
                                    <td>
                                        @if ($assignment->isActive())
                                            <span class="badge bg-success"><i
                                                    class="ri-checkbox-circle-fill me-1"></i>Active</span>
                                        @else
                                            <span class="badge bg-secondary"><i
                                                    class="ri-checkbox-blank-circle-line me-1"></i>Returned</span>
                                        @endif
                                    </td>
                                    <td class="text-center">
                                        @if ($assignment->isActive())
                                            @can('return vehicles')
                                                <form action="{{ route('admin.assignments.return', $assignment) }}"
                                                    method="POST" class="d-inline"
                                                    onsubmit="return confirm('Are you sure you want to mark this vehicle as returned?')">
                                                    @csrf
                                                    <button type="submit" class="btn btn-sm btn-outline-warning"
                                                        title="Return Vehicle">
                                                        <i class="ri-arrow-go-back-line me-1"></i> Return
                                                    </button>
                                                </form>
                                            @endcan
                                        @else
                                            <span class="text-muted">
                                                <i class="ri-check-double-line text-success"></i> Completed
                                            </span>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8" class="text-center py-5">
                                        <div class="text-muted">
                                            <i class="ri-car-line fs-48 d-block mb-2"></i>
                                            <h5>No assignments found</h5>
                                            <p class="mb-0">
                                                @if (($tab ?? 'active') === 'active')
                                                    There are no active vehicle assignments at the moment.
                                                @elseif(($tab ?? '') === 'returned')
                                                    No returned vehicles found.
                                                @elseif(($tab ?? '') === 'today')
                                                    No assignments made today.
                                                @elseif(($tab ?? '') === 'week')
                                                    No assignments made this week.
                                                @else
                                                    No assignment history available.
                                                @endif
                                            </p>
                                            @can('assign vehicles')
                                                <a href="{{ route('admin.assignments.create') }}"
                                                    class="btn btn-primary btn-sm mt-3">
                                                    <i class="ri-add-line me-1"></i> Create New Assignment
                                                </a>
                                            @endcan
                                        </div>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                @if ($assignments->hasPages())
                    <div class="mt-4 d-flex justify-content-between align-items-center">
                        <div class="text-muted">
                            Showing {{ $assignments->firstItem() }} to {{ $assignments->lastItem() }} of
                            {{ $assignments->total() }} entries
                        </div>
                        {{ $assignments->links() }}
                    </div>
                @endif
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script src="{{ asset('assets/js/data-table.js') }}"></script>
@endpush
