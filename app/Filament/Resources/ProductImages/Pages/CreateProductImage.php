<?php

namespace App\Filament\Resources\ProductImages\Pages;

use App\Filament\Resources\ProductImages\ProductImageResource;
use App\Filament\Resources\ProductImages\Schemas\ProductImageForm;
use App\Models\Product;
use App\Models\ProductImage;
use App\Support\MediaUploadLogger;
use Filament\Resources\Pages\CreateRecord;
use Filament\Schemas\Schema;

class CreateProductImage extends CreateRecord
{
    protected static string $resource = ProductImageResource::class;

    /** @var list<string> */
    protected array $pendingImages = [];

    protected ?string $pendingAltText = null;

    protected bool $pendingIsPrimary = false;

    public function form(Schema $schema): Schema
    {
        return ProductImageForm::configureForCreate($schema);
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $this->pendingImages = MediaUploadLogger::extractPaths(
            $data['images'] ?? null,
            'product_image.direct.create'
        );
        $this->pendingAltText = $data['alt_text'] ?? null;
        $this->pendingIsPrimary = (bool) ($data['is_primary'] ?? false);

        if ($this->pendingImages === []) {
            throw new \RuntimeException('Please upload at least one image.');
        }

        return [
            'product_id' => $data['product_id'],
            'image' => $this->pendingImages[0],
            'alt_text' => $this->pendingAltText,
            'is_primary' => $this->pendingIsPrimary,
        ];
    }

    protected function handleRecordCreation(array $data): ProductImage
    {
        $product = Product::query()->findOrFail($data['product_id']);
        $maxSortOrder = (int) $product->images()->max('sort_order');
        $altText = $this->pendingAltText ?: $product->name;
        $created = null;

        foreach ($this->pendingImages as $index => $path) {
            $created = ProductImage::query()->create([
                'product_id' => $product->id,
                'image' => $path,
                'alt_text' => $altText,
                'sort_order' => $maxSortOrder + 1 + $index,
                'is_primary' => $this->pendingIsPrimary && $index === 0,
            ]);
        }

        return $created;
    }

    protected function getCreatedNotificationTitle(): ?string
    {
        $count = count($this->pendingImages);

        return $count > 1
            ? "{$count} product images created"
            : 'Product image created';
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
