<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PartStockHistory extends Model
{
    use HasFactory;

    protected $table = 'part_stock_history';

    const TYPE_IN = 'in';
    const TYPE_OUT = 'out';
    const TYPE_ADJUSTMENT = 'adjustment';

    protected $fillable = [
        'part_id',
        'branch_id',
        'type',
        'quantity',
        'quantity_before',
        'quantity_after',
        'reference',
        'notes',
        'user_id',
    ];

    protected $casts = [
        'quantity' => 'integer',
        'quantity_before' => 'integer',
        'quantity_after' => 'integer',
    ];

    /**
     * Get the part for this history record
     */
    public function part(): BelongsTo
    {
        return $this->belongsTo(Part::class);
    }

    /**
     * Get the branch for this history record
     */
    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    /**
     * Get the user who made this change
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Scope for stock in records
     */
    public function scopeStockIn($query)
    {
        return $query->where('type', self::TYPE_IN);
    }

    /**
     * Scope for stock out records
     */
    public function scopeStockOut($query)
    {
        return $query->where('type', self::TYPE_OUT);
    }
}
