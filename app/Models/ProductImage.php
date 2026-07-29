<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductImage extends Model
{
    protected $fillable = [
        'product_id',
        'image_data',
        'thumbnail_data',
        'mime_type',
        'original_name',
        'file_size',
        'checksum',
    ];

    protected $hidden = [
        'image_data',
        'thumbnail_data',
    ];

    protected $casts = [
        'file_size' => 'integer',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}