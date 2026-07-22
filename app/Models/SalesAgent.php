<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class SalesAgent extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'agent_no',
        'name',
        'email',
        'phone',
        'commission_percentage',
        'date_started',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'commission_percentage' => 'decimal:2',
        'date_started' => 'date',
    ];

    public function customers(): HasMany
    {
        return $this->hasMany(Customer::class);
    }
}
