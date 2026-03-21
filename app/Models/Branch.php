<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Branch extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'location',
    ];

    /**
     * Get all users belonging to this branch (legacy - for backward compatibility)
     */
    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    /**
     * Get all users with access to this branch
     */
    public function assignedUsers(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'branch_user')
            ->withPivot('is_primary')
            ->withTimestamps();
    }

    /**
     * Get managers assigned to this branch
     */
    public function managers(): BelongsToMany
    {
        return $this->assignedUsers()->whereHas('roles', function ($query) {
            $query->where('name', 'Branch Manager');
        });
    }

    /**
     * Get all drivers belonging to this branch
     */
    public function drivers(): HasMany
    {
        return $this->hasMany(Driver::class);
    }

    /**
     * Get all vehicles belonging to this branch
     */
    public function vehicles(): HasMany
    {
        return $this->hasMany(Vehicle::class);
    }

    /**
     * Get all part stock belonging to this branch
     */
    public function partStock(): HasMany
    {
        return $this->hasMany(PartStock::class);
    }
}
