<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AbandonedCart extends Model
{
    protected $fillable = [
        'session_id',
        'user_id',
        'cart_snapshot',
        'emailed_at',
    ];

    protected function casts(): array
    {
        return [
            'cart_snapshot' => 'array',
            'emailed_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
