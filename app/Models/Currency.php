<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Currency extends Model
{
    protected $fillable = [
        'code',
        'name',
        'symbol',
        'rate_to_bdt',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'rate_to_bdt' => 'decimal:8',
            'is_active' => 'boolean',
        ];
    }

    public function convertToBdt(float $amount): float
    {
        return $amount * (float) $this->rate_to_bdt;
    }
}
