<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SalesOrder extends Model
{
    protected $fillable = [
        'so_no',
        'customer_id',
        'customer_no_snapshot',
        'customer_name_snapshot',
        'tin_snapshot',
        'address_snapshot',
        'price_reference_snapshot',
        'sales_agent_snapshot',
        'salesman_snapshot',
        'terms_snapshot',
        'sales_channel',
        'order_date',
        'prepared_by_user_id',
        'prepared_by_name_snapshot',
        'payment_status',
        'status',
        'total_ordered_qty',
        'total_without_vat',
        'vat_exclusive_total',
        'vat_amount',
        'total_with_vat',
        'confirmed_at',
    ];

    protected $casts = [
        'order_date' => 'date',
        'confirmed_at' => 'datetime',
        'total_ordered_qty' => 'decimal:2',
        'total_without_vat' => 'decimal:2',
        'vat_exclusive_total' => 'decimal:2',
        'vat_amount' => 'decimal:2',
        'total_with_vat' => 'decimal:2',
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(SalesOrderItem::class);
    }

    public function preparedBy(): BelongsTo
    {
        return $this->belongsTo(Login::class, 'prepared_by_user_id', 'login_ID');
    }

    public function statusLogs(): HasMany
    {
        return $this->hasMany(SalesOrderStatusLog::class);
    }
}
