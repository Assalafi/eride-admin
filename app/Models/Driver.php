<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use App\Models\HirePurchaseContract;
use App\Models\HirePurchasePayment;

class Driver extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'branch_id',
        'first_name',
        'last_name',
        'phone_number',
        'is_hire_purchase',
        'hire_purchase_status',
    ];

    protected $casts = [
        'is_hire_purchase' => 'boolean',
    ];

    /**
     * Get the user account for this driver
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the branch this driver belongs to
     */
    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    /**
     * Get the wallet for this driver
     */
    public function wallet(): HasOne
    {
        return $this->hasOne(Wallet::class);
    }

    /**
     * Get all vehicle assignments for this driver
     */
    public function vehicleAssignments(): HasMany
    {
        return $this->hasMany(VehicleAssignment::class);
    }

    /**
     * Get all daily ledgers for this driver
     */
    public function dailyLedgers(): HasMany
    {
        return $this->hasMany(DailyLedger::class);
    }

    /**
     * Get all transactions for this driver
     */
    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }

    /**
     * Get all maintenance requests for this driver
     */
    public function maintenanceRequests(): HasMany
    {
        return $this->hasMany(MaintenanceRequest::class);
    }

    /**
     * Get all wallet funding requests for this driver
     */
    public function walletFundingRequests(): HasMany
    {
        return $this->hasMany(WalletFundingRequest::class);
    }

    /**
     * Get all hire purchase contracts for this driver
     */
    public function hirePurchaseContracts(): HasMany
    {
        return $this->hasMany(HirePurchaseContract::class);
    }

    /**
     * Get the active hire purchase contract
     */
    public function activeHirePurchaseContract(): HasOne
    {
        return $this->hasOne(HirePurchaseContract::class)->where('status', 'active');
    }

    /**
     * Get all hire purchase payments for this driver
     */
    public function hirePurchasePayments(): HasMany
    {
        return $this->hasMany(HirePurchasePayment::class);
    }

    /**
     * Get the full name attribute
     */
    public function getFullNameAttribute(): string
    {
        return "{$this->first_name} {$this->last_name}";
    }

    /**
     * Check if driver has active hire purchase
     */
    public function hasActiveHirePurchase(): bool
    {
        return $this->is_hire_purchase && $this->hire_purchase_status === 'active';
    }
}
