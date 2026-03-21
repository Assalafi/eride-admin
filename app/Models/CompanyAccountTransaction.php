<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CompanyAccountTransaction extends Model
{
    use HasFactory;

    const TYPE_INCOME = 'income';
    const TYPE_EXPENSE = 'expense';

    // Category constants - INCOME
    const CATEGORY_DAILY_REMITTANCE = 'daily_remittance';
    const CATEGORY_CHARGING = 'charging';
    const CATEGORY_MAINTENANCE_INCOME = 'maintenance_income';
    
    // Category constants - EXPENSE
    const CATEGORY_FUEL = 'fuel';
    const CATEGORY_MAINTENANCE_EXPENSE = 'maintenance_expense';
    const CATEGORY_SALARY = 'salary';
    const CATEGORY_UTILITIES = 'utilities';
    const CATEGORY_RENT = 'rent';
    const CATEGORY_INSURANCE = 'insurance';
    const CATEGORY_LOAN_PAYMENT = 'loan_payment';
    const CATEGORY_DEBIT_REQUEST = 'debit_request';
    const CATEGORY_MISCELLANEOUS = 'miscellaneous';

    protected $fillable = [
        'branch_id',
        'type',
        'amount',
        'category',
        'reference',
        'description',
        'transaction_date',
        'recorded_by',
        'receipt_document',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'transaction_date' => 'date',
    ];

    /**
     * Get the branch for this transaction
     */
    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    /**
     * Get the user who recorded this transaction
     */
    public function recordedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }

    /**
     * Scope for income transactions
     */
    public function scopeIncome($query)
    {
        return $query->where('type', self::TYPE_INCOME);
    }

    /**
     * Scope for expense transactions
     */
    public function scopeExpense($query)
    {
        return $query->where('type', self::TYPE_EXPENSE);
    }

    /**
     * Scope for date range filter
     */
    public function scopeDateRange($query, $start, $end)
    {
        return $query->whereBetween('transaction_date', [$start, $end]);
    }

    /**
     * Scope for specific branch
     */
    public function scopeForBranch($query, $branchId)
    {
        return $query->where('branch_id', $branchId);
    }

    /**
     * Scope for specific category
     */
    public function scopeCategory($query, $category)
    {
        return $query->where('category', $category);
    }

    /**
     * Check if transaction is income
     */
    public function isIncome(): bool
    {
        return $this->type === self::TYPE_INCOME;
    }

    /**
     * Check if transaction is expense
     */
    public function isExpense(): bool
    {
        return $this->type === self::TYPE_EXPENSE;
    }

    /**
     * Get all available categories
     */
    public static function getCategories(): array
    {
        return [
            // Income
            self::CATEGORY_DAILY_REMITTANCE => 'Daily Remittance',
            self::CATEGORY_CHARGING => 'EV Charging Service',
            self::CATEGORY_MAINTENANCE_INCOME => 'Maintenance Payment',
            // Expense
            self::CATEGORY_FUEL => 'Fuel',
            self::CATEGORY_MAINTENANCE_EXPENSE => 'Maintenance Expense',
            self::CATEGORY_SALARY => 'Salary',
            self::CATEGORY_UTILITIES => 'Utilities',
            self::CATEGORY_RENT => 'Rent',
            self::CATEGORY_INSURANCE => 'Insurance',
            self::CATEGORY_LOAN_PAYMENT => 'Loan Payment',
            self::CATEGORY_DEBIT_REQUEST => 'Debit Request',
            self::CATEGORY_MISCELLANEOUS => 'Miscellaneous',
        ];
    }

    /**
     * Get income categories
     */
    public static function getIncomeCategories(): array
    {
        return [
            self::CATEGORY_DAILY_REMITTANCE => 'Daily Remittance',
            self::CATEGORY_CHARGING => 'EV Charging Service',
            self::CATEGORY_MAINTENANCE_INCOME => 'Maintenance Payment',
        ];
    }

    /**
     * Get expense categories
     */
    public static function getExpenseCategories(): array
    {
        return [
            self::CATEGORY_FUEL => 'Fuel',
            self::CATEGORY_MAINTENANCE_EXPENSE => 'Maintenance Expense',
            self::CATEGORY_SALARY => 'Salary',
            self::CATEGORY_UTILITIES => 'Utilities',
            self::CATEGORY_RENT => 'Rent',
            self::CATEGORY_INSURANCE => 'Insurance',
            self::CATEGORY_LOAN_PAYMENT => 'Loan Payment',
            self::CATEGORY_DEBIT_REQUEST => 'Debit Request',
            self::CATEGORY_MISCELLANEOUS => 'Miscellaneous',
        ];
    }

    // get payment file, if the category is charging the reference is like this CHARGE-14, 14 is id from charging_requests table get the file from payment_receipt column. Then if the category is daily_remittance the reference is like this REMIT-14, 14 is id from transactions table get the file from payment_proof column.

    /**
     * Get payment file based on category and reference
     * 
     * If the category is charging, the reference is like "CHARGE-14" where 14 is id from charging_requests table
     * Get the file from payment_receipt column.
     * 
     * If the category is daily_remittance, the reference is like "REMIT-14" where 14 is id from transactions table
     * Get the file from payment_proof column.
     * 
     * @return string|null The file path or null if not found
     */
    public function getPaymentFileAttribute()
    {
        if (!$this->reference) {
            return null;
        }

        // Parse reference to get type and ID
        $parts = explode('-', $this->reference);
        if (count($parts) < 2) {
            return null;
        }

        $type = $parts[0];
        $id = $parts[1];

        try {
            switch ($this->category) {
                case self::CATEGORY_CHARGING:
                    if ($type === 'CHARGE' && is_numeric($id)) {
                        $chargingRequest = \App\Models\ChargingRequest::find($id);
                        return $chargingRequest?->payment_receipt;
                    }
                    break;

                case self::CATEGORY_DAILY_REMITTANCE:
                    if ($type === 'REMIT' && is_numeric($id)) {
                        $transaction = \App\Models\Transaction::find($id);
                        return $transaction?->payment_proof;
                    }
                    break;

                case self::CATEGORY_MAINTENANCE_INCOME:
                    if ($type === 'MAINT' && is_numeric($id)) {
                        $transaction = \App\Models\Transaction::find($id);
                        return $transaction?->payment_proof;
                    }
                    break;

                default:
                    // For other categories, return the receipt_document if available
                    return $this->receipt_document;
            }
        } catch (\Exception $e) {
            // Log error if needed, but return null on failure
            return null;
        }

        return null;
    }

    /**
     * Get payment file URL for display
     */
    public function getPaymentFileUrlAttribute()
    {
        $file = $this->payment_file;
        
        if (!$file) {
            return null;
        }

        // If it's already a full URL, return as is
        if (filter_var($file, FILTER_VALIDATE_URL)) {
            return $file;
        }

        // If it's a storage path, convert to URL
        if (str_starts_with($file, 'storage/')) {
            return asset($file);
        }

        // Assume it's a relative storage path
        return asset('storage/' . $file);
    }
}
