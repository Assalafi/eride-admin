@extends('layouts.app')

@section('title', 'System Settings')

@section('content')
<div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-4">
    <h3 class="mb-0">System Settings</h3>

    <nav style="--bs-breadcrumb-divider: '>';" aria-label="breadcrumb">
        <ol class="breadcrumb align-items-center mb-0 lh-1">
            <li class="breadcrumb-item">
                <a href="{{ route('dashboard') }}" class="d-flex align-items-center text-decoration-none">
                    <i class="ri-home-4-line fs-18 text-primary me-1"></i>
                    <span class="text-secondary fw-medium hover">Dashboard</span>
                </a>
            </li>
            <li class="breadcrumb-item active" aria-current="page">
                <span class="fw-medium">Settings</span>
            </li>
        </ol>
    </nav>
</div>

<form action="{{ route('admin.settings.update') }}" method="POST" enctype="multipart/form-data">
    @csrf
    @method('PUT')

    <div class="row">
        <!-- General Settings -->
        <div class="col-lg-8">
            <div class="card bg-white border-0 rounded-3 mb-4">
                <div class="card-body p-4">
                    <h4 class="mb-4">
                        <span class="material-symbols-outlined me-2" style="vertical-align: middle;">settings</span>
                        General Settings
                    </h4>

                    @php
                        $generalSettings = $settings->get('text', collect());
                    @endphp

                    @foreach($generalSettings as $setting)
                    <div class="mb-3">
                        <label for="{{ $setting->key }}" class="form-label fw-semibold">
                            {{ ucwords(str_replace('_', ' ', $setting->key)) }}
                        </label>
                        <input type="text" 
                               class="form-control" 
                               id="{{ $setting->key }}" 
                               name="settings[{{ $setting->key }}]" 
                               value="{{ old('settings.' . $setting->key, $setting->value) }}">
                        @if($setting->description)
                        <small class="text-muted">{{ $setting->description }}</small>
                        @endif
                    </div>
                    @endforeach
                </div>
            </div>

            <!-- OPay Payment Gateway -->
            <div class="card bg-white border-0 rounded-3 mb-4">
                <div class="card-body p-4">
                    <h4 class="mb-2">
                        <span class="material-symbols-outlined me-2" style="vertical-align: middle;">account_balance</span>
                        OPay Payment Gateway
                    </h4>
                    <p class="text-muted mb-4">Control the OPay Cashier experience from here. Switching environments changes the OPay account and endpoint used for new checkouts; the driver app does not need to be rebuilt.</p>

                    @php
                        $opaySettings = $settings->get('payment', collect())->keyBy('key');
                        $opayEnvironmentFields = [
                            'live' => [
                                'title' => 'Live settings & credentials',
                                'subtitle' => 'Real OPay payments. Configure the complete production checkout here.',
                                'fields' => [
                                    'opay_live_display_name' => ['label' => 'Merchant display name', 'type' => 'text'],
                                    'opay_live_country' => ['label' => 'Country code', 'type' => 'text'],
                                    'opay_live_currency' => ['label' => 'Currency', 'type' => 'text'],
                                    'opay_live_return_url' => ['label' => 'Return URL', 'type' => 'url'],
                                    'opay_live_cancel_url' => ['label' => 'Cancel URL', 'type' => 'url'],
                                    'opay_live_callback_url' => ['label' => 'Callback URL', 'type' => 'url'],
                                    'opay_live_public_key' => ['label' => 'Public key', 'type' => 'text'],
                                    'opay_live_secret_key' => ['label' => 'Secret key', 'type' => 'password'],
                                    'opay_live_merchant_id' => ['label' => 'Merchant ID', 'type' => 'text'],
                                    'opay_live_base_url' => ['label' => 'API base URL', 'type' => 'url'],
                                ],
                            ],
                            'demo' => [
                                'title' => 'Demo / sandbox settings & credentials',
                                'subtitle' => 'Test payments only. Configure the complete OPay staging checkout here.',
                                'fields' => [
                                    'opay_demo_display_name' => ['label' => 'Merchant display name', 'type' => 'text'],
                                    'opay_demo_country' => ['label' => 'Country code', 'type' => 'text'],
                                    'opay_demo_currency' => ['label' => 'Currency', 'type' => 'text'],
                                    'opay_demo_return_url' => ['label' => 'Return URL', 'type' => 'url'],
                                    'opay_demo_cancel_url' => ['label' => 'Cancel URL', 'type' => 'url'],
                                    'opay_demo_callback_url' => ['label' => 'Callback URL', 'type' => 'url'],
                                    'opay_demo_public_key' => ['label' => 'Public key', 'type' => 'text'],
                                    'opay_demo_secret_key' => ['label' => 'Secret key', 'type' => 'password'],
                                    'opay_demo_merchant_id' => ['label' => 'Merchant ID', 'type' => 'text'],
                                    'opay_demo_base_url' => ['label' => 'API base URL', 'type' => 'url'],
                                ],
                            ],
                        ];
                    @endphp

                    <div class="row g-3 mb-4">
                        <div class="col-md-6">
                            @php $environment = $opaySettings->get('opay_environment'); @endphp
                            @if($environment)
                                <label for="opay_environment" class="form-label fw-semibold">Checkout environment</label>
                                <select class="form-select" id="opay_environment" name="settings[opay_environment]">
                                    <option value="live" @selected(old('settings.opay_environment', $environment->value) === 'live')>Live - real payments</option>
                                    <option value="demo" @selected(old('settings.opay_environment', $environment->value) === 'demo')>Demo - sandbox payments</option>
                                </select>
                                <small class="text-muted">Only new checkouts use this selection. Existing payments keep the environment used when they were created.</small>
                            @endif
                        </div>
                        <div class="col-md-6">
                            @php $enabled = $settings->get('boolean', collect())->firstWhere('key', 'opay_enabled'); @endphp
                            @if($enabled)
                                <label for="opay_enabled" class="form-label fw-semibold">OPay checkout status</label>
                                <select class="form-select" id="opay_enabled" name="settings[opay_enabled]">
                                    <option value="1" @selected((string) old('settings.opay_enabled', $enabled->value) === '1')>Enabled</option>
                                    <option value="0" @selected((string) old('settings.opay_enabled', $enabled->value) === '0')>Disabled</option>
                                </select>
                                <small class="text-muted">Disable this to pause new driver payments without removing credentials.</small>
                            @endif
                        </div>
                    </div>

                    @foreach($opayEnvironmentFields as $environmentKey => $environmentGroup)
                        <div class="border rounded-3 p-3 mb-3">
                            <div class="d-flex justify-content-between align-items-start gap-2 mb-3">
                                <div>
                                    <h6 class="mb-1">{{ $environmentGroup['title'] }}</h6>
                                    <small class="text-muted">{{ $environmentGroup['subtitle'] }}</small>
                                </div>
                                <span class="badge {{ $environmentKey === 'live' ? 'text-bg-success' : 'text-bg-warning' }}">
                                    {{ strtoupper($environmentKey) }}
                                </span>
                            </div>
                            <div class="row g-3">
                                @foreach($environmentGroup['fields'] as $key => $field)
                                    @php $setting = $opaySettings->get($key); @endphp
                                    @if($setting)
                                        <div class="col-md-6">
                                            <label for="{{ $key }}" class="form-label fw-semibold">{{ $field['label'] }}</label>
                                            <input type="{{ $field['type'] }}"
                                                   class="form-control"
                                                   id="{{ $key }}"
                                                   name="settings[{{ $key }}]"
                                                   value="{{ $field['type'] === 'password' ? '' : old('settings.' . $key, $setting->value) }}"
                                                   placeholder="{{ $field['type'] === 'password' ? ($setting->value ? 'Configured - leave blank to keep it' : 'Enter secret key') : '' }}"
                                                   autocomplete="{{ $field['type'] === 'password' ? 'new-password' : 'off' }}"
                                                   {{ $field['type'] === 'url' ? 'inputmode=url' : '' }}>
                                            <small class="text-muted">{{ $setting->description }}</small>
                                        </div>
                                    @endif
                                @endforeach
                            </div>
                        </div>
                    @endforeach

                    <div class="alert alert-info mb-0">
                        <strong>Security:</strong> Secret keys are stored server-side and are never sent to the driver app. Demo and live credentials are kept separately.
                    </div>
                </div>
            </div>

            <!-- Financial Settings -->
            <div class="card bg-white border-0 rounded-3 mb-4">
                <div class="card-body p-4">
                    <h4 class="mb-4">
                        <span class="material-symbols-outlined me-2" style="vertical-align: middle;">payments</span>
                        Financial Settings
                    </h4>

                    @php
                        $numberSettings = $settings->get('number', collect());
                    @endphp

                    @foreach($numberSettings as $setting)
                    <div class="mb-3">
                        <label for="{{ $setting->key }}" class="form-label fw-semibold">
                            {{ ucwords(str_replace('_', ' ', $setting->key)) }}
                        </label>
                        <div class="input-group">
                            <span class="input-group-text">₦</span>
                            <input type="number" 
                                   class="form-control" 
                                   id="{{ $setting->key }}" 
                                   name="settings[{{ $setting->key }}]" 
                                   value="{{ old('settings.' . $setting->key, $setting->value) }}"
                                   step="0.01"
                                   min="0">
                        </div>
                        @if($setting->description)
                        <small class="text-muted">{{ $setting->description }}</small>
                        @endif
                    </div>
                    @endforeach
                </div>
            </div>

            <!-- System Preferences -->
            <div class="card bg-white border-0 rounded-3 mb-4">
                <div class="card-body p-4">
                    <h4 class="mb-4">
                        <span class="material-symbols-outlined me-2" style="vertical-align: middle;">tune</span>
                        System Preferences
                    </h4>

                    @php
                        $booleanSettings = $settings->get('boolean', collect());
                    @endphp

                    @foreach($booleanSettings as $setting)
                    @if($setting->key !== 'opay_enabled')
                    <div class="mb-3">
                        <div class="form-check form-switch">
                            <input type="hidden" name="settings[{{ $setting->key }}]" value="0">
                            <input class="form-check-input" 
                                   type="checkbox" 
                                   role="switch" 
                                   id="{{ $setting->key }}" 
                                   name="settings[{{ $setting->key }}]" 
                                   value="1"
                                   {{ old('settings.' . $setting->key, $setting->value) == '1' ? 'checked' : '' }}>
                            <label class="form-check-label fw-semibold" for="{{ $setting->key }}">
                                {{ ucwords(str_replace('_', ' ', $setting->key)) }}
                            </label>
                        </div>
                        @if($setting->description)
                        <small class="text-muted d-block ms-5">{{ $setting->description }}</small>
                        @endif
                    </div>
                    @endif
                    @endforeach
                </div>
            </div>

            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-primary">
                    <span class="material-symbols-outlined me-2" style="vertical-align: middle;">save</span>
                    Save Settings
                </button>
                <a href="{{ route('dashboard') }}" class="btn btn-secondary">
                    <span class="material-symbols-outlined me-2" style="vertical-align: middle;">cancel</span>
                    Cancel
                </a>
            </div>
        </div>

        <!-- Sidebar -->
        <div class="col-lg-4">
            <!-- Logo Upload -->
            <div class="card bg-white border-0 rounded-3 mb-4">
                <div class="card-body p-4">
                    <h5 class="mb-3">
                        <span class="material-symbols-outlined me-2" style="vertical-align: middle;">image</span>
                        System Logo
                    </h5>

                    <div class="text-center mb-3">
                        @if(app_logo())
                        <img src="{{ app_logo() }}" 
                             alt="System Logo" 
                             class="img-fluid rounded" 
                             id="logo-preview"
                             style="max-height: 150px;">
                        @else
                        <div class="bg-light rounded d-flex align-items-center justify-content-center" id="logo-preview" style="height: 150px;">
                            <span class="material-symbols-outlined text-muted" style="font-size: 64px;">image</span>
                        </div>
                        @endif
                    </div>

                    <div class="mb-3">
                        <label for="logo" class="form-label fw-semibold">Upload New Logo</label>
                        <input type="file" 
                               class="form-control" 
                               id="logo" 
                               name="logo" 
                               accept="image/*">
                        <small class="text-muted">Max size: 2MB. Formats: JPG, PNG, SVG</small>
                    </div>
                </div>
            </div>

            <!-- Favicon Upload -->
            <div class="card bg-white border-0 rounded-3 mb-4">
                <div class="card-body p-4">
                    <h5 class="mb-3">
                        <span class="material-symbols-outlined me-2" style="vertical-align: middle;">brand_awareness</span>
                        Browser Favicon
                    </h5>

                    <div class="text-center mb-3">
                        @if(setting('system_favicon'))
                        <img src="{{ asset('storage/' . setting('system_favicon')) }}" 
                             alt="Favicon" 
                             class="rounded" 
                             id="favicon-preview"
                             style="width: 64px; height: 64px; object-fit: contain;">
                        @else
                        <div class="bg-light rounded d-flex align-items-center justify-content-center mx-auto" id="favicon-preview" style="width: 64px; height: 64px;">
                            <span class="material-symbols-outlined text-muted" style="font-size: 32px;">favorite</span>
                        </div>
                        @endif
                    </div>

                    <div class="mb-3">
                        <label for="favicon" class="form-label fw-semibold">Upload New Favicon</label>
                        <input type="file" 
                               class="form-control" 
                               id="favicon" 
                               name="favicon" 
                               accept="image/*">
                        <small class="text-muted">Max size: 1MB. Formats: PNG, ICO (32x32 or 64x64)</small>
                    </div>
                </div>
            </div>

            <!-- Quick Info -->
            <div class="card bg-white border-0 rounded-3 mb-4">
                <div class="card-body p-4">
                    <h5 class="mb-3">
                        <span class="material-symbols-outlined me-2" style="vertical-align: middle;">info</span>
                        Information
                    </h5>
                    <p class="text-secondary mb-3">
                        <strong>Note:</strong> Changes to these settings will affect the entire system immediately.
                    </p>
                    <p class="text-secondary mb-3">
                        <strong>Daily Remittance:</strong> The default amount drivers are expected to pay daily.
                    </p>
                    <p class="text-secondary mb-0">
                        <strong>Charging Per Session:</strong> The amount charged for each charging session.
                    </p>
                </div>
            </div>

            <!-- Cache Info -->
            <div class="card bg-white border-0 rounded-3 mb-4">
                <div class="card-body p-4">
                    <h5 class="mb-3">
                        <span class="material-symbols-outlined me-2" style="vertical-align: middle;">cached</span>
                        Cache
                    </h5>
                    <p class="text-secondary mb-3">
                        Settings are cached for better performance. Cache is automatically cleared when you save settings.
                    </p>
                    <p class="text-muted mb-0 small">
                        <i class="ri-information-line me-1"></i>
                        Last updated: {{ now()->format('M d, Y H:i:s') }}
                    </p>
                </div>
            </div>
        </div>
    </div>
