<?php

namespace App\Models\Concerns;

use App\Support\MediaPath;
use App\Support\MediaUploadLogger;
use Illuminate\Support\Facades\Storage;

trait NormalizesMediaPath
{
    public static function bootNormalizesMediaPath(): void
    {
        static::saving(function ($model) {
            if (! isset($model->attributes['image'])) {
                return;
            }

            $before = $model->attributes['image'];
            $normalized = MediaPath::normalize($before);
            $model->attributes['image'] = $normalized;

            MediaUploadLogger::info('model.saving', [
                'model' => class_basename($model),
                'id' => $model->getKey(),
                'before' => is_scalar($before) ? $before : json_encode($before),
                'after' => $normalized,
            ]);

            if ($normalized && ! str_starts_with($normalized, 'http')) {
                MediaUploadLogger::auditFile($normalized, 'model.saving.' . class_basename($model));
            }
        });

        static::saved(function ($model) {
            if (! isset($model->attributes['image'])) {
                return;
            }

            MediaUploadLogger::info('model.saved', [
                'model' => class_basename($model),
                'id' => $model->getKey(),
                'image' => $model->attributes['image'] ?? null,
            ]);
        });

        static::deleting(function ($model) {
            $path = MediaPath::normalize($model->getRawOriginal('image'));

            MediaUploadLogger::info('model.deleting', [
                'model' => class_basename($model),
                'id' => $model->getKey(),
                'path' => $path,
            ]);

            if ($path && ! str_starts_with($path, 'http')) {
                Storage::disk('public')->delete($path);
            }
        });
    }

    public function getImageAttribute(?string $value): ?string
    {
        return MediaPath::normalize($value);
    }
}
