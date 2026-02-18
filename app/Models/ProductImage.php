<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductImage extends Model
{
    protected $fillable = [
        'product_id',
        'image_url',
        'is_primary',
    ];

    protected $casts = [
        'is_primary' => 'boolean',
    ];

    protected $appends = ['full_url'];

    public function getFullUrlAttribute()
    {
        if (str_starts_with($this->image_url, 'http')) {
            return $this->image_url;
        }

        return url($this->image_url);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
    
}

