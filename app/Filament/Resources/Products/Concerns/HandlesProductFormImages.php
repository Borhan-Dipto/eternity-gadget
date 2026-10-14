<?php

namespace App\Filament\Resources\Products\Concerns;

use App\Models\ProductImage;
use App\Support\MediaUploadLogger;

trait HandlesProductFormImages
{
    /** @var list<string>|null */
    protected ?array $pendingGalleryImages = null;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $this->pendingGalleryImages = MediaUploadLogger::extractPaths(
            $data['product_images'] ?? null,
            'product.create.form'
        );
        unset($data['product_images']);

        MediaUploadLogger::info('product.create.form_data', [
            'product_name' => $data['name'] ?? null,
            'pending_images' => $this->pendingGalleryImages,
        ]);

        return $data;
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $this->pendingGalleryImages = MediaUploadLogger::extractPaths(
            $data['product_images'] ?? null,
            'product.edit.form'
        );
        unset($data['product_images']);

        MediaUploadLogger::info('product.edit.form_data', [
            'product_id' => $this->record?->id,
            'product_name' => $data['name'] ?? $this->record?->name,
            'pending_images' => $this->pendingGalleryImages,
        ]);

        return $data;
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        $data['product_images'] = $this->record->images()
            ->orderBy('sort_order')
            ->pluck('image')
            ->filter()
            ->values()
            ->all();

        MediaUploadLogger::info('product.form.fill', [
            'product_id' => $this->record->id,
            'db_image_paths' => $data['product_images'],
        ]);

        foreach ($data['product_images'] as $path) {
            MediaUploadLogger::auditFile($path, 'product.form.fill');
        }

        return $data;
    }

    protected function afterCreate(): void
    {
        MediaUploadLogger::info('product.after_create', [
            'product_id' => $this->record->id,
            'pending_images' => $this->pendingGalleryImages,
        ]);

        $this->syncGalleryImages('product.create');
    }

    protected function afterSave(): void
    {
        MediaUploadLogger::info('product.after_save', [
            'product_id' => $this->record->id,
            'pending_images' => $this->pendingGalleryImages,
        ]);

        $this->syncGalleryImages('product.edit');
    }

    private function syncGalleryImages(string $source): void
    {
        if ($this->pendingGalleryImages === null) {
            return;
        }

        if ($this->pendingGalleryImages === []) {
            MediaUploadLogger::warning('product.sync.cleared', [
                'source' => $source,
                'product_id' => $this->record->id,
                'message' => 'All product images removed from form',
            ]);

            $this->record->images()->delete();

            return;
        }

        $existingImages = $this->record->images()->get()->keyBy('image');
        $primaryPath = $existingImages->firstWhere('is_primary', true)?->image
            ?? $existingImages->sortBy('sort_order')->first()?->image;
        $submittedPaths = $this->pendingGalleryImages;

        if ($primaryPath && in_array($primaryPath, $submittedPaths, true)) {
            $primaryIndex = array_search($primaryPath, $submittedPaths, true);
        } else {
            $primaryIndex = 0;
        }

        $keptIds = [];

        try {
            foreach ($submittedPaths as $index => $path) {
                MediaUploadLogger::auditFile($path, "{$source}.before_db_write");

                $image = $existingImages->get($path);

                if ($image) {
                    $image->update([
                        'alt_text' => $this->record->name,
                        'sort_order' => $index,
                        'is_primary' => $index === $primaryIndex,
                    ]);

                    $keptIds[] = $image->id;

                    MediaUploadLogger::info('product.sync.updated', [
                        'source' => $source,
                        'product_id' => $this->record->id,
                        'product_image_id' => $image->id,
                        'path' => $path,
                        'sort_order' => $index,
                    ]);
                } else {
                    $created = $this->record->images()->create([
                        'image' => $path,
                        'alt_text' => $this->record->name,
                        'sort_order' => $index,
                        'is_primary' => $index === $primaryIndex,
                    ]);

                    $keptIds[] = $created->id;

                    MediaUploadLogger::info('product.sync.created', [
                        'source' => $source,
                        'product_id' => $this->record->id,
                        'product_image_id' => $created->id,
                        'path' => $path,
                        'sort_order' => $index,
                    ]);
                }

                MediaUploadLogger::auditFile($path, "{$source}.after_db_write");
            }

            if ($keptIds !== []) {
                ProductImage::query()
                    ->where('product_id', $this->record->id)
                    ->whereNotIn('id', $keptIds)
                    ->delete();
            }
        } catch (\Throwable $e) {
            MediaUploadLogger::error('product.sync.exception', [
                'source' => $source,
                'product_id' => $this->record->id,
                'paths' => $submittedPaths,
                'exception' => $e->getMessage(),
            ]);

            throw $e;
        }
    }
}
