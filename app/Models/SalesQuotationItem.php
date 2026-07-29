<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SalesQuotationItem extends Model
{
    protected $fillable = [
        'sales_quotation_id',
        'product_id',
        'item_no_snapshot',
        'item_name_snapshot',
        'brand_snapshot',
        'unit_snapshot',
        'available_quantity_snapshot',
        'quantity',
        'offer_description',
        'discount_percent',
        'unit_price_without_tax_snapshot',
        'unit_price',
        'unit_price_with_tax',
        'tax_amount',
        'line_total',
    ];

    protected $casts = [
        'available_quantity_snapshot' => 'decimal:2',
        'quantity' => 'decimal:2',
        'discount_percent' => 'decimal:2',
        'unit_price_without_tax_snapshot' => 'decimal:2',
        'unit_price' => 'decimal:2',
        'unit_price_with_tax' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'line_total' => 'decimal:2',
    ];

    public function salesQuotation(): BelongsTo
    {
        return $this->belongsTo(SalesQuotation::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class)->withTrashed();
    }
}
