@extends('layouts.app')

@section('title', 'OPay Payments')

@section('content')
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-4">
        <div>
            <h3 class="mb-1">OPay Payments</h3>
            <p class="text-muted mb-0">Every checkout created by the driver app, including its latest OPay status.</p>
        </div>
        <a href="{{ route('admin.payments.index') }}" class="btn btn-outline-secondary">
            <i class="ri-arrow-left-line me-1"></i>All Payments
        </a>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-xl col-md-6">
            <div class="card bg-white border-0 rounded-3 h-100"><div class="card-body p-4">
                <small class="text-muted d-block mb-1">Total OPay checkouts</small>
                <h4 class="mb-0">{{ number_format($summary['total']) }}</h4>
            </div></div>
        </div>
        <div class="col-xl col-md-6">
            <div class="card bg-white border-0 rounded-3 h-100"><div class="card-body p-4">
                <small class="text-muted d-block mb-1">Pending / initial</small>
                <h4 class="mb-0 text-warning">{{ number_format($summary['pending']) }}</h4>
            </div></div>
        </div>
        <div class="col-xl col-md-6">
            <div class="card bg-white border-0 rounded-3 h-100"><div class="card-body p-4">
                <small class="text-muted d-block mb-1">Successful</small>
                <h4 class="mb-0 text-success">{{ number_format($summary['successful']) }}</h4>
            </div></div>
        </div>
        <div class="col-xl col-md-6">
            <div class="card bg-white border-0 rounded-3 h-100"><div class="card-body p-4">
                <small class="text-muted d-block mb-1">Failed / closed</small>
                <h4 class="mb-0 text-danger">{{ number_format($summary['failed']) }}</h4>
            </div></div>
        </div>
        <div class="col-xl col-md-6">
            <div class="card bg-white border-0 rounded-3 h-100"><div class="card-body p-4">
                <small class="text-muted d-block mb-1">Successful amount</small>
                <h4 class="mb-0">₦{{ number_format($summary['amount'], 2) }}</h4>
            </div></div>
        </div>
    </div>

    <div class="card bg-white border-0 rounded-3 mb-4">
        <div class="card-body p-4">
            <form method="GET" action="{{ route('admin.payments.opay.index') }}">
                <div class="row g-3 align-items-end">
                    <div class="col-lg-2 col-md-4">
                        <label class="form-label">Status</label>
                        <select class="form-select" name="status">
                            <option value="">All statuses</option>
                            @foreach(['INITIAL', 'PENDING', 'SUCCESS', 'FAIL', 'CLOSE'] as $option)
                                <option value="{{ $option }}" @selected($status === $option)>{{ $option }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-lg-2 col-md-4">
                        <label class="form-label">Environment</label>
                        <select class="form-select" name="environment">
                            <option value="">Live and demo</option>
                            <option value="live" @selected($environment === 'live')>Live</option>
                            <option value="demo" @selected($environment === 'demo')>Demo</option>
                        </select>
                    </div>
                    <div class="col-lg-3 col-md-4">
                        <label class="form-label">Driver</label>
                        <select class="form-select" name="driver_id">
                            <option value="">All drivers</option>
                            @foreach($drivers as $driver)
                                <option value="{{ $driver->id }}" @selected((string) $driverId === (string) $driver->id)>{{ $driver->full_name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-lg-2 col-md-4">
                        <label class="form-label">Reference</label>
                        <input class="form-control" name="reference" value="{{ $reference }}" placeholder="ERIDE-...">
                    </div>
                    <div class="col-lg-1 col-md-4">
                        <label class="form-label">From</label>
                        <input type="date" class="form-control" name="date_from" value="{{ $dateFrom }}">
                    </div>
                    <div class="col-lg-1 col-md-4">
                        <label class="form-label">To</label>
                        <input type="date" class="form-control" name="date_to" value="{{ $dateTo }}">
                    </div>
                    <div class="col-lg-1 col-md-4 d-flex gap-2">
                        <button class="btn btn-primary" type="submit" title="Filter"><i class="ri-filter-line"></i></button>
                        <a class="btn btn-outline-secondary" href="{{ route('admin.payments.opay.index') }}" title="Reset"><i class="ri-refresh-line"></i></a>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <div class="card bg-white border-0 rounded-3">
        <div class="card-body p-4">
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
                <h4 class="mb-0">OPay checkout history</h4>
                <small class="text-muted">Verify any payment that is not successful.</small>
            </div>
            <div class="table-responsive">
                <table class="table align-middle">
                    <thead>
                        <tr>
                            <th>Driver</th>
                            <th>Reference</th>
                            <th>Purpose</th>
                            <th>Environment</th>
                            <th>Amount</th>
                            <th>Status</th>
                            <th>OPay status</th>
                            <th>Date</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($payments as $payment)
                            @php
                                $statusClass = match ($payment->status) {
                                    'SUCCESS' => 'bg-success',
                                    'FAIL', 'CLOSE' => 'bg-danger',
                                    default => 'bg-warning text-dark',
                                };
                                $opayStatus = strtoupper((string) data_get($payment->gateway_response, 'data.status', $payment->status));
                            @endphp
                            <tr>
                                <td>
                                    <strong>{{ $payment->driver?->full_name ?? 'Unknown driver' }}</strong><br>
                                    <small class="text-muted">{{ $payment->driver?->phone_number ?? 'N/A' }}</small>
                                </td>
                                <td><code>{{ $payment->reference }}</code></td>
                                <td>{{ str_replace('_', ' ', ucwords($payment->purpose)) }}</td>
                                <td><span class="badge {{ $payment->environment === 'demo' ? 'bg-warning text-dark' : 'bg-success' }}">{{ strtoupper($payment->environment) }}</span></td>
                                <td class="fw-semibold">₦{{ number_format((float) $payment->amount, 2) }}</td>
                                <td><span class="badge {{ $statusClass }}">{{ $payment->status }}</span></td>
                                <td><span class="badge bg-light text-dark border">{{ $opayStatus }}</span></td>
                                <td>{{ $payment->created_at?->format('M d, Y H:i') ?? 'N/A' }}</td>
                                <td>
                                    @if($payment->status !== 'SUCCESS')
                                        <form method="POST" action="{{ route('admin.payments.opay.verify', $payment) }}" onsubmit="return confirm('Ask OPay for the latest status of this payment?')">
                                            @csrf
                                            <button type="submit" class="btn btn-sm btn-outline-primary" title="Verify with OPay">
                                                <i class="ri-refresh-line me-1"></i>Verify
                                            </button>
                                        </form>
                                    @else
                                        <span class="text-success small"><i class="ri-checkbox-circle-line me-1"></i>Confirmed</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="9" class="text-center text-muted py-5">No OPay payments found.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if($payments->hasPages())
                <div class="mt-3">{{ $payments->links() }}</div>
            @endif
        </div>
    </div>
@endsection
