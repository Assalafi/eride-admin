<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\HirePurchaseContract;

class Transaction extends Model
{
    use HasFactory;

    /**
     * Transaction Types
     */
    const TYPE_DAILY_REMITTANCE = 'daily_remittance';
    const TYPE_MAINTENANCE_DEBIT = 'maintenance_debit';
    const TYPE_WALLET_TOP_UP = 'wallet_top_up';
    const TYPE_CREDIT = 'credit';
    const TYPE_DEBIT = 'debit';
    const TYPE_REFUND = 'refund';
    const TYPE_PENALTY = 'penalty';
    const TYPE_BONUS = 'bonus';

    /**
     * Transaction Statuses
     */
    const STATUS_PENDING = 'pending';
    const STATUS_SUCCESSFUL = 'successful';
    const STATUS_REJECTED = 'rejected';
    const STATUS_FAILED = 'failed';

    protected $fillable = [
        'driver_id',
        'approved_by',
        'type',
        'amount',
        'reference',
        'description',
        'payment_proof',
        'paid_at',
        'status',
        'processed_by',
        'processed_at',
        'is_hire_purchase_payment',
        'hire_purchase_contract_id',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'paid_at' => 'datetime',
        'processed_at' => 'datetime',
        'is_hire_purchase_payment' => 'boolean',
    ];

    /**
     * Get the driver for this transaction
     */
    public function driver(): BelongsTo
    {
        return $this->belongsTo(Driver::class);
    }

    /**
     * Get the user who approved this transaction
     */
    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    /**
     * Get the user who processed this transaction
     */
    public function processor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'processed_by');
    }

    /**
     * Get the hire purchase contract for this transaction
     */
    public function hirePurchaseContract(): BelongsTo
    {
        return $this->belongsTo(HirePurchaseContract::class);
    }
}
