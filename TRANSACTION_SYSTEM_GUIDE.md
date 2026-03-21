# eRide Transaction System Guide

## Overview
The transaction system has been updated to handle all types of financial transactions in the eRide Transport Management System with flexibility and proper tracking.

## Database Structure

### Transactions Table Schema
```
id                  - Primary key
driver_id           - Foreign key to drivers table
approved_by         - Foreign key to users (for approval workflow)
type                - VARCHAR(50) - Transaction type identifier
amount              - DECIMAL(10,2) - Transaction amount
reference           - VARCHAR(100) - Unique reference number
description         - TEXT - Transaction description/notes
status              - ENUM - Transaction status (pending, successful, rejected, failed)
processed_by        - Foreign key to users (who processed the transaction)
processed_at        - TIMESTAMP - When transaction was processed
created_at          - TIMESTAMP
updated_at          - TIMESTAMP
```

## Transaction Types

The system supports the following transaction types:

### 1. **credit**
- **Description**: General credit to driver wallet
- **Use Cases**: 
  - Admin wallet funding
  - Bonus payments
  - Refunds
  - Corrections
- **Example**: Admin funds driver wallet with ₦50,000

### 2. **debit**
- **Description**: General debit from driver wallet
- **Use Cases**:
  - Manual deductions
  - Corrections
  - Penalties
- **Example**: Deduct ₦5,000 for policy violation

### 3. **daily_remittance**
- **Description**: Daily payment from driver to company
- **Use Cases**:
  - Daily earnings remittance
  - Payment submissions from mobile app
- **Example**: Driver submits ₦15,000 daily payment

### 4. **maintenance_debit**
- **Description**: Debit for vehicle maintenance costs
- **Use Cases**:
  - Approved maintenance requests
  - Parts and service costs
- **Example**: ₦25,000 deducted for vehicle repairs

### 5. **wallet_top_up**
- **Description**: Wallet funding from external sources
- **Use Cases**:
  - Bank transfers
  - Mobile money deposits
  - Third-party payments
- **Example**: Driver adds ₦100,000 via bank transfer

### 6. **refund**
- **Description**: Return of previously deducted amounts
- **Use Cases**:
  - Cancelled maintenance
  - Overpayment returns
  - Error corrections
- **Example**: Return ₦10,000 for cancelled service

### 7. **penalty**
- **Description**: Fines and penalties
- **Use Cases**:
  - Policy violations
  - Late payments
  - Damages
- **Example**: ₦5,000 penalty for late vehicle return

### 8. **bonus**
- **Description**: Performance bonuses and incentives
- **Use Cases**:
  - Performance rewards
  - Referral bonuses
  - Special incentives
- **Example**: ₦20,000 bonus for excellent service

## Transaction Statuses

### 1. **pending**
- Transaction created but not yet processed
- Awaiting approval or verification
- Can be approved or rejected

### 2. **successful**
- Transaction completed successfully
- Wallet balance updated
- Final state for approved transactions

### 3. **rejected**
- Transaction was declined/rejected
- No wallet balance change
- Requires reason/description

### 4. **failed**
- Transaction attempted but failed
- Technical or validation error
- May be retried

## Usage Examples

### Creating a Credit Transaction (Wallet Funding)
```php
Transaction::create([
    'driver_id' => $driver->id,
    'type' => 'credit',
    'amount' => 50000.00,
    'reference' => 'FUND-' . strtoupper(uniqid()),
    'description' => 'Admin wallet funding',
    'status' => 'successful',
    'processed_by' => auth()->id(),
    'processed_at' => now(),
]);
```

### Creating a Debit Transaction (Maintenance)
```php
Transaction::create([
    'driver_id' => $driver->id,
    'type' => 'maintenance_debit',
    'amount' => 25000.00,
    'reference' => 'MAINT-' . $maintenanceRequest->id,
    'description' => 'Vehicle maintenance: ' . $maintenanceRequest->description,
    'status' => 'successful',
    'processed_by' => auth()->id(),
    'processed_at' => now(),
]);
```

### Creating a Pending Transaction (Daily Remittance)
```php
Transaction::create([
    'driver_id' => $driver->id,
    'type' => 'daily_remittance',
    'amount' => 15000.00,
    'reference' => 'PAY-' . date('Ymd') . '-' . $driver->id,
    'description' => 'Daily remittance for ' . date('Y-m-d'),
    'status' => 'pending',
]);
```

## Reference Number Formats

Use consistent reference number formats for traceability:

- **Credit/Funding**: `FUND-{UNIQUE_ID}`
- **Maintenance**: `MAINT-{REQUEST_ID}`
- **Daily Payment**: `PAY-{DATE}-{DRIVER_ID}`
- **Bonus**: `BONUS-{UNIQUE_ID}`
- **Penalty**: `PEN-{UNIQUE_ID}`
- **Refund**: `REF-{ORIGINAL_REFERENCE}`