</form>
@endsection

@push('scripts')
<script>
    // Preview logo before upload
    document.getElementById('logo').addEventListener('change', function(e) {
        const file = e.target.files[0];
        if (file) {
            const reader = new FileReader();
            reader.onload = function(e) {
                const preview = document.getElementById('logo-preview');
                if (preview.tagName === 'IMG') {
                    preview.src = e.target.result;
                } else {
                    const newImg = document.createElement('img');
                    newImg.src = e.target.result;
                    newImg.id = 'logo-preview';
                    newImg.className = 'img-fluid rounded';
                    newImg.style.maxHeight = '150px';
                    newImg.alt = 'System Logo';
                    preview.parentNode.replaceChild(newImg, preview);
                }
            }
            reader.readAsDataURL(file);
        }
    });

    // Preview favicon before upload
    document.getElementById('favicon').addEventListener('change', function(e) {
        const file = e.target.files[0];
        if (file) {
            const reader = new FileReader();
            reader.onload = function(e) {
                const preview = document.getElementById('favicon-preview');
                if (preview.tagName === 'IMG') {
                    preview.src = e.target.result;
                } else {
                    const newImg = document.createElement('img');
                    newImg.src = e.target.result;
                    newImg.id = 'favicon-preview';
                    newImg.className = 'rounded';
                    newImg.style.width = '64px';
                    newImg.style.height = '64px';
                    newImg.style.objectFit = 'contain';
                    newImg.alt = 'Favicon';
                    preview.parentNode.replaceChild(newImg, preview);
                }
            }
            reader.readAsDataURL(file);
        }
    });
</script>
@endpush
