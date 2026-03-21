# Rich Filtering System - Implementation Guide

## Overview
This guide explains how to implement the rich filtering system across all modules in the eRide Management System.

## Features
- **Time-based filters**: Daily, Weekly, Monthly, Yearly, Custom Date Range
- **Branch filtering**: Filter by specific branches (Super Admin only)
- **Type/Status filtering**: Filter by transaction types, statuses, etc.
- **Category filtering**: Filter by predefined categories
- **Search functionality**: Search across multiple fields
- **Filter persistence**: Filters persist across pagination

## Implementation Steps

### 1. Use the HasDateFilters Trait

Add the trait to your controller:

```php
<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Traits\HasDateFilters;
use Illuminate\Http\Request;

class YourController extends Controller
{
    use HasDateFilters;
    
    public function index(Request $request)
    {
        // Get filter parameters
        $timeFilter = $request->get('time_filter', 'monthly');
        $startDate = $request->get('start_date');
        $endDate = $request->get('end_date');
        
        // Get date range using the trait
        [$start, $end] = $this->getDateRange($timeFilter, $startDate, $endDate);
        
        // Build your query
        $data = YourModel::query()
            ->when($start && $end, function ($q) use ($start, $end) {
                $q->whereBetween('date_column', [$start, $end]);
            })
            ->paginate(20)
            ->withQueryString();
            
        return view('your.view', compact('data', 'timeFilter', 'startDate', 'endDate'));
    }
}
```

### 2. Add Date Range Scope to Your Model

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class YourModel extends Model
{
    /**
     * Scope for date range filter
     */
    public function scopeDateRange($query, $start, $end)
    {
        return $query->whereBetween('your_date_column', [$start, $end]);
    }
}
```

### 3. Add Filter UI to Your View

```blade
<!-- Filters -->
<div class="card bg-white border-0 rounded-3 mb-4">
    <div class="card-body p-4">
        <form method="GET" action="{{ route('your.route') }}" id="filterForm">
            <div class="row g-3">
                <!-- Time Period Filter -->
                <div class="col-lg-2 col-md-4">
                    <label class="form-label">Time Period</label>
                    <select class="form-select" name="time_filter" id="timeFilter">
                        <option value="daily" {{ $timeFilter == 'daily' ? 'selected' : '' }}>Today</option>
                        <option value="weekly" {{ $timeFilter == 'weekly' ? 'selected' : '' }}>This Week</option>
                        <option value="monthly" {{ $timeFilter == 'monthly' ? 'selected' : '' }}>This Month</option>
                        <option value="yearly" {{ $timeFilter == 'yearly' ? 'selected' : '' }}>This Year</option>
                        <option value="custom" {{ $timeFilter == 'custom' ? 'selected' : '' }}>Custom Range</option>
                    </select>
                </div>

                <!-- Start Date (shown only for custom) -->
                <div class="col-lg-2 col-md-4" id="startDateDiv" style="display: {{ $timeFilter == 'custom' ? 'block' : 'none' }};">
                    <label class="form-label">Start Date</label>
                    <input type="date" class="form-control" name="start_date" value="{{ $startDate }}">
                </div>

                <!-- End Date (shown only for custom) -->
                <div class="col-lg-2 col-md-4" id="endDateDiv" style="display: {{ $timeFilter == 'custom' ? 'block' : 'none' }};">
                    <label class="form-label">End Date</label>
                    <input type="date" class="form-control" name="end_date" value="{{ $endDate }}">
                </div>

                <!-- Additional Filters Here -->
                
                <!-- Submit Buttons -->
                <div class="col-lg-3 col-md-4 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary me-2">
                        <i class="ri-filter-line me-1"></i> Apply Filters
                    </button>
                    <a href="{{ route('your.route') }}" class="btn btn-outline-secondary">
                        <i class="ri-refresh-line"></i> Reset
                    </a>
                </div>
            </div>
        </form>
    </div>
</div>
```

### 4. Add JavaScript for Dynamic Filters

```blade
@push('scripts')
<script>
// Handle time filter changes
document.getElementById('timeFilter').addEventListener('change', function() {
    const customDivs = ['startDateDiv', 'endDateDiv'];
    if (this.value === 'custom') {
        customDivs.forEach(id => {
            document.getElementById(id).style.display = 'block';
        });
    } else {
        customDivs.forEach(id => {
            document.getElementById(id).style.display = 'none';
        });
    }
});
</script>
@endpush
```

## Available Filter Types

### 1. Time-based Filters
- **daily**: Today's data
- **weekly**: This week's data (Monday to Sunday)
- **monthly**: This month's data
- **yearly**: This year's data
- **custom**: Custom date range (requires start_date and end_date)

### 2. Branch Filter (Super Admin)
```php
->when($branchId, function ($q) use ($branchId) {
    $q->where('branch_id', $branchId);
})
->when(!$user->hasRole('Super Admin'), function ($q) use ($user) {
    $q->where('branch_id', $user->branch_id);
})
```

### 3. Status/Type Filter
```php
->when($status, function ($q) use ($status) {
    $q->where('status', $status);
})
```

### 4. Category Filter
```php
->when($category, function ($q) use ($category) {
    $q->where('category', $category);
})
```

### 5. Search Filter
```php
->when($search, function ($q) use ($search) {
    $q->where(function ($query) use ($search) {
        $query->where('field1', 'like', "%{$search}%")
              ->orWhere('field2', 'like', "%{$search}%")
              ->orWhere('field3', 'like', "%{$search}%");
    });
})
```

## Best Practices

1. **Always use `withQueryString()`** on pagination to preserve filters
2. **Set default time filter** to 'monthly' for better performance
3. **Validate date inputs** to prevent invalid queries
4. **Use indexed columns** for better query performance
5. **Cache statistics** when dealing with large datasets
6. **Provide clear labels** for all filter options
7. **Show active filters** to users for better UX

## Example Modules to Update

Apply these filters to:
- ✅ Company Account (Already implemented)
- [ ] Daily Balances
- [ ] Maintenance Requests
- [ ] Charging Requests
- [ ] Wallet Funding Requests
- [ ] Payments
- [ ] Vehicle Assignments
- [ ] Transactions

## Trait Methods

### `getDateRange($timeFilter, $startDate, $endDate)`
Returns an array with start and end Carbon dates based on the time filter.

### `getTimeFilterOptions()`
Returns an array of available time filter options for dropdowns.

### `getDateRangeLabel($timeFilter, $start, $end)`
Returns a formatted string label for the current date range.

## Performance Tips

1. **Add indexes** to date columns:
```php
$table->index('transaction_date');
$table->index(['branch_id', 'transaction_date']);
```

2. **Use eager loading** for relationships:
```php
->with(['branch', 'user', 'relatedModel'])
```

3. **Limit results** with pagination:
```php
->paginate(20)
```

4. **Cache statistics** for dashboard:
```php
Cache::remember('stats_' . $timeFilter . '_' . $branchId, 300, function() {
    return $this->calculateStats();
});
```

## Support

For questions or issues, refer to:
- `app/Traits/HasDateFilters.php` - Main trait
- `app/Http/Controllers/Admin/CompanyAccountController.php` - Reference implementation
- `resources/views/admin/company-account/index.blade.php` - UI reference
