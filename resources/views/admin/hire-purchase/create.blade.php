@extends('layouts.app')

@section('title', 'Create Hire Purchase Contract')

@section('content')
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-4">
        <div>
            <h3 class="mb-1">
                <i class="ri-add-circle-line me-2"></i>New Hire Purchase Contract
            </h3>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('admin.hire-purchase.index') }}">Hire Purchase</a></li>
                    <li class="breadcrumb-item active">New Contract</li>
                </ol>
            </nav>
        </div>
        <a href="{{ route('admin.hire-purchase.index') }}" class="btn btn-outline-secondary">
            <i class="ri-arrow-left-line me-1"></i>Back
        </a>
    </div>

    <form action="{{ route('admin.hire-purchase.store') }}" method="POST">
        @csrf

        <div class="row">
            <div class="col-lg-8">
                <!-- Driver & Vehicle Selection -->
                <div class="card bg-white border-0 rounded-3 mb-4">
                    <div class="card-body p-4">
                        <h5 class="mb-4"><i class="ri-user-line me-2"></i>Driver & Vehicle</h5>

                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Select Driver <span class="text-danger">*</span></label>
                                <select class="form-select @error('driver_id') is-invalid @enderror" name="driver_id"
                                    required>
                                    <option value="">-- Select Driver --</option>
                                    @foreach ($drivers as $driver)
                                        <option value="{{ $driver->id }}"
                                            {{ old('driver_id') == $driver->id ? 'selected' : '' }}>
                                            {{ $driver->full_name }} ({{ $driver->phone_number }})
                                        </option>
                                    @endforeach
                                </select>
                                @error('driver_id')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                                <small class="text-muted">Only drivers marked for hire purchase without active contracts are
                                    shown</small>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">Select Vehicle <span class="text-danger">*</span></label>
                                <select class="form-select @error('vehicle_id') is-invalid @enderror" name="vehicle_id"
                                    required>
                                    <option value="">-- Select Vehicle --</option>
                                    @foreach ($vehicles as $vehicle)
                                        <option value="{{ $vehicle->id }}"
                                            {{ old('vehicle_id') == $vehicle->id ? 'selected' : '' }}>
                                            {{ $vehicle->plate_number }} - {{ $vehicle->make }} {{ $vehicle->model }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('vehicle_id')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                                <small class="text-muted">Only vehicles without active hire purchase contracts are
                                    shown</small>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Financial Details -->
                <div class="card bg-white border-0 rounded-3 mb-4">
                    <div class="card-body p-4">
                        <h5 class="mb-4"><i class="ri-money-dollar-circle-line me-2"></i>Financial Details</h5>

                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Vehicle Price (₦) <span class="text-danger">*</span></label>
                                <input type="number" class="form-control @error('vehicle_price') is-invalid @enderror"
                                    name="vehicle_price" value="{{ old('vehicle_price') }}" step="0.01" min="1"
                                    required id="vehiclePrice">
                                @error('vehicle_price')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                                <small class="text-muted">Total cost of the vehicle</small>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">Down Payment (₦)</label>
                                <input type="number" class="form-control @error('down_payment') is-invalid @enderror"
                                    name="down_payment" value="{{ old('down_payment', 0) }}" step="0.01" min="0"
                                    id="downPayment">
                                @error('down_payment')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                                <small class="text-muted">Initial payment made by driver (optional)</small>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">Minimum Daily Payment (₦) <span
                                        class="text-danger">*</span></label>
                                <input type="number" class="form-control @error('daily_payment') is-invalid @enderror"
                                    name="daily_payment" value="{{ old('daily_payment') }}" step="0.01" min="1"
                                    required id="dailyPayment">
                                @error('daily_payment')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                                <small class="text-muted">Minimum amount driver must pay daily. Driver can pay more to
                                    finish earlier.</small>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">Payment Duration (Days) <span class="text-danger">*</span></label>
                                <input type="number" class="form-control @error('total_payment_days') is-invalid @enderror"
                                    name="total_payment_days" value="{{ old('total_payment_days') }}" min="1"
                                    required id="totalDays">
                                @error('total_payment_days')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                                <small class="text-muted">Total number of days to complete payment</small>
                            </div>
                        </div>

                        <!-- Calculator Preview -->
                        <div class="bg-light rounded-3 p-4 mt-4" id="calculatorPreview">
                            <h6 class="mb-3"><i class="ri-calculator-line me-2"></i>Payment Calculator</h6>
                            <div class="row g-3">
                                <div class="col-md-3">
                                    <small class="text-muted d-block">Vehicle Price</small>
                                    <strong id="calcVehiclePrice">₦0</strong>
                                </div>
                                <div class="col-md-3">
                                    <small class="text-muted d-block">Down Payment</small>
                                    <strong class="text-info" id="calcDownPayment">₦0</strong>
                                </div>
                                <div class="col-md-3">
                                    <small class="text-muted d-block">Amount to Finance</small>
                                    <strong class="text-primary" id="calcFinance">₦0</strong>
                                </div>
                                <div class="col-md-3">
                                    <small class="text-muted d-block">Expected Total</small>
                                    <strong class="text-success" id="calcTotal">₦0</strong>
                                </div>
                            </div>
                            <hr>
                            <div class="row g-3">
                                <div class="col-md-4">
                                    <small class="text-muted d-block">Daily Payment</small>
                                    <strong id="calcDaily">₦0/day</strong>
                                </div>
                                <div class="col-md-4">
                                    <small class="text-muted d-block">Duration</small>
                                    <strong id="calcDuration">0 days</strong>
                                </div>
                                <div class="col-md-4">
                                    <small class="text-muted d-block">Estimated Completion</small>
                                    <strong id="calcEndDate">-</strong>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Contract Settings -->
                <div class="card bg-white border-0 rounded-3 mb-4">
                    <div class="card-body p-4">
                        <h5 class="mb-4"><i class="ri-settings-3-line me-2"></i>Contract Settings</h5>

                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Start Date <span class="text-danger">*</span></label>
                                <input type="date" class="form-control @error('start_date') is-invalid @enderror"
                                    name="start_date" value="{{ old('start_date', date('Y-m-d')) }}" required
                                    id="startDate">
                                @error('start_date')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">Grace Period (Days)</label>
                                <input type="number"
                                    class="form-control @error('grace_period_days') is-invalid @enderror"
                                    name="grace_period_days" value="{{ old('grace_period_days', 0) }}" min="0">
                                @error('grace_period_days')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                                <small class="text-muted">Days after due date before penalty applies</small>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">Late Fee Percentage (%)</label>
                                <input type="number"
                                    class="form-control @error('late_fee_percentage') is-invalid @enderror"
                                    name="late_fee_percentage" value="{{ old('late_fee_percentage', 0) }}"
                                    step="0.01" min="0" max="100">
                                @error('late_fee_percentage')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                                <small class="text-muted">Percentage of daily payment as penalty</small>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">Late Fee Fixed (₦)</label>
                                <input type="number" class="form-control @error('late_fee_fixed') is-invalid @enderror"
                                    name="late_fee_fixed" value="{{ old('late_fee_fixed', 0) }}" step="0.01"
                                    min="0">
                                @error('late_fee_fixed')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                                <small class="text-muted">Fixed amount penalty for late payment</small>
                            </div>

                            <div class="col-12">
                                <label class="form-label">Notes</label>
                                <textarea class="form-control @error('notes') is-invalid @enderror" name="notes" rows="3"
                                    placeholder="Additional contract notes...">{{ old('notes') }}</textarea>
                                @error('notes')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-4">
                <!-- Summary Card -->
                <div class="card bg-white border-0 rounded-3 mb-4 sticky-top" style="top: 80px;">
                    <div class="card-body p-4">
                        <h5 class="mb-4"><i class="ri-file-list-3-line me-2"></i>Contract Summary</h5>

                        <div class="bg-light rounded-3 p-3 mb-4">
                            <div class="d-flex justify-content-between mb-2">
                                <span class="text-muted">Vehicle Price:</span>
                                <strong id="summaryPrice">₦0</strong>
                            </div>
                            <div class="d-flex justify-content-between mb-2">
                                <span class="text-muted">Down Payment:</span>
                                <strong class="text-info" id="summaryDown">₦0</strong>
                            </div>
                            <hr>
                            <div class="d-flex justify-content-between">
                                <span class="text-muted">Amount to Finance:</span>
                                <strong class="text-primary" id="summaryFinance">₦0</strong>
                            </div>
                        </div>

                        <div class="mb-4">
                            <div class="d-flex justify-content-between mb-2">
                                <span class="text-muted">Daily Payment:</span>
                                <strong id="summaryDaily">₦0</strong>
                            </div>
                            <div class="d-flex justify-content-between mb-2">
                                <span class="text-muted">Duration:</span>
                                <strong id="summaryDuration">0 days</strong>
                            </div>
                            <div class="d-flex justify-content-between">
                                <span class="text-muted">End Date:</span>
                                <strong id="summaryEndDate">-</strong>
                            </div>
                        </div>

                        <div class="alert alert-warning mb-4">
                            <i class="ri-information-line me-2"></i>
                            <small>Please review all details before creating the contract. This action will immediately
                                activate the hire purchase agreement.</small>
                        </div>

                        <button type="submit" class="btn btn-success w-100 btn-lg">
                            <i class="ri-check-line me-2"></i>Create Contract
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </form>
@endsection

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const vehiclePrice = document.getElementById('vehiclePrice');
            const downPayment = document.getElementById('downPayment');
            const dailyPayment = document.getElementById('dailyPayment');
            const totalDays = document.getElementById('totalDays');
            const startDate = document.getElementById('startDate');

            function formatCurrency(amount) {
                return '₦' + parseFloat(amount || 0).toLocaleString('en-NG', {
                    minimumFractionDigits: 0,
                    maximumFractionDigits: 0
                });
            }

            function calculateEndDate(start, days) {
                if (!start || !days) return '-';
                const date = new Date(start);
                date.setDate(date.getDate() + parseInt(days));
                return date.toLocaleDateString('en-NG', {
                    month: 'short',
                    day: 'numeric',
                    year: 'numeric'
                });
            }

            function updateCalculations() {
                const price = parseFloat(vehiclePrice.value) || 0;
                const down = parseFloat(downPayment.value) || 0;
                const daily = parseFloat(dailyPayment.value) || 0;
                const days = parseInt(totalDays.value) || 0;
                const start = startDate.value;

                const finance = price - down;
                const total = daily * days;
                const endDate = calculateEndDate(start, days);

                // Update calculator preview
                document.getElementById('calcVehiclePrice').textContent = formatCurrency(price);
                document.getElementById('calcDownPayment').textContent = formatCurrency(down);
                document.getElementById('calcFinance').textContent = formatCurrency(finance);
                document.getElementById('calcTotal').textContent = formatCurrency(total);
                document.getElementById('calcDaily').textContent = formatCurrency(daily) + '/day';
                document.getElementById('calcDuration').textContent = days + ' days';
                document.getElementById('calcEndDate').textContent = endDate;

                // Update summary
                document.getElementById('summaryPrice').textContent = formatCurrency(price);
                document.getElementById('summaryDown').textContent = formatCurrency(down);
                document.getElementById('summaryFinance').textContent = formatCurrency(finance);
                document.getElementById('summaryDaily').textContent = formatCurrency(daily);
                document.getElementById('summaryDuration').textContent = days + ' days';
                document.getElementById('summaryEndDate').textContent = endDate;
            }

            // Add event listeners
            [vehiclePrice, downPayment, dailyPayment, totalDays, startDate].forEach(input => {
                input.addEventListener('input', updateCalculations);
                input.addEventListener('change', updateCalculations);
            });

            // Initial calculation
            updateCalculations();
        });
    </script>
@endpush
