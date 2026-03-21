<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HirePurchasePayment extends Model
{
    use HasFactory;

    const STATUS_PENDING = 'pending';
    const STATUS_PAID = 'paid';
    const STATUS_PARTIAL = 'partial';
    const STATUS_OVERDUE = 'overdue';
    const STATUS_WAIVED = 'waived';
    const STATUS_SKIPPED = 'skipped';

    protected $fillable = [
        'hire_purchase_contract_id',
        'driver_id',
        'transaction_id',
        'payment_number',
        'expected_amount',
        'amount_paid',
        'penalty_amount',
        'total_amount',
        'balance_before',
        'balance_after',
        'due_date',
        'paid_date',
        'days_late',
        'status',
        'payment_method',
        'payment_proof',
        'notes',
        'processed_by',
        'processed_at',
    ];

    protected $casts = [
        'expected_amount' => 'decimal:2',
        'amount_paid' => 'decimal:2',
        'penalty_amount' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'balance_before' => 'decimal:2',
        'balance_after' => 'decimal:2',
        'due_date' => 'date',
        'paid_date' => 'date',
        'processed_at' => 'datetime',
    ];

    public function contract(): BelongsTo
    {
        return $this->belongsTo(HirePurchaseContract::class, 'hire_purchase_contract_id');
    }

    public function driver(): BelongsTo
    {
        return $this->belongsTo(Driver::class);
    }

    public function transaction(): BelongsTo
    {
        return $this->belongsTo(Transaction::class);
    }

    public function processor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'processed_by');
    }

    /**
     * Check if payment is overdue
     */
    public function getIsOverdueAttribute(): bool
    {
        return $this->status !== self::STATUS_PAID && 
               $this->status !== self::STATUS_WAIVED && 
               $this->status !== self::STATUS_SKIPPED && 
               $this->due_date < now()->toDateString();
    }

    /**
     * Get days overdue
     */
    public function getDaysOverdueAttribute(): int
    {
        if (!$this->is_overdue) return 0;
        return now()->diffInDays($this->due_date);
    }

    /**
     * Scope for pending payments
     */
    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    /**
     * Scope for overdue payments
     */
    public function scopeOverdue($query)
    {
        return $query->where('status', self::STATUS_OVERDUE);
    }

    /**
     * Scope for paid payments
     */
    public function scopePaid($query)
    {
        return $query->where('status', self::STATUS_PAID);
    }

    /**
     * Scope for due today
     */
    public function scopeDueToday($query)
    {
        return $query->whereDate('due_date', today())
            ->whereIn('status', [self::STATUS_PENDING, self::STATUS_OVERDUE]);
    }

    /**
     * Scope for upcoming payments
     */
    public function scopeUpcoming($query, $days = 7)
    {
        return $query->whereBetween('due_date', [today(), today()->addDays($days)])
            ->whereIn('status', [self::STATUS_PENDING]);
    }
}
