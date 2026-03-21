@extends('layouts.app')

@section('title', 'Edit Hire Purchase Contract')

@section('content')
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-4">
        <div>
            <h3 class="mb-1">
                <i class="ri-edit-line me-2"></i>Edit Contract: {{ $hirePurchase->contract_number }}
            </h3>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('admin.hire-purchase.index') }}">Hire Purchase</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('admin.hire-purchase.show', $hirePurchase) }}">{{ $hirePurchase->contract_number }}</a></li>
                    <li class="breadcrumb-item active">Edit</li>
                </ol>
            </nav>
        </div>
        <a href="{{ route('admin.hire-purchase.show', $hirePurchase) }}" class="btn btn-outline-secondary">
            <i class="ri-arrow-left-line me-1"></i>Back to Contract
        </a>
    </div>

    @if(session('error'))
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <i class="ri-error-warning-line me-2"></i>{{ session('error') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    @endif

    <form action="{{ route('admin.hire-purchase.update', $hirePurchase) }}" method="POST">
        @csrf
        @method('PUT')

        <div class="row">
            <div class="col-lg-8">
                <!-- Contract Info (Read-only) -->
                <div class="card bg-white border-0 rounded-3 mb-4">
                    <div class="card-body p-4">
                        <h5 class="mb-4"><i class="ri-file-list-3-line me-2"></i>Contract Information</h5>

                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Contract Number</label>
                                <input type="text" class="form-control" value="{{ $hirePurchase->contract_number }}" disabled>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">Status <span class="text-danger">*</span></label>
                                <select class="form-select @error('status') is-invalid @enderror" name="status" required>
                                    <option value="active" {{ old('status', $hirePurchase->status) == 'active' ? 'selected' : '' }}>Active</option>
                                    <option value="suspended" {{ old('status', $hirePurchase->status) == 'suspended' ? 'selected' : '' }}>Suspended</option>
                                    <option value="terminated" {{ old('status', $hirePurchase->status) == 'terminated' ? 'selected' : '' }}>Terminated</option>
                                    <option value="completed" {{ old('status', $hirePurchase->status) == 'completed' ? 'selected' : '' }}>Completed</option>
                                    <option value="defaulted" {{ old('status', $hirePurchase->status) == 'defaulted' ? 'selected' : '' }}>Defaulted</option>
                                </select>
                                @error('status')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">Driver</label>
                                <input type="text" class="form-control" value="{{ $hirePurchase->driver->full_name }} ({{ $hirePurchase->driver->phone_number }})" disabled>
                                <small class="text-muted">Driver cannot be changed after contract creation</small>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">Vehicle</label>
                                <input type="text" class="form-control" value="{{ $hirePurchase->vehicle->plate_number }} - {{ $hirePurchase->vehicle->make }} {{ $hirePurchase->vehicle->model }}" disabled>
                                <small class="text-muted">Vehicle cannot be changed after contract creation</small>
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
                                    name="vehicle_price" value="{{ old('vehicle_price', $hirePurchase->vehicle_price) }}" step="0.01" min="1"
                                    required id="vehiclePrice">
                                @error('vehicle_price')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                                <small class="text-muted">Total cost of the vehicle</small>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">Down Payment (₦)</label>
                                <input type="number" class="form-control @error('down_payment') is-invalid @enderror"
                                    name="down_payment" value="{{ old('down_payment', $hirePurchase->down_payment) }}" step="0.01" min="0"
                                    id="downPayment">
                                @error('down_payment')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                                <small class="text-muted">Initial payment made by driver</small>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">Minimum Daily Payment (₦) <span class="text-danger">*</span></label>
                                <input type="number" class="form-control @error('daily_payment') is-invalid @enderror"
                                    name="daily_payment" value="{{ old('daily_payment', $hirePurchase->daily_payment) }}" step="0.01" min="1"
                                    required id="dailyPayment">
                                @error('daily_payment')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                                <small class="text-muted">Minimum amount driver must pay daily</small>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">Payment Duration (Days) <span class="text-danger">*</span></label>
                                <input type="number" class="form-control @error('total_payment_days') is-invalid @enderror"
                                    name="total_payment_days" value="{{ old('total_payment_days', $hirePurchase->total_payment_days) }}" min="1"
                                    required id="totalDays">
                                @error('total_payment_days')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                                <small class="text-muted">Total number of days to complete payment</small>
                            </div>
                        </div>

                        <!-- Current Payment Status -->
                        <div class="bg-light rounded-3 p-4 mt-4">
                            <h6 class="mb-3"><i class="ri-bar-chart-line me-2"></i>Current Payment Status</h6>
                            <div class="row g-3">
                                <div class="col-md-3">
                                    <small class="text-muted d-block">Total Paid</small>
                                    <strong class="text-success">₦{{ number_format($hirePurchase->total_paid) }}</strong>
                                </div>
                                <div class="col-md-3">
                                    <small class="text-muted d-block">Current Balance</small>
                                    <strong class="text-danger">₦{{ number_format($hirePurchase->total_balance) }}</strong>
                                </div>
                                <div class="col-md-3">
                                    <small class="text-muted d-block">Payments Made</small>
                                    <strong>{{ $hirePurchase->payments_made }} days</strong>
                                </div>
                                <div class="col-md-3">
                                    <small class="text-muted d-block">Progress</small>
                                    <strong>{{ $hirePurchase->progress_percentage }}%</strong>
                                </div>
                            </div>
                        </div>

                        <!-- Calculator Preview -->
                        <div class="bg-info bg-opacity-10 rounded-3 p-4 mt-4" id="calculatorPreview">
                            <h6 class="mb-3"><i class="ri-calculator-line me-2"></i>Updated Calculation Preview</h6>
                            <div class="row g-3">
                                <div class="col-md-3">
                                    <small class="text-muted d-block">New Vehicle Price</small>
                                    <strong id="calcVehiclePrice">₦0</strong>
                                </div>
                                <div class="col-md-3">
                                    <small class="text-muted d-block">Down Payment</small>
                                    <strong class="text-info" id="calcDownPayment">₦0</strong>
                                </div>
                                <div class="col-md-3">
                                    <small class="text-muted d-block">Total Amount Due</small>
                                    <strong class="text-primary" id="calcFinance">₦0</strong>
                                </div>
                                <div class="col-md-3">
                                    <small class="text-muted d-block">New Balance</small>
                                    <strong class="text-danger" id="calcNewBalance">₦0</strong>
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
                                    name="start_date" value="{{ old('start_date', $hirePurchase->start_date->format('Y-m-d')) }}" required
                                    id="startDate">
                                @error('start_date')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">Grace Period (Days)</label>
                                <input type="number"
                                    class="form-control @error('grace_period_days') is-invalid @enderror"
                                    name="grace_period_days" value="{{ old('grace_period_days', $hirePurchase->grace_period_days) }}" min="0">
                                @error('grace_period_days')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                                <small class="text-muted">Days after due date before penalty applies</small>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">Late Fee Percentage (%)</label>
                                <input type="number"
                                    class="form-control @error('late_fee_percentage') is-invalid @enderror"
                                    name="late_fee_percentage" value="{{ old('late_fee_percentage', $hirePurchase->late_fee_percentage) }}"
                                    step="0.01" min="0" max="100">
                                @error('late_fee_percentage')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                                <small class="text-muted">Percentage of daily payment as penalty</small>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">Late Fee Fixed (₦)</label>
                                <input type="number" class="form-control @error('late_fee_fixed') is-invalid @enderror"
                                    name="late_fee_fixed" value="{{ old('late_fee_fixed', $hirePurchase->late_fee_fixed) }}" step="0.01"
                                    min="0">
                                @error('late_fee_fixed')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                                <small class="text-muted">Fixed amount penalty for late payment</small>
                            </div>

                            <div class="col-12">
                                <label class="form-label">Notes</label>
                                <textarea class="form-control @error('notes') is-invalid @enderror" name="notes" rows="3"
                                    placeholder="Additional contract notes...">{{ old('notes', $hirePurchase->notes) }}</textarea>
                                @error('notes')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-12">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="regenerate_schedule" value="1" id="regenerateSchedule">
                                    <label class="form-check-label" for="regenerateSchedule">
                                        <strong>Regenerate Payment Schedule</strong>
                                        <br><small class="text-muted">Check this to delete all pending/overdue payments and regenerate the schedule based on new parameters. Paid payments will be preserved.</small>
                                    </label>
                                </div>
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
                                <strong id="summaryPrice">₦{{ number_format($hirePurchase->vehicle_price) }}</strong>
                            </div>
                            <div class="d-flex justify-content-between mb-2">
                                <span class="text-muted">Down Payment:</span>
                                <strong class="text-info" id="summaryDown">₦{{ number_format($hirePurchase->down_payment) }}</strong>
                            </div>
                            <hr>
                            <div class="d-flex justify-content-between mb-2">
                                <span class="text-muted">Total Amount:</span>
                                <strong class="text-primary" id="summaryTotal">₦{{ number_format($hirePurchase->total_amount) }}</strong>
                            </div>
                            <div class="d-flex justify-content-between mb-2">
                                <span class="text-muted">Already Paid:</span>
                                <strong class="text-success">₦{{ number_format($hirePurchase->total_paid) }}</strong>
                            </div>
                            <hr>
                            <div class="d-flex justify-content-between">
                                <span class="text-muted">New Balance:</span>
                                <strong class="text-danger" id="summaryBalance">₦{{ number_format($hirePurchase->total_balance) }}</strong>
                            </div>
                        </div>

                        <div class="mb-4">
                            <div class="d-flex justify-content-between mb-2">
                                <span class="text-muted">Daily Payment:</span>
                                <strong id="summaryDaily">₦{{ number_format($hirePurchase->daily_payment) }}</strong>
                            </div>
                            <div class="d-flex justify-content-between mb-2">
                                <span class="text-muted">Duration:</span>
                                <strong id="summaryDuration">{{ $hirePurchase->total_payment_days }} days</strong>
                            </div>
                            <div class="d-flex justify-content-between">
                                <span class="text-muted">Expected End:</span>
                                <strong id="summaryEndDate">{{ $hirePurchase->expected_end_date->format('M d, Y') }}</strong>
                            </div>
                        </div>

                        <div class="alert alert-warning mb-4">
                            <i class="ri-information-line me-2"></i>
                            <small>Changes to financial parameters will recalculate the remaining balance based on payments already made.</small>
                        </div>

                        <button type="submit" class="btn btn-primary w-100 btn-lg">
                            <i class="ri-check-line me-2"></i>Update Contract
                        </button>

                        <a href="{{ route('admin.hire-purchase.show', $hirePurchase) }}" class="btn btn-outline-secondary w-100 mt-2">
                            Cancel
                        </a>
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
            const totalPaid = {{ $hirePurchase->total_paid }};

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

                const totalAmount = price - down;
                const newBalance = Math.max(0, totalAmount - totalPaid);
                const endDate = calculateEndDate(start, days);

                // Update calculator preview
                document.getElementById('calcVehiclePrice').textContent = formatCurrency(price);
                document.getElementById('calcDownPayment').textContent = formatCurrency(down);
                document.getElementById('calcFinance').textContent = formatCurrency(totalAmount);
                document.getElementById('calcNewBalance').textContent = formatCurrency(newBalance);

                // Update summary
                document.getElementById('summaryPrice').textContent = formatCurrency(price);
                document.getElementById('summaryDown').textContent = formatCurrency(down);
                document.getElementById('summaryTotal').textContent = formatCurrency(totalAmount);
                document.getElementById('summaryBalance').textContent = formatCurrency(newBalance);
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
