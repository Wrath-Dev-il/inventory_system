<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SalesOrderItem extends Model
{
    protected $fillable = [
        'sales_order_id',
        'product_id',
        'item_no_snapshot',
        'product_name_snapshot',
        'brand_snapshot',
        'unit_snapshot',
        'ordered_qty',
        'selling_price_snapshot',
        'discount_percent_snapshot',
        'unit_price_without_vat',
        'line_total_without_vat',
        'vat_amount',
        'line_total_with_vat',
    ];

    protected $casts = [
        'ordered_qty' => 'decimal:2',
        'selling_price_snapshot' => 'decimal:2',
        'discount_percent_snapshot' => 'decimal:2',
        'unit_price_without_vat' => 'decimal:2',
        'line_total_without_vat' => 'decimal:2',
        'vat_amount' => 'decimal:2',
        'line_total_with_vat' => 'decimal:2',
    ];

    public function salesOrder(): BelongsTo
    {
        return $this->belongsTo(SalesOrder::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class)->withTrashed();
    }
}
