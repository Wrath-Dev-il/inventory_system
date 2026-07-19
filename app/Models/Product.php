<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    protected $fillable = [
        'item_no',
        'product',
        'brand',
        'unit',
        'qty',
        'restock_level',
        'item_source',
        'item_source_id',
        'cost_currency',
        'cost_value',
        'cost_in_yuan',
        'cost_in_peso',
        'selling_price',
        'price_online',
        'sea_freight',
        'air_freight',
    ];

    protected $casts = [
        'qty' => 'decimal:2',
        'restock_level' => 'decimal:2',
        'cost_value' => 'decimal:2',
        'cost_in_yuan' => 'decimal:2',
        'cost_in_peso' => 'decimal:2',
        'selling_price' => 'decimal:2',
        'price_online' => 'decimal:2',
        'sea_freight' => 'decimal:2',
        'air_freight' => 'decimal:2',
    ];

    public function itemSource()
    {
        return $this->belongsTo(ItemSource::class);
    }
}
