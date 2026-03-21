<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PartStock extends Model
{
    use HasFactory;

    protected $table = 'part_stock';

    protected $fillable = [
        'part_id',
        'branch_id',
        'quantity',
    ];

    /**
     * Get the part for this stock record
     */
    public function part(): BelongsTo
    {
        return $this->belongsTo(Part::class);
    }

    /**
     * Get the branch for this stock record
     */
    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }
}
