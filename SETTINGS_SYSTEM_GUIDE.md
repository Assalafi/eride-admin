# eRide System Settings Guide

## Overview
The Settings system provides a flexible, database-backed configuration management solution for the eRide Transport Management System. All settings are cached for performance and can be easily managed through the admin interface.

## Database Structure

### System Settings Table
```
id              - Primary key
key             - Unique setting identifier
value           - Setting value (stored as text)
type            - Setting type (text, number, boolean, file)
description     - Setting description
created_at      - Timestamp
updated_at      - Timestamp
```

## Default Settings

### 1. **System Settings**
- **system_name**: "eRide Transport Management"
- **company_email**: "info@eride.ng"
- **company_phone**: "+234 xxx xxx xxxx"
- **company_address**: "Maiduguri, Borno State, Nigeria"
- **currency**: "NGN"
- **currency_symbol**: "₦"

### 2. **Financial Settings**
- **daily_remittance_amount**: 15000 (in Naira)
- **charging_per_session**: 5000 (in Naira)

### 3. **System Preferences**
- **maintenance_approval_required**: true
- **payment_approval_required**: true

### 4. **File Settings**
- **system_logo**: Path to uploaded logo file

## Usage

### Using Helper Functions

#### Get a Setting Value
```php
// Simple usage
$systemName = setting('system_name');

// With default value
$sessionCharge = setting('charging_per_session', 5000);

// In Blade templates
<h1>{{ setting('system_name') }}</h1>
<p>Daily Remittance: ₦{{ number_format(setting('daily_remittance_amount'), 2) }}</p>
```

#### Set a Setting Value
```php
// Set text setting
set_setting('system_name', 'New eRide System', 'text', 'System name');

// Set number setting
set_setting('daily_remittance_amount', 20000, 'number', 'Daily remittance');

// Set boolean setting
set_setting('maintenance_approval_required', 1, 'boolean', 'Require approval');
```

### Using the Model Directly

#### Get Setting
```php
use App\Models\SystemSetting;

// Get single setting
$value = SystemSetting::get('system_name');

// Get all settings
$allSettings = SystemSetting::getAll();
```

#### Set Setting
```php
SystemSetting::set('new_setting', 'value', 'text', 'Description');
```

#### Clear Cache
```php
SystemSetting::clearCache();
```

## Admin Interface

### Accessing Settings
1. Login as Super Admin
2. Navigate to **Settings** in the sidebar (under SYSTEM section)
3. Update settings as needed
4. Click "Save Settings"

### Settings Sections

#### 1. General Settings
All text-based settings:
- System Name
- Company Email
- Company Phone
- Company Address
- Currency
- Currency Symbol

#### 2. Financial Settings
All number-based settings:
- Daily Remittance Amount
- Charging Per Session

#### 3. System Preferences
All boolean toggle settings:
- Maintenance Approval Required
- Payment Approval Required

#### 4. System Logo
File upload for system logo:
- Supported formats: JPG, PNG, SVG
- Max size: 2MB
- Live preview before upload

## Adding New Settings

### Via Migration
```php
DB::table('system_settings')->insert([
    'key' => 'new_setting_key',
    'value' => 'default_value',
    'type' => 'text', // text, number, boolean, file
    'description' => 'Setting description',
    'created_at' => now(),
    'updated_at' => now(),
]);
```

### Via Code
```php
SystemSetting::set('new_setting', 'value', 'text', 'Description');
```

### Via Admin Interface
The interface automatically displays all settings from the database grouped by type.

## Setting Types

### 1. **text**
- String values
- Examples: system_name, company_email, company_address
- Input: Text field

### 2. **number**
- Numeric values
- Examples: daily_remittance_amount, charging_per_session
- Input: Number field with currency prefix
- Validation: min:0, step:0.01

### 3. **boolean**
- True/False values (stored as 1/0)
- Examples: maintenance_approval_required, payment_approval_required
- Input: Toggle switch
- Display: Checked = 1 (true), Unchecked = 0 (false)

### 4. **file**
- File paths
- Examples: system_logo
- Input: File upload field
- Stored in: storage/app/public/logos/

## Caching System

### How it Works
- Settings are cached for 1 hour (3600 seconds)
- Cache keys: `setting_{key}` for individual settings
- Cache key: `all_settings` for all settings
- Cache automatically cleared when settings are updated

