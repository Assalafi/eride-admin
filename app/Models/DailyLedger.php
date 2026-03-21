<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DailyLedger extends Model
{
    use HasFactory;

    protected $fillable = [
        'driver_id',
        'date',
        'required_payment',
        'amount_paid',
        'balance',
        'status',
    ];

    protected $casts = [
        'date' => 'date',
        'required_payment' => 'decimal:2',
        'amount_paid' => 'decimal:2',
        'balance' => 'decimal:2',
    ];

    /**
     * Get the driver for this ledger entry
     */
    public function driver(): BelongsTo
    {
        return $this->belongsTo(Driver::class);
    }

    /**
     * Update the ledger status based on payment
     */
    public function updateStatus(): void
    {
        if ($this->amount_paid >= $this->required_payment) {
            $this->status = 'paid';
        } elseif ($this->amount_paid > 0) {
            $this->status = 'partially_paid';
        } else {
            $this->status = 'due';
        }
        
        $this->balance = $this->required_payment - $this->amount_paid;
        $this->save();
    }
}
