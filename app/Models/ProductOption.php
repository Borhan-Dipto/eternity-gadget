<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductOption extends Model
{
    protected $fillable = [
        'product_id',
        'device_model_id',
        'name',
        'value',
        'color_hex',
        'price_adjustment',
        'stock',
        'sort_order',
        'image',
    ];

    public const GROUP_COLOR = 'Color';

    protected static function booted(): void
    {
        static::saving(function (self $option): void {
            if ($option->device_model_id) {
                $deviceModel = $option->relationLoaded('deviceModel')
                    ? $option->deviceModel
                    : DeviceModel::query()->find($option->device_model_id);

                if ($deviceModel) {
                    $option->value = $deviceModel->name;
                    $option->name = 'Compatible Device';
                }
            } elseif (blank($option->name)) {
                $option->name = 'Variant';
            }
        });
    }

    protected function casts(): array
    {
        return [
            'price_adjustment' => 'decimal:2',
        ];
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function deviceModel(): BelongsTo
    {
        return $this->belongsTo(DeviceModel::class);
    }

    public function isCompatibilityOption(): bool
    {
        return $this->device_model_id !== null;
    }

    public function isColorOption(): bool
    {
        return ! $this->isCompatibilityOption()
            && strcasecmp((string) $this->name, self::GROUP_COLOR) === 0;
    }
}