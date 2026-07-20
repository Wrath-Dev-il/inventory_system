<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ItemSource extends Model
{
    use SoftDeletes;

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
