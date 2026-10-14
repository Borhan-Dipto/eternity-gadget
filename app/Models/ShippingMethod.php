<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ShippingMethod extends Model
{
    protected $fillable = [
        'name',
        'code',
        'base_cost',
        'cost_per_kg',
        'min_days',
        'max_days',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'base_cost' => 'decimal:2',
            'cost_per_kg' => 'decimal:2',
            'min_days' => 'integer',
            'max_days' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    public function calculateCost(float $weightKg): float
    {
        return (float) $this->base_cost + ((float) $this->cost_per_kg * $weightKg);
    }
}
