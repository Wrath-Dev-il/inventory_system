<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PriceReference extends Model
{
    protected $fillable = [
        'code',
        'name',
        'default_discount_percent',
    ];

    protected $casts = [
        'default_discount_percent' => 'decimal:2',
    ];

    public function customers(): HasMany
    {
        return $this->hasMany(Customer::class);
    }
}
