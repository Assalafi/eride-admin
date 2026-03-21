# Account Privilege System - Implementation Guide

## Overview
This document outlines the account privilege system for eRide Transport Management System, allowing accountants to request debits from the company account with approval workflows.

## Features Implemented

### 1. Company Account Management
- **View Company Account Balance**: Real-time visibility of company funds
- **Transaction History**: Complete record of all income and expenses
- **Monthly Statistics**: Income and expense summaries
- **Multi-branch Support**: All branches under one company (eRide)

### 2. Debit Request System
- **Request Creation**: Accountants can request amounts with descriptions
- **Approval Workflow**: Admin/CEO approval for requests above threshold
- **Document Upload**: Attach receipts and supporting documents
- **Status Tracking**: Pending, Approved, Rejected statuses

### 3. Approval Threshold Settings
- **Configurable Threshold**: Set amount (default: ₦100,000) requiring approval
- **Automatic Processing**: Requests below threshold can be auto-processed
- **Role-based Access**: Only CEO and Admin can approve requests

### 4. Transaction Recording
- **Auto-Debit on Approval**: Approved requests automatically debit company account
- **Complete Audit Trail**: All transactions logged with references
- **Balance Updates**: Real-time balance calculations

## Database Structure

### Tables Created

#### 1. `account_debit_requests`
```sql
- id (primary key)
- branch_id (foreign key to branches)
- requested_by (foreign key to users)
- amount (decimal 12,2)
- description (text, required)
- status (enum: pending, approved, rejected)
- approved_by (foreign key to users, nullable)
- approval_notes (text, nullable)
- approved_at (timestamp, nullable)
- receipt_document (string, nullable)
- timestamps
```

#### 2. System Settings Added
- `company_account_balance`: Current balance (₦0 default)
- `debit_approval_threshold`: Approval threshold (₦100,000 default)

### Existing Tables Used
- `company_account_transactions`: Records all transactions
- `system_settings`: Stores configuration
- `users`: User management with roles
- `branches`: Branch information

## Models Created

### 1. AccountDebitRequest Model
**Location**: `app/Models/AccountDebitRequest.php`

**Relationships**:
- `branch()`: Belongs to Branch
- `requester()`: Belongs to User (who requested)
- `approver()`: Belongs to User (who approved/rejected)

**Scopes**:
- `pending()`: Get pending requests
- `approved()`: Get approved requests
- `rejected()`: Get rejected requests

**Attributes**:
- `statusColor`: Returns badge color for status

### 2. CompanyAccountTransaction Model (Updated)
**Location**: `app/Models/CompanyAccountTransaction.php`

**New Category Added**:
- `CATEGORY_DEBIT_REQUEST = 'debit_request'`

## Controller Implementation

### AccountController
**Location**: `app/Http/Controllers/AccountController.php`

#### Routes & Methods:

1. **Dashboard**
   - Route: `GET /accounts`
   - Method: `index()`
   - Shows: Balance, recent transactions, pending requests, monthly stats

2. **Create Debit Request**
   - Route: `GET /accounts/debit-requests/create`
   - Method: `createDebitRequest()`
   - Shows: Request form with threshold info

3. **Store Debit Request**
   - Route: `POST /accounts/debit-requests`
   - Method: `storeDebitRequest()`
   - Validates: Amount, description, optional receipt
   - Creates: New debit request with pending status

4. **View Debit Requests**
   - Route: `GET /accounts/debit-requests`
   - Method: `debitRequests()`
   - Filters: By status, branch
   - Shows: Paginated list of requests

5. **Show Request Details**
   - Route: `GET /accounts/debit-requests/{id}`
   - Method: `showDebitRequest()`
   - Shows: Full request details with documents

6. **Review Request (Approve/Reject)**
   - Route: `POST /accounts/debit-requests/{id}/review`
   - Method: `reviewDebitRequest()`
   - Actions: Approve or reject with notes
   - Authorization: CEO and Admin only
   - On Approval: Debits account and creates transaction

7. **View Transactions**
   - Route: `GET /accounts/transactions`
   - Method: `transactions()`
   - Filters: By type, date range, branch
   - Shows: Paginated transaction history

## Workflow Process

### Debit Request Flow:

```
1. Accountant → Creates debit request with description
   ↓
2. System → Saves as "pending" status
   ↓
3. Admin/CEO → Reviews request
   ↓
4. If Approved:
   - Check company balance (sufficient?)
   - Debit company account
   - Create transaction record
   - Update request status to "approved"
   - Record approver and timestamp
   ↓
5. If Rejected:
   - Update request status to "rejected"
   - Record rejection notes and approver
```