### Manual Cache Clear
```php
// Clear all settings cache
SystemSetting::clearCache();

// Laravel cache clear
php artisan cache:clear
```

## Integration Examples

### 1. Daily Remittance Validation
```php
$dailyAmount = setting('daily_remittance_amount', 15000);

if ($payment->amount < $dailyAmount) {
    return back()->with('error', 'Payment must be at least ₦' . number_format($dailyAmount, 2));
}
```

### 2. Maintenance Approval Check
```php
if (setting('maintenance_approval_required', 1)) {
    // Require manager approval
    $maintenanceRequest->status = 'pending';
} else {
    // Auto-approve
    $maintenanceRequest->status = 'approved';
}
```

### 3. Dynamic Charging
```php
$chargingAmount = setting('charging_per_session', 5000);

Transaction::create([
    'driver_id' => $driver->id,
    'type' => 'debit',
    'amount' => $chargingAmount,
    'description' => 'Charging session fee',
]);
```

### 4. Email Configuration
```php
Mail::to(setting('company_email'))
    ->send(new ContactFormMail($data));
```

### 5. Display Logo
```blade
@php
    $logo = setting('system_logo');
@endphp

@if($logo)
    <img src="{{ asset('storage/' . $logo) }}" alt="{{ setting('system_name') }}">
@else
    <h2>{{ setting('system_name') }}</h2>
@endif
```

## Security

### Access Control
- Only **Super Admin** can access settings
- Protected by `role:Super Admin` middleware
- All updates logged via updated_at timestamp

### File Upload Security
- File type validation (images only for logo)
- File size limit (2MB max)
- Files stored in secure storage directory
- Automatic file naming

### Input Validation
- CSRF protection
- Type-based validation
- Min/max constraints for numbers
- Required field validation

## Performance Considerations

### Caching Benefits
- Reduces database queries
- Improves page load times
- Automatic cache invalidation on updates

### Best Practices
1. Use helper functions for frequently accessed settings
2. Cache settings in controller constructors if needed
3. Don't bypass cache for performance-critical operations
4. Set appropriate cache duration (default: 1 hour)

### When to Avoid Caching
- During testing/development
- When settings change very frequently
- For real-time configuration requirements

## Troubleshooting

### Issue: Setting not updating
**Solution**: Clear cache with `SystemSetting::clearCache()` or `php artisan cache:clear`

### Issue: Logo not displaying
**Solution**: 
1. Check storage link: `php artisan storage:link`
2. Verify file exists in storage/app/public/logos/
3. Check file permissions

### Issue: Boolean setting always false
**Solution**: Ensure checkbox input has value="1" and check old() value properly

### Issue: Cache not clearing
**Solution**: Use `php artisan cache:clear` or change cache driver in .env

## API Integration

### For Mobile App
```php
// API endpoint to get settings
Route::get('/api/settings', function() {
    return response()->json([
        'daily_remittance' => setting('daily_remittance_amount'),
        'charging_per_session' => setting('charging_per_session'),
        'company_info' => [
            'name' => setting('system_name'),
            'email' => setting('company_email'),
            'phone' => setting('company_phone'),
            'address' => setting('company_address'),
        ],
    ]);
});
```

## Extending the System

### Add New Setting Type
1. Add type to migration
2. Update controller validation
3. Add UI component in view
4. Update documentation

### Add Settings Category
1. Group settings by type in migration
2. Add new card section in view
3. Update controller to handle new group

### Add Settings Export/Import
```php
// Export settings
$settings = SystemSetting::all()->toJson();
file_put_contents('settings.json', $settings);

// Import settings
$settings = json_decode(file_get_contents('settings.json'));
foreach ($settings as $setting) {
    SystemSetting::set($setting->key, $setting->value, $setting->type, $setting->description);
}
```

## Backup & Restore

### Backup Settings
```bash
# Via database backup
mysqldump -u username -p database_name system_settings > settings_backup.sql

# Via Laravel
php artisan tinker
>>> \App\Models\SystemSetting::all()->toJson()
```

### Restore Settings
```bash
# Via database restore
mysql -u username -p database_name < settings_backup.sql

# Via code
SystemSetting::insert($backupData);
```

---

**Last Updated**: October 8, 2025
**System Version**: eRide Transport Management System v1.0