## Best Practices

### 1. Always Include Description
```php
'description' => 'Clear, detailed description of the transaction'
```

### 2. Generate Unique References
```php
'reference' => 'PREFIX-' . strtoupper(uniqid())
```

### 3. Track Who Processed
```php
'processed_by' => auth()->id(),
'processed_at' => now(),
```

### 4. Use Database Transactions
```php
DB::transaction(function () use ($data) {
    // Update wallet
    $driver->wallet->increment('balance', $amount);
    
    // Create transaction record
    Transaction::create([...]);
    
    // Update daily ledger
    DailyLedger::updateOrCreate([...]);
});
```

### 5. Update Daily Ledger
Always sync transactions with daily ledgers:
```php
if ($transaction->type === 'credit') {
    $ledger->increment('total_credits', $amount);
} else {
    $ledger->increment('total_debits', $amount);
}
```

## Integration Points

### 1. Wallet Funding (Admin)
- **Location**: `DriverController@fundWallet`
- **Type**: `credit`
- **Flow**: Admin → Transaction → Wallet → Ledger

### 2. Maintenance Requests
- **Location**: `MaintenanceRequestController@complete`
- **Type**: `maintenance_debit`
- **Flow**: Approval → Completion → Transaction → Wallet

### 3. Daily Remittance
- **Location**: Mobile App → API
- **Type**: `daily_remittance`
- **Flow**: Driver Payment → Pending → Approval → Successful

### 4. Payment Approval
- **Location**: `PaymentController@approve`
- **Type**: `daily_remittance`
- **Flow**: Pending → Approved → Wallet Credit

## Reporting & Analytics

### Transaction Summary by Type
```php
$summary = Transaction::selectRaw('type, COUNT(*) as count, SUM(amount) as total')
    ->where('driver_id', $driverId)
    ->where('status', 'successful')
    ->groupBy('type')
    ->get();
```

### Driver Transaction History
```php
$transactions = Transaction::where('driver_id', $driverId)
    ->with(['approver', 'processor'])
    ->orderBy('created_at', 'desc')
    ->paginate(20);
```

### Financial Reports
```php
$credits = Transaction::whereIn('type', ['credit', 'wallet_top_up', 'bonus', 'refund'])
    ->where('status', 'successful')
    ->sum('amount');

$debits = Transaction::whereIn('type', ['debit', 'maintenance_debit', 'penalty'])
    ->where('status', 'successful')
    ->sum('amount');
```

## Migration Applied

The following migration has been applied:
- **File**: `2025_10_08_225107_update_transactions_table_for_comprehensive_handling.php`
- **Changes**:
  - Changed `type` from ENUM to VARCHAR(50) for flexibility
  - Added `reference` column for unique transaction identifiers
  - Added `description` column for transaction details
  - Added `processed_by` foreign key to track who processed
  - Added `processed_at` timestamp for processing time

## Model Constants

Use the Transaction model constants for type safety:
```php
Transaction::TYPE_CREDIT              // 'credit'
Transaction::TYPE_DEBIT               // 'debit'
Transaction::TYPE_DAILY_REMITTANCE    // 'daily_remittance'
Transaction::TYPE_MAINTENANCE_DEBIT   // 'maintenance_debit'
Transaction::TYPE_WALLET_TOP_UP       // 'wallet_top_up'
Transaction::TYPE_REFUND              // 'refund'
Transaction::TYPE_PENALTY             // 'penalty'
Transaction::TYPE_BONUS               // 'bonus'

Transaction::STATUS_PENDING           // 'pending'
Transaction::STATUS_SUCCESSFUL        // 'successful'
Transaction::STATUS_REJECTED          // 'rejected'
Transaction::STATUS_FAILED            // 'failed'
```

## Troubleshooting

### Issue: "Data truncated for column 'type'"
**Solution**: Migration applied - `type` is now VARCHAR(50)

### Issue: Missing reference or description
**Solution**: These fields are now nullable but recommended to populate

### Issue: Can't track who processed
**Solution**: Always set `processed_by` and `processed_at` when processing

## Security Considerations

1. **Validate amounts**: Always validate positive amounts for credits, negative for debits
2. **Verify balance**: Check wallet balance before debits
3. **Audit trail**: `processed_by` and `processed_at` provide audit trail
4. **Reference uniqueness**: Generate unique references to prevent duplicates
5. **Status transitions**: Ensure valid status transitions (pending → successful/rejected)

## Future Enhancements

Consider adding:
- Transaction reversal/void functionality
- Batch transaction processing
- Scheduled/recurring transactions
- Transaction approval workflow
- Multi-currency support
- Transaction fees/charges
- Payment gateway integration

---

**Last Updated**: October 8, 2025
**System Version**: eRide Transport Management System v1.0
