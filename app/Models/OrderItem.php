<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderItem extends Model
{
    protected $fillable = ['order_id', 'product_id', 'product_name', 'option_summary', 'image', 'quantity', 'unit_price', 'total'];

    /** The photo of the version that was ordered (falls back to the product's main photo for older orders). */
    public function displayImage(): ?string
    {
        return $this->image ?: $this->product?->primaryImage();
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}