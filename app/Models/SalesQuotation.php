<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SalesQuotation extends Model
{
    protected $fillable = [
        'quotation_no',
        'quotation_date',
        'customer_id',
        'customer_no_snapshot',
        'customer_name_snapshot',
        'price_reference_snapshot',
        'terms_snapshot',
        'tin_snapshot',
        'address_snapshot',
        'sales',
        'remarks',
        'payment_terms',
        'cancellation_terms',
        'delivery_terms',
        'lead_time_at',
        'valid_until',
        'warranty',
        'mode_of_payment',
        'subtotal',
        'tax_amount',
        'grand_total',
        'status',
        'prepared_by_user_id',
        'prepared_by_name_snapshot',
        'attention_to',
    ];

    protected $casts = [
        'quotation_date' => 'date',
        'lead_time_at' => 'datetime',
        'valid_until' => 'datetime',
        'subtotal' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'grand_total' => 'decimal:2',
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(SalesQuotationItem::class);
    }

    public function preparedBy(): BelongsTo
    {
        return $this->belongsTo(Login::class, 'prepared_by_user_id', 'login_ID');
    }
}
