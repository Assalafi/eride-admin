# 🔄 Restore Payment Feature

## Transaction Recovery System

Added a restore feature for rejected payments that creates a new identical transaction with pending status, allowing administrators to recover from rejections.

---

## 🎯 Feature Overview

### Restore Functionality

-   **Target**: Rejected transactions only
-   **Action**: Creates new identical transaction
-   **Status**: New transaction starts as "pending"
-   **Reference**: Unique identifier with "RESTORED-" prefix
-   **Audit Trail**: Links to original rejected transaction

### User Experience

-   **Visual Indicator**: Warning restore button for rejected payments
-   **Confirmation Dialog**: Clear explanation of restore action
-   **Success Message**: Confirmation of new transaction creation
-   **Original Preserved**: Rejected transaction remains unchanged

---

## 🛠️ Implementation Details

### Backend Changes

#### New Controller Method

```php
public function restore(Transaction $transaction)
{
    $user = auth()->user();

    // Check if user can restore this transaction
    if (!$user->hasRole('Super Admin') && $transaction->driver->branch_id !== $user->branch_id) {
        abort(403, 'You do not have permission to restore this transaction.');
    }

    if ($transaction->status !== 'rejected') {
        return back()->withErrors(['error' => 'Only rejected transactions can be restored.']);
    }

    // Create a new identical transaction
    $newTransaction = Transaction::create([
        'driver_id' => $transaction->driver_id,
        'type' => $transaction->type,
        'amount' => $transaction->amount,
        'reference' => 'RESTORED-' . $transaction->reference . '-' . time(),
        'description' => 'Restored from rejected transaction #' . $transaction->id,
        'payment_proof' => $transaction->payment_proof,
        'status' => 'pending',
        'approved_by' => null,
        'processed_by' => null,
        'processed_at' => null,
    ]);

    return redirect()->route('admin.payments.index')
        ->with('success', 'Payment restored successfully! New transaction created with pending status.');
}
```

#### Route Addition

```php
Route::middleware(['permission:approve payments'])->group(function () {
    Route::post('admin/payments/{transaction}/approve', [PaymentController::class, 'approve'])->name('admin.payments.approve');
    Route::post('admin/payments/{transaction}/reject', [PaymentController::class, 'reject'])->name('admin.payments.reject');
    Route::post('admin/payments/{transaction}/restore', [PaymentController::class, 'restore'])->name('admin.payments.restore');
    Route::post('admin/payments/generate-daily-remittances', [PaymentController::class, 'generateDailyRemittances'])->name('admin.payments.generate-daily-remittances');
});
```

### Frontend Changes

#### Updated Action Buttons

```blade
@if ($transaction->status == 'pending')
    @can('approve payments')
        <div class="d-flex gap-1">
            <!-- Approve Button -->
            <form action="{{ route('admin.payments.approve', $transaction) }}" method="POST" class="d-inline">
                @csrf
                <button type="submit" class="btn btn-sm btn-success" title="Approve"
                    onclick="return confirm('Approve this payment?')">
                    <i class="ri-check-line"></i>
                </button>
            </form>
            <!-- Reject Button -->
            <button type="button" class="btn btn-sm btn-danger" title="Reject"
                onclick="openRejectionModal('{{ route('admin.payments.reject', $transaction) }}',
                                          '{{ $transaction->driver->full_name }}',
                                          '₦{{ number_format($transaction->amount, 2) }}')">
                <i class="ri-close-line"></i>
            </button>
        </div>
    @endcan
@elseif($transaction->status == 'rejected')
    @can('approve payments')
        <div class="d-flex gap-1">
            <!-- Restore Button -->
            <form action="{{ route('admin.payments.restore', $transaction) }}" method="POST" class="d-inline">
                @csrf
                <button type="submit" class="btn btn-sm btn-warning" title="Restore"
                    onclick="return confirm('Restore this payment? This will create a new identical transaction with pending status.')">
                    <i class="ri-refresh-line"></i>
                </button>
            </form>
        </div>
    @endcan
@else
    <span class="text-muted">-</span>
@endif
```

---

## 📊 Transaction Lifecycle

### Complete Flow

```
1. Original Transaction (Pending)
   ↓
2. Admin Rejects with Comment
   ↓
3. Transaction Status: Rejected
   ↓
4. Admin Clicks Restore
   ↓
5. New Transaction Created (Pending)
   ↓
6. Original Remains (Rejected)
```

### Transaction Details Comparison

#### Original Rejected Transaction

