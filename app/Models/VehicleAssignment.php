<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VehicleAssignment extends Model
{
    use HasFactory;

    protected $fillable = [
        'vehicle_id',
        'driver_id',
        'assigned_at',
        'returned_at',
    ];

    protected $casts = [
        'assigned_at' => 'datetime',
        'returned_at' => 'datetime',
    ];

    /**
     * Get the vehicle for this assignment
     */
    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class);
    }

    /**
     * Get the driver for this assignment
     */
    public function driver(): BelongsTo
    {
        return $this->belongsTo(Driver::class);
    }

    /**
     * Check if the assignment is active
     */
    public function isActive(): bool
    {
        return is_null($this->returned_at);
    }

    /**
     * Get the status attribute
     */
    public function getStatusAttribute(): string
    {
        return $this->isActive() ? 'active' : 'returned';
    }

    /**
     * Scope for active assignments
     */
    public function scopeActive($query)
    {
        return $query->whereNull('returned_at');
    }

    /**
     * Scope for returned assignments
     */
    public function scopeReturned($query)
    {
        return $query->whereNotNull('returned_at');
    }
}
