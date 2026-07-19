<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ItemSource extends Model
{
    protected $fillable = ['name'];

    public function equivalencies()
    {
        return $this->hasMany(ItemSourceEquivalency::class);
    }

    public function currentEquivalency()
    {
        return $this->hasOne(ItemSourceEquivalency::class)->latest('id');
    }
}