```php
[
    'id' => 123,
    'status' => 'rejected',
    'reference' => 'REM-2025-001',
    'description' => 'Receipt unclear - please upload better quality',
    'amount' => 5000.00,
    'driver_id' => 45,
    // ... other original fields
]
```

#### New Restored Transaction

```php
[
    'id' => 124, // New ID
    'status' => 'pending', // Fresh pending status
    'reference' => 'RESTORED-REM-2025-001-1732368000', // Unique reference
    'description' => 'Restored from rejected transaction #123', // Audit trail
    'amount' => 5000.00, // Same amount
    'driver_id' => 45, // Same driver
    'payment_proof' => 'receipts/original.jpg', // Same proof
    // ... all other fields identical except status and timestamps
]
```

---

## 🎨 Visual Design

### Button States

#### Pending Transaction

```
[✓ Approve] [✗ Reject]
```

#### Rejected Transaction

```
[🔄 Restore]
```

#### Successful Transaction

```
[-] (No actions)
```

### Button Styling

-   **Restore Button**: Warning color (`btn-warning`)
-   **Icon**: Refresh icon (`ri-refresh-line`)
-   **Size**: Small button (`btn-sm`)
-   **Tooltip**: "Restore"
-   **Confirmation**: Detailed explanation dialog

---

## 🧪 Testing Scenarios

### Test Case 1: Successful Restore

```
Given: Rejected transaction with valid rejection reason
When: Admin clicks restore and confirms
Then: New identical transaction created with pending status
```

### Test Case 2: Restore Non-Rejected Transaction

```
Given: Pending or successful transaction
When: Admin tries to restore (URL manipulation)
Then: Error message: "Only rejected transactions can be restored"
```

### Test Case 3: Permission Check

```
Given: Admin from different branch tries to restore
When: Restore action attempted
Then: 403 Forbidden error
```

### Test Case 4: Reference Uniqueness

```
Given: Multiple restores of same transaction
When: Each restore creates new transaction
Then: Each has unique reference with timestamp
```

### Test Case 5: Data Integrity

```
Given: Rejected transaction with payment proof
When: Restored
Then: New transaction has same payment proof file
```

---

## 📈 Business Benefits

### Operational Flexibility

-   **Recovery Options**: Ability to undo rejections when needed
-   **Error Correction**: Fix mistaken rejections quickly
-   **Driver Relations**: Recover from administrative errors
-   **Workflow Continuity**: Maintain payment processing flow

### Audit & Compliance

-   **Complete Trail**: Original rejection preserved
-   **Transparent History**: Clear link between transactions
-   **Reference Tracking**: Unique identifiers for restored items
-   **Documentation**: Automatic audit trail creation

### User Experience

-   **Simple Recovery**: One-click restore process
-   **Clear Confirmation**: Detailed explanation before action
-   **Status Clarity**: Visual distinction of restorable items
-   **Success Feedback**: Confirmation of new transaction

---

## 🔒 Security Features

### Permission Controls

-   **Same Permissions**: Uses existing approve payments permission
-   **Branch Restrictions**: Respects branch-based access control
-   **Status Validation**: Only rejected transactions can be restored
-   **User Authentication**: Requires authenticated admin user

### Data Integrity

-   **Original Preserved**: Rejected transaction never modified
-   **New Creation**: Always creates fresh transaction
-   **Reference Safety**: Unique references prevent conflicts
-   **Audit Trail**: Clear link to original transaction

---

## ✅ Summary

**Key Features**:

1. ✅ Restore button for rejected transactions only
2. ✅ Creates new identical transaction with pending status
3. ✅ Preserves original rejected transaction
4. ✅ Unique reference with "RESTORED-" prefix
5. ✅ Audit trail linking to original transaction
6. ✅ Confirmation dialog with clear explanation

**Files Modified**:

-   ✅ `PaymentController.php` - Added restore method
-   ✅ `routes/web.php` - Added restore route
-   ✅ `payments/index.blade.php` - Added restore button

**Security & Validation**:

-   ✅ Only rejected transactions can be restored
-   ✅ Existing permission system maintained
-   ✅ Branch access controls preserved
-   ✅ Data integrity ensured

**Result**: Complete transaction recovery system with audit trail!

---

## 🚀 Result

**Rejected payments can now be restored with a new identical transaction!**

Administrators can now:

-   Restore rejected payments to pending status
-   Create new identical transactions automatically
-   Maintain complete audit trail of restores
-   Recover from administrative errors
-   Preserve original rejection records

**Enhanced payment management with full recovery capabilities!** 🔄💳📋
