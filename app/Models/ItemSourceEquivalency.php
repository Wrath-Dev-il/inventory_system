<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ItemSourceEquivalency extends Model
{
    protected $fillable = [
        'item_source_id',
        'exchange_rate_id',
        'multiplier',
        'yuan_amount',
        'peso_amount',
        'rate_used',
        'converted_at',
        'created_by',
    ];

    protected $casts = [
        'multiplier' => 'decimal:6',
        'yuan_amount' => 'decimal:4',
        'peso_amount' => 'decimal:4',
        'rate_used' => 'decimal:8',
        'converted_at' => 'datetime',
    ];

    public function itemSource()
    {
        return $this->belongsTo(ItemSource::class);
    }

    public function exchangeRate()
    {
        return $this->belongsTo(ExchangeRate::class);
    }
}
