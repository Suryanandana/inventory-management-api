<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Product extends Model
{
    protected $fillable = [
        'category_id',
        'name',
        'slug',
        'description',
        'price',
        'is_active',
    ];

    protected $casts = [
        'price' => 'float',
        'is_active' => 'boolean',
    ];

    // Relations
    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function images()
    {
        return $this->hasMany(ProductImage::class);
    }

    public function variants()
    {
        return $this->hasMany(ProductVariant::class);
    }

    // Helpers
    public function primaryImage()
    {
        return $this->images()->where('is_primary', true)->first();
    }

    public function minPrice()
    {
        return $this->variants()->min('price') ?? $this->price;
    }

    protected static function booted()
    {
        static::creating(function ($product) {
            if (! $product->slug) {
                $product->slug = Str::slug($product->name);
            }
        });
    }
}
