# Account Privilege System - Setup Guide

## ✅ Files Created

### 1. Database Files
- ✅ `database/migrations/2025_10_20_162500_create_account_debit_requests_table.php`
- ✅ `database/migrations/2025_10_20_162501_add_account_settings.php`
- ✅ `database/seeders/AddAccountPrivilegeRoleSeeder.php`

### 2. Backend Files
- ✅ `app/Models/AccountDebitRequest.php`
- ✅ `app/Http/Controllers/AccountController.php` (Updated with permissions)
- ✅ `app/Models/CompanyAccountTransaction.php` (Updated with debit_request category)

### 3. Routes File
- ✅ `routes/accounts_routes.php`

### 4. Documentation
- ✅ `ACCOUNT_PRIVILEGE_SYSTEM.md`
- ✅ `ACCOUNT_SETUP_GUIDE.md` (This file)

---

## 🚀 Installation Steps

### Step 1: Run Migrations
```bash
cd "/Applications/XAMPP/xamppfiles/htdocs/eRide system/eRide_admin_web"
php artisan migrate
```

This will create:
- `account_debit_requests` table
- Add `company_account_balance` setting (₦0)
- Add `debit_approval_threshold` setting (₦100,000)

### Step 2: Run Seeder
```bash
php artisan db:seed --class=AddAccountPrivilegeRoleSeeder
```

This will create:
- **7 new permissions**:
  - `view company account`
  - `create debit request`
  - `view debit requests`
  - `approve debit requests`
  - `manage account settings`
  - `view account transactions`
  - `export account reports`

- **New role**: Accountant
- **Updated roles**: CEO, Admin, Branch Manager with account permissions

### Step 3: Include Routes
Add this line to `routes/web.php`:

```php
// At the top with other use statements
use Illuminate\Support\Facades\Route;

// After existing routes, add:
require __DIR__.'/accounts_routes.php';
```

**Or** copy the contents of `routes/accounts_routes.php` directly into `routes/web.php`.

---

## 🎭 Roles & Permissions

### Accountant Role (New)
**Can:**
- ✅ View company account balance
- ✅ Create debit requests
- ✅ View all debit requests (own branch)
- ✅ View transaction history

**Cannot:**
- ❌ Approve debit requests
- ❌ Modify account settings

### Admin & CEO Roles (Updated)
**Can do everything:**
- ✅ View company account
- ✅ Create debit requests
- ✅ **Approve/reject debit requests** ⭐
- ✅ View all branches' requests
- ✅ Manage account settings
- ✅ View all transactions
- ✅ Export reports

### Branch Manager Role (Updated)
**Can:**
- ✅ View company account
- ✅ Create debit requests
- ✅ View debit requests (own branch)
- ✅ View transactions

**Cannot:**
- ❌ Approve debit requests

---

## 📋 Create Users with Accountant Role

### Option 1: In Database/Seeder
```php
$accountant = User::create([
    'name' => 'John Accountant',
    'email' => 'accountant@eride.ng',
    'password' => bcrypt('password'),
    'branch_id' => 1,
]);
$accountant->assignRole('Accountant');
```

### Option 2: Via Admin Panel
1. Go to Users Management
2. Create new user
3. Assign "Accountant" role
4. Assign to a branch

---

## 🔗 Navigation Menu

Add to your sidebar (e.g., `resources/views/layouts/sidebar.blade.php`):

```blade
@can('view company account')
<li class="nav-item">
    <a class="nav-link" href="{{ route('accounts.index') }}">
        <i class="fas fa-wallet"></i>
        <span>Company Account</span>
    </a>
</li>
@endcan
```

Or with submenu:

```blade
@can('view company account')
<li class="nav-item">
    <a class="nav-link collapsed" href="#" data-toggle="collapse" data-target="#collapseAccounts">
        <i class="fas fa-wallet"></i>
        <span>Company Account</span>
    </a>
    <div id="collapseAccounts" class="collapse" data-parent="#accordionSidebar">
        <div class="bg-white py-2 collapse-inner rounded">
            <a class="collapse-item" href="{{ route('accounts.index') }}">Dashboard</a>
            @can('create debit request')
            <a class="collapse-item" href="{{ route('accounts.debit-requests.create') }}">New Debit Request</a>
            @endcan
            <a class="collapse-item" href="{{ route('accounts.debit-requests.index') }}">Debit Requests</a>
            <a class="collapse-item" href="{{ route('accounts.transactions') }}">Transactions</a>
        </div>
    </div>
</li>
@endcan
```

---

## 📊 Update Settings Page

Add to your settings page to allow editing the threshold:

```blade
<div class="form-group">
    <label for="debit_approval_threshold">Debit Approval Threshold (₦)</label>
    <input type="number" 
           class="form-control" 
           id="debit_approval_threshold" 
           name="debit_approval_threshold" 
           value="{{ $settings->debit_approval_threshold ?? 100000 }}"
           min="0" 
           step="1000">
    <small class="form-text text-muted">
        Debit requests above this amount require Admin/CEO approval
    </small>
</div>
```