### Balance Calculation:
```
New Balance = Current Balance - Debit Amount
```

### Transaction Record:
```
- Type: expense
- Category: debit_request
- Reference: DR-{request_id}
- Amount: Request amount
- Description: From request
- Branch: Request branch
- Recorded by: Approver
```

## Security & Authorization

### Role Requirements:

**Accountant/Branch User**:
- Can create debit requests
- Can view own branch requests
- Cannot approve requests

**Admin/CEO**:
- Can view all branch requests
- Can approve/reject requests
- Can view all transactions
- Can access company balance

### Validation:
- Amount: Required, numeric, minimum ₦1
- Description: Required, max 1000 characters
- Receipt: Optional, PDF/JPG/PNG, max 2MB

### Authorization Checks:
- Branch users can only see their branch data
- Only pending requests can be processed
- Approvers cannot approve their own requests
- Sufficient balance check before debit

## Next Steps: Views Implementation

You need to create the following Blade views:

### 1. Dashboard View
**File**: `resources/views/accounts/index.blade.php`
- Display company balance (large, prominent)
- Show monthly income/expense cards
- List recent transactions (table)
- Show pending debit requests (alert/table)
- Quick action buttons

### 2. Create Debit Request
**File**: `resources/views/accounts/create-debit-request.blade.php`
- Form with amount input
- Description textarea
- Receipt upload field
- Display approval threshold info
- Submit button

### 3. Debit Requests List
**File**: `resources/views/accounts/debit-requests.blade.php`
- Filterable table (status, branch)
- Show: Amount, requestor, branch, status, date
- Action buttons (view, approve/reject)
- Pagination
- Status badges

### 4. Request Details
**File**: `resources/views/accounts/show-debit-request.blade.php`
- Full request information
- Requestor details
- Branch information
- Receipt document link
- Approval/rejection form (for authorized users)
- Status history

### 5. Transactions List
**File**: `resources/views/accounts/transactions.blade.php`
- Filterable table (type, date, branch)
- Show: Date, type, category, amount, balance impact
- Export functionality
- Summary totals

### 6. Settings Page (Update existing)
Add to settings page:
- Company account balance (display only)
- Debit approval threshold (editable)

## Routes to Add

Add these routes to `routes/web.php`:

```php
Route::middleware(['auth'])->prefix('accounts')->name('accounts.')->group(function () {
    // Dashboard
    Route::get('/', [AccountController::class, 'index'])->name('index');
    
    // Debit Requests
    Route::get('/debit-requests/create', [AccountController::class, 'createDebitRequest'])->name('debit-requests.create');
    Route::post('/debit-requests', [AccountController::class, 'storeDebitRequest'])->name('debit-requests.store');
    Route::get('/debit-requests', [AccountController::class, 'debitRequests'])->name('debit-requests.index');
    Route::get('/debit-requests/{debitRequest}', [AccountController::class, 'showDebitRequest'])->name('debit-requests.show');
    
    // Review (Admin/CEO only)
    Route::post('/debit-requests/{debitRequest}/review', [AccountController::class, 'reviewDebitRequest'])
        ->name('debit-requests.review')
        ->middleware('role:Admin|CEO');
    
    // Transactions
    Route::get('/transactions', [AccountController::class, 'transactions'])->name('transactions');
});
```

## Navigation Menu

Add to sidebar navigation:

```html
<li class="nav-item">
    <a class="nav-link" href="{{ route('accounts.index') }}">
        <i class="fas fa-wallet"></i>
        <span>Company Account</span>
    </a>
</li>
```

## Migration Commands

Run these commands to set up the database:

```bash
# Run migrations
php artisan migrate

# The migrations will create:
# - account_debit_requests table
# - Add account settings to system_settings table
```

## Testing Checklist

- [ ] Create debit request as accountant
- [ ] View pending requests
- [ ] Approve request as Admin (check balance deducted)
- [ ] Reject request as CEO
- [ ] View transaction history
- [ ] Filter transactions by date/type
- [ ] Update approval threshold in settings
- [ ] Test authorization (non-admin cannot approve)
- [ ] Test balance insufficient scenario
- [ ] Upload and view receipt documents

## Future Enhancements

1. **Email Notifications**: Notify on request creation/approval
2. **Export Reports**: PDF/Excel export for transactions
3. **Budget Management**: Set monthly budgets per category
4. **Multi-currency Support**: Handle different currencies
5. **Recurring Debits**: Schedule regular debit requests
6. **Approval Chain**: Multiple approval levels
7. **Dashboard Analytics**: Charts and graphs
8. **Mobile App Integration**: API for mobile access

## Support

For questions or issues, contact the development team.
