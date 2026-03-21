<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class MaintenanceRequest extends Model
{
    use HasFactory;

    protected $fillable = [
        'driver_id',
        'mechanic_id',
        'approved_by_id',
        'issue_description',
        'issue_photos',
        'manager_notes',
        'completed_at',
        'status',
    ];

    protected $casts = [
        'completed_at' => 'datetime',
    ];

    /**
     * Get the driver for this maintenance request
     */
    public function driver(): BelongsTo
    {
        return $this->belongsTo(Driver::class);
    }

    /**
     * Get the mechanic who created this request
     */
    public function mechanic(): BelongsTo
    {
        return $this->belongsTo(User::class, 'mechanic_id');
    }

    /**
     * Get the manager who approved this request
     */
    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by_id');
    }

    /**
     * Get all parts for this maintenance request
     */
    public function parts(): BelongsToMany
    {
        return $this->belongsToMany(Part::class, 'maintenance_request_parts', 'request_id', 'part_id')
            ->withPivot('quantity', 'unit_cost', 'total_cost')
            ->withTimestamps();
    }

    /**
     * Get the vehicle relationship through driver's current assignment
     */
    public function vehicle()
    {
        // This creates a dynamic relationship that gets the driver's current vehicle
        return $this->hasOneThrough(
            Vehicle::class,
            VehicleAssignment::class,
            'driver_id', // Foreign key on vehicle_assignments table
            'id', // Foreign key on vehicles table
            'driver_id', // Local key on maintenance_requests table
            'vehicle_id' // Local key on vehicle_assignments table
        )->latest('vehicle_assignments.created_at');
    }

    /**
     * Get the vehicle through driver's current assignment (Accessor)
     */
    public function getVehicleAttribute()
    {
        // Return null if driver doesn't exist
        if (!$this->driver) {
            return null;
        }
        
        return $this->driver->vehicleAssignments()->with('vehicle')->latest()->first()?->vehicle;
    }

    /**
     * Calculate the total cost of this maintenance request
     */
    public function getTotalCostAttribute(): float
    {
        return $this->parts->sum(function ($part) {
            // Use pivot table costs (which are saved when parts are attached)
            return (float)($part->pivot->total_cost ?? 0);
        });
    }
}
