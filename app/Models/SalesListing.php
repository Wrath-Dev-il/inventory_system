<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SalesListing extends Model
{
    protected $fillable = [
        'sales_order_id',
        'billing_date',
        'due_date',
        'transaction_type',
        'po_no',
        'sales_invoice_no',
        'quotation_no',
        'initial_payment_status',
        'final_payment_status',
        'actual_payment_remarks',
        'updated_by',
    ];

    protected $casts = [
        'billing_date' => 'date',
        'due_date' => 'date',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function salesOrder(): BelongsTo
    {
        return $this->belongsTo(SalesOrder::class);
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(Login::class, 'updated_by', 'login_ID');
    }
}
