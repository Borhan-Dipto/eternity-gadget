<?php

namespace App\Models;

use App\Models\Concerns\NormalizesMediaPath;
use App\Support\MediaUploadLogger;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductImage extends Model
{
    use NormalizesMediaPath;

    protected $fillable = ['product_id', 'image', 'alt_text', 'sort_order', 'is_primary', 'option_ids'];

    protected $casts = ['option_ids' => 'array'];

    protected static function booted(): void
    {
        static::creating(function (self $image) {
            MediaUploadLogger::info('product_image.creating', [
                'product_id' => $image->product_id,
                'image' => $image->getAttributes()['image'] ?? null,
                'is_primary' => $image->is_primary,
            ]);

            if ($image->product_id && $image->sort_order === null) {
                self::query()
                    ->where('product_id', $image->product_id)
                    ->increment('sort_order');

                $image->sort_order = 0;
            }
        });

        static::created(function (self $image) {
            MediaUploadLogger::info('product_image.created', [
                'id' => $image->id,
                'product_id' => $image->product_id,
                'image' => $image->image,
                'is_primary' => $image->is_primary,
            ]);

            MediaUploadLogger::auditFile($image->image, 'product_image.created');
        });

        static::saved(function (self $image) {
            if (! $image->is_primary) {
                return;
            }

            self::query()
                ->where('product_id', $image->product_id)
                ->whereKeyNot($image->id)
                ->update(['is_primary' => false]);
        });
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}