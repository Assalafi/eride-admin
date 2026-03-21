@extends('layouts.app')

@section('title', 'Payment Calendar - ' . $contract->contract_number)

@section('content')
<div class="container-fluid py-4">
    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="{{ route('admin.hire-purchase.index') }}">Hire Purchase</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('admin.hire-purchase.show', $contract) }}">{{ $contract->contract_number }}</a></li>
                    <li class="breadcrumb-item active">Payment Calendar</li>
                </ol>
            </nav>
            <h4 class="mb-0 mt-2">
                <span class="material-symbols-outlined align-middle me-2">calendar_month</span>
                Payment Calendar
            </h4>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('admin.hire-purchase.show', $contract) }}" class="btn btn-outline-secondary">
                <span class="material-symbols-outlined align-middle me-1">arrow_back</span>
                Back to Contract
            </a>
            <button class="btn btn-outline-primary" onclick="window.print()">
                <span class="material-symbols-outlined align-middle me-1">print</span>
                Print Calendar
            </button>
        </div>
    </div>

    <!-- Contract Summary Card -->
    <div class="card mb-4">
        <div class="card-body">
            <div class="row align-items-center">
                <div class="col-md-3">
                    <div class="d-flex align-items-center">
                        <div class="bg-primary bg-opacity-10 rounded-circle p-3 me-3">
                            <span class="material-symbols-outlined text-primary" style="font-size: 28px;">person</span>
                        </div>
                        <div>
                            <small class="text-muted d-block">Driver</small>
                            <strong>{{ $contract->driver->full_name }}</strong>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="d-flex align-items-center">
                        <div class="bg-info bg-opacity-10 rounded-circle p-3 me-3">
                            <span class="material-symbols-outlined text-info" style="font-size: 28px;">directions_car</span>
                        </div>
                        <div>
                            <small class="text-muted d-block">Vehicle</small>
                            <strong>{{ $contract->vehicle->plate_number }}</strong>
                            <small class="d-block text-muted">{{ $contract->vehicle->make }} {{ $contract->vehicle->model }}</small>
                        </div>
                    </div>
                </div>
                <div class="col-md-2">
                    <small class="text-muted d-block">Min. Daily Payment</small>
                    <strong class="text-primary fs-5">₦{{ number_format($contract->daily_payment, 2) }}</strong>
                </div>
                <div class="col-md-2">
                    <small class="text-muted d-block">Progress</small>
                    <div class="progress" style="height: 8px;">
                        <div class="progress-bar bg-success" style="width: {{ $contract->progress_percentage }}%"></div>
                    </div>
                    <small class="text-muted">{{ number_format($contract->progress_percentage, 1) }}% Complete</small>
                </div>
                <div class="col-md-2 text-end">
                    <small class="text-muted d-block">Balance</small>
                    <strong class="text-danger fs-5">₦{{ number_format($contract->outstanding_balance, 2) }}</strong>
                </div>
            </div>
        </div>
    </div>

    <!-- Legend -->
    <div class="card mb-4">
        <div class="card-body py-2">
            <div class="d-flex flex-wrap gap-4 justify-content-center">
                <div class="d-flex align-items-center">
                    <span class="badge bg-success me-2" style="width: 20px; height: 20px;"></span>
                    <small>Paid</small>
                </div>
                <div class="d-flex align-items-center">
                    <span class="badge bg-warning me-2" style="width: 20px; height: 20px;"></span>
                    <small>Partial Payment</small>
                </div>
                <div class="d-flex align-items-center">
                    <span class="badge bg-danger me-2" style="width: 20px; height: 20px;"></span>
                    <small>Overdue</small>
                </div>
                <div class="d-flex align-items-center">
                    <span class="badge bg-primary me-2" style="width: 20px; height: 20px;"></span>
                    <small>Due Today</small>
                </div>
                <div class="d-flex align-items-center">
                    <span class="badge bg-light border me-2" style="width: 20px; height: 20px;"></span>
                    <small>Upcoming</small>
                </div>
                <div class="d-flex align-items-center">
                    <span class="badge bg-secondary me-2" style="width: 20px; height: 20px;"></span>
                    <small>Rest Day (Sunday)</small>
                </div>
            </div>
        </div>
    </div>

    <!-- Calendar View -->
    @php
        $startDate = \Carbon\Carbon::parse($contract->start_date);
        $endDate = \Carbon\Carbon::parse($contract->end_date);
        $today = \Carbon\Carbon::today();
        $currentMonth = $startDate->copy()->startOfMonth();
        $lastMonth = $endDate->copy()->endOfMonth();
        
        // Get all payments indexed by date
        $paymentsByDate = $contract->payments->groupBy(function($payment) {
            return \Carbon\Carbon::parse($payment->payment_date)->format('Y-m-d');
        });
    @endphp

    @while($currentMonth <= $lastMonth)
        <div class="card mb-4 calendar-month">
            <div class="card-header bg-dark text-white">
                <h5 class="mb-0">
                    <span class="material-symbols-outlined align-middle me-2">calendar_month</span>
                    {{ $currentMonth->format('F Y') }}
                </h5>
            </div>
            <div class="card-body p-0">
                <table class="table table-bordered mb-0 calendar-table">
                    <thead class="table-light">
                        <tr>
                            <th class="text-center" style="width: 14.28%;">Sun</th>
                            <th class="text-center" style="width: 14.28%;">Mon</th>
                            <th class="text-center" style="width: 14.28%;">Tue</th>
                            <th class="text-center" style="width: 14.28%;">Wed</th>
                            <th class="text-center" style="width: 14.28%;">Thu</th>
                            <th class="text-center" style="width: 14.28%;">Fri</th>
                            <th class="text-center" style="width: 14.28%;">Sat</th>
                        </tr>
                    </thead>
                    <tbody>
                        @php
                            $monthStart = $currentMonth->copy()->startOfMonth();
                            $monthEnd = $currentMonth->copy()->endOfMonth();
                            $weekStart = $monthStart->copy()->startOfWeek(\Carbon\Carbon::SUNDAY);
                            $weekEnd = $monthEnd->copy()->endOfWeek(\Carbon\Carbon::SATURDAY);
                            $day = $weekStart->copy();
                        @endphp
                        
                        @while($day <= $weekEnd)
                            @if($day->dayOfWeek === 0)
                                <tr>
                            @endif
                            
                            @php
                                $dateKey = $day->format('Y-m-d');
                                $isInContract = $day >= $startDate && $day <= $endDate;
                                $isCurrentMonth = $day->month === $currentMonth->month;
                                $isSunday = $day->dayOfWeek === 0;
                                $isToday = $day->isSameDay($today);
                                $isPast = $day < $today;
                                $isFuture = $day > $today;
                                
                                // Get payment for this date
                                $dayPayments = $paymentsByDate->get($dateKey, collect());
                                $totalPaidOnDay = $dayPayments->sum('amount_paid');
                                $expectedAmount = $contract->daily_payment;
                                
                                // Determine status
                                $status = 'none';
                                if ($isInContract && !$isSunday) {
                                    if ($totalPaidOnDay >= $expectedAmount) {
                                        $status = 'paid';
                                    } elseif ($totalPaidOnDay > 0) {
                                        $status = 'partial';
                                    } elseif ($isToday) {
                                        $status = 'today';
                                    } elseif ($isPast) {
                                        $status = 'overdue';
                                    } else {
                                        $status = 'upcoming';
                                    }
                                } elseif ($isInContract && $isSunday) {
                                    $status = 'rest';
                                }
                                
                                // Determine cell class
                                $cellClass = '';
                                $textClass = '';
                                if (!$isCurrentMonth) {
                                    $cellClass = 'bg-light text-muted';
                                } elseif ($status === 'paid') {
                                    $cellClass = 'bg-success bg-opacity-25';
                                    $textClass = 'text-success';
                                } elseif ($status === 'partial') {
                                    $cellClass = 'bg-warning bg-opacity-25';
                                    $textClass = 'text-warning';
                                } elseif ($status === 'overdue') {
                                    $cellClass = 'bg-danger bg-opacity-25';
                                    $textClass = 'text-danger';
                                } elseif ($status === 'today') {
                                    $cellClass = 'bg-primary bg-opacity-25 border-primary';
                                    $textClass = 'text-primary';
                                } elseif ($status === 'rest') {
                                    $cellClass = 'bg-secondary bg-opacity-10';
                                    $textClass = 'text-muted';
                                }
                            @endphp
                            
                            <td class="calendar-day {{ $cellClass }}" style="height: 90px; vertical-align: top;">
                                <div class="d-flex flex-column h-100">
                                    <div class="d-flex justify-content-between align-items-start mb-1">
                                        <span class="fw-bold {{ $isToday ? 'badge bg-primary' : '' }}" style="font-size: 14px;">
                                            {{ $day->day }}
                                        </span>
                                        @if($isInContract && !$isSunday && $isCurrentMonth)
                                            @if($status === 'paid')
                                                <span class="material-symbols-outlined text-success" style="font-size: 16px;">check_circle</span>
                                            @elseif($status === 'partial')
                                                <span class="material-symbols-outlined text-warning" style="font-size: 16px;">pending</span>
                                            @elseif($status === 'overdue')
                                                <span class="material-symbols-outlined text-danger" style="font-size: 16px;">error</span>
                                            @endif
                                        @endif
                                    </div>
                                    
                                    @if($isInContract && $isCurrentMonth)
                                        @if($isSunday)
                                            <small class="text-muted fst-italic">Rest Day</small>
                                        @else
                                            <div class="mt-auto">
                                                @if($totalPaidOnDay > 0)
                                                    <small class="{{ $textClass }} fw-bold d-block">
                                                        ₦{{ number_format($totalPaidOnDay, 0) }}
                                                    </small>
                                                    @if($status === 'paid' && $totalPaidOnDay > $expectedAmount)
                                                        <small class="text-success" style="font-size: 10px;">
                                                            +₦{{ number_format($totalPaidOnDay - $expectedAmount, 0) }}
                                                        </small>
                                                    @elseif($status === 'partial')
                                                        <small class="text-danger" style="font-size: 10px;">
                                                            -₦{{ number_format($expectedAmount - $totalPaidOnDay, 0) }}
                                                        </small>
                                                    @endif
                                                @else
                                                    <small class="{{ $textClass }}" style="font-size: 11px;">
                                                        ₦{{ number_format($expectedAmount, 0) }}
                                                    </small>
                                                @endif
                                            </div>
                                        @endif
                                    @endif
                                </div>
                            </td>
                            
                            @if($day->dayOfWeek === 6)
                                </tr>
                            @endif
                            
                            @php $day->addDay(); @endphp
                        @endwhile
                    </tbody>
                </table>
            </div>
            
            <!-- Month Summary -->
            @php
                $monthPayments = $contract->payments->filter(function($payment) use ($currentMonth) {
                    return \Carbon\Carbon::parse($payment->payment_date)->month === $currentMonth->month 
                        && \Carbon\Carbon::parse($payment->payment_date)->year === $currentMonth->year;
                });
                $monthTotal = $monthPayments->sum('amount_paid');
                $monthExpected = 0;
                $tempDay = $currentMonth->copy()->startOfMonth();
                while ($tempDay->month === $currentMonth->month) {
                    if ($tempDay >= $startDate && $tempDay <= $endDate && $tempDay->dayOfWeek !== 0) {
                        $monthExpected += $contract->daily_payment;
                    }
                    $tempDay->addDay();
                }
            @endphp
            <div class="card-footer bg-light">
                <div class="row text-center">
                    <div class="col-md-4">
                        <small class="text-muted d-block">Expected This Month</small>
                        <strong>₦{{ number_format($monthExpected, 2) }}</strong>
                    </div>
                    <div class="col-md-4">
                        <small class="text-muted d-block">Collected This Month</small>
                        <strong class="text-success">₦{{ number_format($monthTotal, 2) }}</strong>
                    </div>
                    <div class="col-md-4">
                        <small class="text-muted d-block">Difference</small>
                        @php $diff = $monthTotal - $monthExpected; @endphp
                        <strong class="{{ $diff >= 0 ? 'text-success' : 'text-danger' }}">
                            {{ $diff >= 0 ? '+' : '' }}₦{{ number_format($diff, 2) }}
                        </strong>
                    </div>
                </div>
            </div>
        </div>
        
        @php $currentMonth->addMonth(); @endphp
    @endwhile

    <!-- Overall Summary -->
    <div class="card bg-dark text-white">
        <div class="card-body">
            <div class="row text-center">
                <div class="col-md-2">
                    <small class="text-white-50 d-block">Contract Duration</small>
                    <strong class="fs-5">{{ $contract->total_payment_days }} Days</strong>
                </div>
                <div class="col-md-2">
                    <small class="text-white-50 d-block">Total Amount</small>
                    <strong class="fs-5">₦{{ number_format($contract->total_amount, 2) }}</strong>
                </div>
                <div class="col-md-2">
                    <small class="text-white-50 d-block">Total Paid</small>
                    <strong class="fs-5 text-success">₦{{ number_format($contract->total_paid, 2) }}</strong>
                </div>
                <div class="col-md-2">
                    <small class="text-white-50 d-block">Outstanding</small>
                    <strong class="fs-5 text-warning">₦{{ number_format($contract->outstanding_balance, 2) }}</strong>
                </div>
                <div class="col-md-2">
                    <small class="text-white-50 d-block">Days Remaining</small>
                    <strong class="fs-5">{{ max(0, $contract->days_remaining) }}</strong>
                </div>
                <div class="col-md-2">
                    <small class="text-white-50 d-block">Status</small>
                    @php
                        $statusColors = [
                            'pending' => 'warning',
                            'active' => 'info',
                            'completed' => 'success',
                            'defaulted' => 'danger',
                            'terminated' => 'secondary',
                        ];
                    @endphp
                    <span class="badge bg-{{ $statusColors[$contract->status] ?? 'secondary' }} fs-6">
                        {{ ucfirst($contract->status) }}
                    </span>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    @media print {
        .btn, nav, .sidebar, header, .breadcrumb {
            display: none !important;
        }
        .card {
            break-inside: avoid;
            page-break-inside: avoid;
        }
        .calendar-month {
            page-break-after: always;
        }
        .calendar-month:last-of-type {
            page-break-after: avoid;
        }
    }
    
    .calendar-table td {
        transition: all 0.2s ease;
    }
    
    .calendar-table td:hover {
        transform: scale(1.02);
        box-shadow: 0 2px 8px rgba(0,0,0,0.15);
        z-index: 1;
        position: relative;
    }
</style>
@endsection
