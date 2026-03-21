<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class HirePurchaseContract extends Model
{
    use HasFactory, SoftDeletes;

    const STATUS_PENDING = 'pending';
    const STATUS_ACTIVE = 'active';
    const STATUS_COMPLETED = 'completed';
    const STATUS_DEFAULTED = 'defaulted';
    const STATUS_TERMINATED = 'terminated';
    const STATUS_SUSPENDED = 'suspended';

    const PAYMENT_FREQUENCY_DAILY = 'daily';
    const PAYMENT_FREQUENCY_WEEKLY = 'weekly';
    const PAYMENT_FREQUENCY_MONTHLY = 'monthly';

    protected $fillable = [
        'driver_id',
        'vehicle_id',
        'branch_id',
        'contract_number',
        'vehicle_price',
        'down_payment',
        'total_amount',
        'daily_payment',
        'weekly_payment',
        'monthly_payment',
        'payment_frequency',
        'total_payment_days',
        'grace_period_days',
        'total_paid',
        'total_balance',
        'payments_made',
        'payments_remaining',
        'missed_payments',
        'late_payments',
        'late_fee_percentage',
        'late_fee_fixed',
        'total_penalties',
        'start_date',
        'expected_end_date',
        'actual_end_date',
        'last_payment_date',
        'next_payment_due',
        'status',
        'notes',
        'termination_reason',
        'created_by',
        'approved_by',
        'approved_at',
    ];

    protected $casts = [
        'vehicle_price' => 'decimal:2',
        'down_payment' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'daily_payment' => 'decimal:2',
        'weekly_payment' => 'decimal:2',
        'monthly_payment' => 'decimal:2',
        'total_paid' => 'decimal:2',
        'total_balance' => 'decimal:2',
        'late_fee_percentage' => 'decimal:2',
        'late_fee_fixed' => 'decimal:2',
        'total_penalties' => 'decimal:2',
        'start_date' => 'date',
        'expected_end_date' => 'date',
        'actual_end_date' => 'date',
        'last_payment_date' => 'date',
        'next_payment_due' => 'date',
        'approved_at' => 'datetime',
    ];

    public function driver(): BelongsTo
    {
        return $this->belongsTo(Driver::class);
    }

    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(HirePurchasePayment::class);
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class, 'hire_purchase_contract_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    /**
     * Generate unique contract number
     */
    public static function generateContractNumber(): string
    {
        $prefix = 'HP';
        $year = date('Y');
        $month = date('m');
        
        $lastContract = self::whereYear('created_at', $year)
            ->whereMonth('created_at', $month)
            ->orderBy('id', 'desc')
            ->first();
        
        $sequence = $lastContract ? (int)substr($lastContract->contract_number, -4) + 1 : 1;
        
        return sprintf('%s%s%s%04d', $prefix, $year, $month, $sequence);
    }

    /**
     * Get current payment amount based on frequency
     */
    public function getCurrentPaymentAmount(): float
    {
        return match($this->payment_frequency) {
            self::PAYMENT_FREQUENCY_WEEKLY => (float) $this->weekly_payment,
            self::PAYMENT_FREQUENCY_MONTHLY => (float) $this->monthly_payment,
            default => (float) $this->daily_payment,
        };
    }

    /**
     * Calculate progress percentage
     */
    public function getProgressPercentageAttribute(): float
    {
        if ($this->total_amount <= 0) return 0;
        return round(($this->total_paid / $this->total_amount) * 100, 2);
    }

    /**
     * Check if contract is overdue
     */
    public function getIsOverdueAttribute(): bool
    {
        if (!$this->next_payment_due) return false;
        return $this->status === self::STATUS_ACTIVE && $this->next_payment_due < now()->toDateString();
    }

    /**
     * Get days until next payment
     */
    public function getDaysUntilNextPaymentAttribute(): int
    {
        if (!$this->next_payment_due) return 0;
        return now()->diffInDays($this->next_payment_due, false);
    }

    /**
     * Get overdue amount
     */
    public function getOverdueAmountAttribute(): float
    {
        return $this->payments()
            ->where('status', 'overdue')
            ->sum('expected_amount');
    }

    /**
     * Get pending payments count
     */
    public function getPendingPaymentsCountAttribute(): int
    {
        return $this->payments()
            ->whereIn('status', ['pending', 'overdue'])
            ->count();
    }

    /**
     * Scope for active contracts
     */
    public function scopeActive($query)
    {
        return $query->where('status', self::STATUS_ACTIVE);
    }

    /**
     * Scope for overdue contracts
     */
    public function scopeOverdue($query)
    {
        return $query->where('status', self::STATUS_ACTIVE)
            ->where('next_payment_due', '<', now()->toDateString());
    }

    /**
     * Scope for branch
     */
    public function scopeForBranch($query, $branchId)
    {
        return $query->where('branch_id', $branchId);
    }
}
