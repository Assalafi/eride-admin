<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, HasApiTokens, HasRoles;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'phone',
        'email',
        'password',
        'branch_id',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    /**
     * Get the user's role name (for API compatibility)
     */
    public function getRoleAttribute(): ?string
    {
        return $this->getRoleNames()->first();
    }

    /**
     * Get the branch that the user belongs to (legacy - for backward compatibility)
     */
    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    /**
     * Get all branches that this user has access to
     */
    public function branches(): BelongsToMany
    {
        return $this->belongsToMany(Branch::class, 'branch_user')
            ->withPivot('is_primary')
            ->withTimestamps();
    }

    /**
     * Get the primary branch for this user
     */
    public function primaryBranch(): BelongsTo
    {
        return $this->belongsTo(Branch::class, 'branch_id');
    }

    /**
     * Get the IDs of all branches this user has access to
     */
    public function getBranchIdsAttribute(): array
    {
        return $this->branches()->pluck('branches.id')->toArray();
    }

    /**
     * Check if user has access to a specific branch
     */
    public function hasAccessToBranch($branchId): bool
    {
        if ($this->hasRole('Super Admin') || $this->hasRole('Accountant')) {
            return true;
        }
        
        return $this->branches()->where('branches.id', $branchId)->exists();
    }

    /**
     * Get the primary branch ID for this user
     */
    public function getPrimaryBranchIdAttribute(): ?int
    {
        if ($this->hasRole('Super Admin') || $this->hasRole('Accountant')) {
            return null;
        }
        
        $primaryBranch = $this->branches()->where('is_primary', true)->first();
        if ($primaryBranch) {
            return $primaryBranch->id;
        }
        
        // Fallback to legacy branch_id
        return $this->branch_id;
    }

    /**
     * Get transactions approved by this user
     */
    public function approvedTransactions(): HasMany
    {
        return $this->hasMany(Transaction::class, 'approved_by');
    }

    /**
     * Get maintenance requests approved by this user
     */
    public function approvedMaintenanceRequests(): HasMany
    {
        return $this->hasMany(MaintenanceRequest::class, 'approved_by_id');
    }

    /**
     * Get maintenance requests created by this user (mechanic)
     */
    public function maintenanceRequests(): HasMany
    {
        return $this->hasMany(MaintenanceRequest::class, 'mechanic_id');
    }
}
