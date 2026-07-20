<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SalesOrderSequence extends Model
{
    protected $fillable = ['month_year', 'last_sequence'];

    protected $casts = [
        'last_sequence' => 'integer',
    ];
}
