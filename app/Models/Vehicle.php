<?php

namespace App\Models;

use App\Models\HirePurchaseContract;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Vehicle extends Model
{
    use HasFactory;

    protected $fillable = [
        'branch_id',
        'plate_number',
        'make',
        'model',
    ];

    /**
     * Get the branch this vehicle belongs to
     */
    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    /**
     * Get all assignments for this vehicle
     */
    public function assignments(): HasMany
    {
        return $this->hasMany(VehicleAssignment::class);
    }

    /**
     * Get the current assignment for this vehicle
     */
    public function currentAssignment()
    {
        return $this->hasOne(VehicleAssignment::class)->whereNull('returned_at')->latest();
    }

    /**
     * Get hire purchase contracts for this vehicle
     */
    public function hirePurchaseContracts(): HasMany
    {
        return $this->hasMany(HirePurchaseContract::class);
    }

    /**
     * Get the active hire purchase contract for this vehicle
     */
    public function activeHirePurchaseContract(): HasOne
    {
        return $this->hasOne(HirePurchaseContract::class)
            ->where('status', 'active');
    }

    /**
     * Check if vehicle has an active hire purchase contract
     */
    public function hasActiveHirePurchaseContract(): bool
    {
        return $this->hirePurchaseContracts()
            ->where('status', 'active')
            ->exists();
    }
}
