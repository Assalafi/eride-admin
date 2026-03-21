<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Part extends Model
{
    use HasFactory;

    protected $fillable = [
        'branch_id',
        'name',
        'sku',
        'picture',
        'description',
        'cost',
    ];

    protected $casts = [
        'cost' => 'decimal:2',
    ];

    /**
     * Get the branch that owns this part
     */
    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    /**
     * Get all stock records for this part
     */
    public function stock(): HasMany
    {
        return $this->hasMany(PartStock::class);
    }

    /**
     * Get stock history for this part
     */
    public function stockHistory(): HasMany
    {
        return $this->hasMany(PartStockHistory::class);
    }

    /**
     * Get all maintenance requests that include this part
     */
    public function maintenanceRequests(): BelongsToMany
    {
        return $this->belongsToMany(MaintenanceRequest::class, 'maintenance_request_parts', 'part_id', 'request_id')
            ->withPivot('quantity', 'unit_cost', 'total_cost')
            ->withTimestamps();
    }

    /**
     * Get stock quantity for a specific branch
     */
    public function getStockForBranch($branchId): int
    {
        $stock = $this->stock()->where('branch_id', $branchId)->first();
        return $stock ? $stock->quantity : 0;
    }

    /**
     * Get total stock across all branches
     */
    public function getTotalStock(): int
    {
        return $this->stock()->sum('quantity');
    }
}
