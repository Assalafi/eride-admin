<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PaymentGatewayTransaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'driver_id',
        'gateway',
        'purpose',
        'reference',
        'gateway_order_no',
        'gateway_transaction_id',
        'amount',
        'minimum_amount',
        'amount_minor',
        'currency',
        'status',
        'checkout_url',
        'transaction_id',
        'wallet_funding_request_id',
        'gateway_response',
        'callback_payload',
        'completed_at',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'minimum_amount' => 'decimal:2',
        'amount_minor' => 'integer',
        'gateway_response' => 'array',
        'callback_payload' => 'array',
        'completed_at' => 'datetime',
    ];

    public function driver(): BelongsTo
    {
        return $this->belongsTo(Driver::class);
    }

    public function transaction(): BelongsTo
    {
        return $this->belongsTo(Transaction::class);
    }

    public function walletFundingRequest(): BelongsTo
    {
        return $this->belongsTo(WalletFundingRequest::class);
    }
}
