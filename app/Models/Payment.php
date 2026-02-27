<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'product_id',
        'variant_id',
        'external_id',
        'invoice_id',
        'description',
        'amount',
        'currency',
        'status',
        'xendit_response',
    ];

    protected $casts = [
        'xendit_response' => 'array',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function variant()
    {
        return $this->belongsTo(ProductVariant::class);
    }
}