---

## 🧪 Testing Workflow

### Test as Accountant:
1. Login as accountant
2. Go to Company Account → New Debit Request
3. Enter amount: ₦50,000
4. Description: "Office supplies purchase"
5. Upload receipt (optional)
6. Submit → Status should be "Pending"

### Test as Admin/CEO:
1. Login as Admin or CEO
2. Go to Company Account → Debit Requests
3. Click on the pending request
4. Review details
5. Approve with notes: "Approved for office supplies"
6. Check:
   - ✅ Request status changed to "Approved"
   - ✅ Company balance decreased by ₦50,000
   - ✅ Transaction created with reference "DR-1"

### Test Authorization:
1. Try to approve as accountant → Should get "403 Forbidden"
2. Try to approve own request → Should get error message
3. Branch user should only see their branch requests

---

## 📱 Next: Create Views

You need to create these Blade views:

### 1. Dashboard (`resources/views/accounts/index.blade.php`)
```blade
@extends('layouts.app')
@section('content')
<div class="container-fluid">
    <h1 class="h3 mb-4">Company Account</h1>
    
    <!-- Balance Card -->
    <div class="card border-left-success mb-4">
        <div class="card-body">
            <div class="row no-gutters align-items-center">
                <div class="col mr-2">
                    <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                        Company Account Balance
                    </div>
                    <div class="h2 mb-0 font-weight-bold text-gray-800">
                        ₦{{ number_format($balance, 2) }}
                    </div>
                </div>
                <div class="col-auto">
                    <i class="fas fa-wallet fa-3x text-gray-300"></i>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Monthly Stats Row -->
    <div class="row mb-4">
        <div class="col-md-6">
            <div class="card border-left-primary">
                <div class="card-body">
                    <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                        This Month Income
                    </div>
                    <div class="h5 mb-0 font-weight-bold text-gray-800">
                        ₦{{ number_format($monthlyIncome, 2) }}
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card border-left-danger">
                <div class="card-body">
                    <div class="text-xs font-weight-bold text-danger text-uppercase mb-1">
                        This Month Expenses
                    </div>
                    <div class="h5 mb-0 font-weight-bold text-gray-800">
                        ₦{{ number_format($monthlyExpense, 2) }}
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Pending Requests Alert -->
    @if($pendingRequests->count() > 0)
    <div class="alert alert-warning">
        <i class="fas fa-exclamation-triangle"></i>
        You have {{ $pendingRequests->count() }} pending debit request(s) awaiting approval.
        <a href="{{ route('accounts.debit-requests.index', ['status' => 'pending']) }}" class="alert-link">
            View Requests
        </a>
    </div>
    @endif
    
    <!-- Recent Transactions -->
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h6 class="m-0 font-weight-bold text-primary">Recent Transactions</h6>
            <a href="{{ route('accounts.transactions') }}" class="btn btn-sm btn-primary">View All</a>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Branch</th>
                            <th>Type</th>
                            <th>Category</th>
                            <th>Description</th>
                            <th class="text-right">Amount</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($transactions as $transaction)
                        <tr>
                            <td>{{ $transaction->transaction_date->format('M d, Y') }}</td>
                            <td>{{ $transaction->branch->name }}</td>
                            <td>
                                <span class="badge badge-{{ $transaction->type === 'income' ? 'success' : 'danger' }}">
                                    {{ ucfirst($transaction->type) }}
                                </span>
                            </td>
                            <td>{{ str_replace('_', ' ', ucwords($transaction->category, '_')) }}</td>
                            <td>{{ Str::limit($transaction->description, 40) }}</td>
                            <td class="text-right font-weight-bold text-{{ $transaction->type === 'income' ? 'success' : 'danger' }}">
                                {{ $transaction->type === 'income' ? '+' : '-' }}₦{{ number_format($transaction->amount, 2) }}
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="6" class="text-center text-muted">No transactions yet</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
```

### 2. Create Debit Request (`resources/views/accounts/create-debit-request.blade.php`)
### 3. Debit Requests List (`resources/views/accounts/debit-requests.blade.php`)
### 4. Request Details (`resources/views/accounts/show-debit-request.blade.php`)
### 5. Transactions List (`resources/views/accounts/transactions.blade.php`)

---

## 📞 Support

For questions or issues:
- Review `ACCOUNT_PRIVILEGE_SYSTEM.md` for detailed documentation
- Check Laravel logs: `storage/logs/laravel.log`
- Verify permissions: `php artisan permission:cache-reset`

---

## ✅ Checklist

- [ ] Run migrations
- [ ] Run seeder
- [ ] Add routes to web.php
- [ ] Add navigation menu item
- [ ] Create views (5 files)
- [ ] Create test accountant user
- [ ] Test create debit request
- [ ] Test approval workflow
- [ ] Update settings page
- [ ] Test authorization

---

**You're ready to implement the frontend views! Would you like me to create all 5 Blade views next?**
