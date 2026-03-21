<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ChargingRequest extends Model
{
    use HasFactory;

    /**
     * Status constants
     */
    const STATUS_PENDING = 'pending';
    const STATUS_APPROVED = 'approved';
    const STATUS_IN_PROGRESS = 'in_progress';
    const STATUS_COMPLETED = 'completed';
    const STATUS_CANCELLED = 'cancelled';

    protected $fillable = [
        'driver_id',
        'vehicle_id',
        'location',
        'battery_level_before',
        'battery_level_after',
        'energy_consumed',
        'charging_cost',
        'payment_receipt',
        'charging_start',
        'charging_end',
        'duration_minutes',
        'status',
        'notes',
        'approved_by',
        'approved_at',
    ];

    protected $casts = [
        'battery_level_before' => 'decimal:2',
        'battery_level_after' => 'decimal:2',
        'energy_consumed' => 'decimal:2',
        'charging_cost' => 'decimal:2',
        'charging_start' => 'datetime',
        'charging_end' => 'datetime',
        'approved_at' => 'datetime',
    ];

    /**
     * Get the driver for this charging request
     */
    public function driver(): BelongsTo
    {
        return $this->belongsTo(Driver::class);
    }

    /**
     * Get the vehicle for this charging request
     */
    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class);
    }

    /**
     * Get the user who approved this request
     */
    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    /**
     * Alias for approver - for API consistency
     */
    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    /**
     * Scope for pending requests
     */
    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    /**
     * Scope for in progress requests
     */
    public function scopeInProgress($query)
    {
        return $query->where('status', self::STATUS_IN_PROGRESS);
    }

    /**
     * Scope for completed requests
     */
    public function scopeCompleted($query)
    {
        return $query->where('status', self::STATUS_COMPLETED);
    }

    /**
     * Calculate duration if both start and end times exist
     */
    public function calculateDuration(): ?int
    {
        if ($this->charging_start && $this->charging_end) {
            return $this->charging_start->diffInMinutes($this->charging_end);
        }
        return null;
    }
}
