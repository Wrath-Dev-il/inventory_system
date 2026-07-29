<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SalesQuotationSequence extends Model
{
    protected $fillable = ['year', 'month', 'last_sequence'];

    protected $casts = [
        'year' => 'integer',
        'month' => 'integer',
        'last_sequence' => 'integer',
    ];
}
