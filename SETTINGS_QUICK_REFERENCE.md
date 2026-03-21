# ⚡ Settings System - Quick Reference Card

## 🎯 Access Settings
**URL:** `/admin/settings`  
**Menu:** Dashboard → Settings (sidebar)  
**Permission:** Super Admin only

---

## 🛠️ Helper Functions

```php
// Get any setting
setting('key', 'default_value')

// System branding
app_name()              // "eRide Transport Management"
app_logo()              // "http://example.com/storage/logos/logo.png"
app_favicon()           // "http://example.com/storage/logos/favicon.png"

// Company info
company_email()         // "info@eride.ng"
company_phone()         // "+234 000 000 0000"
company_address()       // "Nigeria"
```

---

## 📝 Usage in Blade

```blade
<!-- Page title -->
<title>{{ app_name() }} - Dashboard</title>

<!-- Favicon -->
<link rel="icon" href="{{ app_favicon() }}">

<!-- Logo -->
@if(app_logo())
    <img src="{{ app_logo() }}" alt="{{ app_name() }}">
@else
    <span>{{ app_name() }}</span>
@endif

<!-- Any setting -->
<p>Daily Fee: ₦{{ setting('daily_remittance_amount', 5000) }}</p>
```

---

## 💾 Available Settings

| Key | Type | Default | Description |
|-----|------|---------|-------------|
| `system_name` | text | eRide Transport Management | System name |
| `company_email` | text | info@eride.ng | Contact email |
| `company_phone` | text | +234 000 000 0000 | Contact phone |
| `company_address` | text | Nigeria | Company address |
| `daily_remittance_amount` | number | 5000.00 | Daily driver payment |
| `charging_cost_per_session` | number | 2000.00 | Charging cost |
| `enable_maintenance` | boolean | 1 | Maintenance module |
| `enable_notifications` | boolean | 1 | Notifications |
| `require_manager_approval` | boolean | 1 | Approval required |
| `system_logo` | file | null | Logo path |
| `system_favicon` | file | null | Favicon path |

---

## 🔧 Programmatic Usage

```php
use App\Models\SystemSetting;

// Get value
$value = SystemSetting::get('key', 'default');

// Set value
SystemSetting::set('key', 'value', 'type', 'description');

// Get all settings
$all = SystemSetting::getAll();

// Clear cache
SystemSetting::clearCache();
```

---

## 🚀 Commands

```bash
# Populate default settings
php artisan db:seed --class=SystemSettingsSeeder

# Clear caches
php artisan cache:clear
php artisan config:clear
php artisan view:clear

# Reload helpers
composer dump-autoload

# Create storage link
php artisan storage:link
```

---

## 📁 File Locations

```
/app/Helpers/settings_helper.php          # Helper functions
/app/Models/SystemSetting.php             # Model
/app/Http/Controllers/Admin/SettingsController.php
/resources/views/admin/settings/index.blade.php
/database/seeders/SystemSettingsSeeder.php
/storage/app/public/logos/                # Logo storage
```

---

## ⚠️ Troubleshooting

| Issue | Solution |
|-------|----------|
| Settings not updating | `php artisan cache:clear` |
| Logo not showing | `php artisan storage:link` |
| Helper not found | `composer dump-autoload` |
| Favicon not changing | Clear browser cache (Ctrl+F5) |

---

## ✅ Quick Upload Specs

**Logo:**
- Formats: JPG, PNG, SVG
- Max size: 2MB
- Recommended: 200x50px PNG

**Favicon:**
- Formats: PNG, ICO
- Max size: 1MB
- Recommended: 64x64px PNG

---

## 📚 Full Documentation

- **User Guide:** HOW_TO_CUSTOMIZE_BRANDING.md
- **Developer Guide:** SYSTEM_SETTINGS_GUIDE.md
- **Implementation:** SETTINGS_IMPLEMENTATION_SUMMARY.md

---

**Version:** 1.0 | **Updated:** Oct 9, 2025
