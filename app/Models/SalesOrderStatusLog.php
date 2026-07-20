<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SalesOrderStatusLog extends Model
{
    protected $fillable = [
        'sales_order_id',
        'from_status',
        'to_status',
        'changed_by',
        'remarks',
    ];

    public function salesOrder(): BelongsTo
    {
        return $this->belongsTo(SalesOrder::class);
    }

    public function changedBy(): BelongsTo
    {
        return $this->belongsTo(Login::class, 'changed_by', 'login_ID');
    }
}
