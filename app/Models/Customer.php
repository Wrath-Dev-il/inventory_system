<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Customer extends Model
{
    protected $fillable = [
        'customer_no',
        'customer_name',
        'tin',
        'price_reference_id',
        'price_reference',
        'discount_percent',
        'sales_agent_id',
        'salesman_name',
        'date_started',
        'terms',
    ];

    protected $casts = [
        'discount_percent' => 'decimal:2',
        'date_started' => 'date',
    ];

    public function priceReference(): BelongsTo
    {
        return $this->belongsTo(PriceReference::class);
    }

    public function salesAgent(): BelongsTo
    {
        return $this->belongsTo(SalesAgent::class);
    }

    public function customerAddress(): HasOne
    {
        return $this->hasOne(CustomerAddress::class);
    }
}
