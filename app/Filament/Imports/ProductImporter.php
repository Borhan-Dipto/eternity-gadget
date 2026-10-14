<?php

namespace App\Filament\Imports;

use App\Models\Product;
use Filament\Actions\Imports\ImportColumn;
use Filament\Actions\Imports\Importer;
use Filament\Actions\Imports\Models\Import;
use Illuminate\Support\Number;
use Illuminate\Support\Str;

class ProductImporter extends Importer
{
    protected static ?string $model = Product::class;

    public static function getColumns(): array
    {
        return [
            ImportColumn::make('name')
                ->requiredMapping()
                ->rules(['required', 'max:255']),
            ImportColumn::make('slug')
                ->rules(['max:255']),
            ImportColumn::make('sku')
                ->label('SKU')
                ->rules(['max:64']),
            ImportColumn::make('category_id')
                ->numeric()
                ->rules(['nullable', 'integer']),
            ImportColumn::make('short_description'),
            ImportColumn::make('description'),
            ImportColumn::make('price')
                ->numeric()
                ->rules(['required', 'numeric', 'min:0']),
            ImportColumn::make('sale_price')
                ->numeric()
                ->rules(['nullable', 'numeric', 'min:0']),
            ImportColumn::make('stock')
                ->numeric()
                ->rules(['nullable', 'integer', 'min:0']),
            ImportColumn::make('weight')
                ->numeric()
                ->rules(['nullable', 'numeric', 'min:0']),
            ImportColumn::make('video_url')
                ->rules(['nullable', 'url', 'max:500']),
            ImportColumn::make('is_active')
                ->boolean()
                ->rules(['nullable', 'boolean']),
            ImportColumn::make('is_featured')
                ->boolean()
                ->rules(['nullable', 'boolean']),
            ImportColumn::make('meta_title')
                ->rules(['nullable', 'max:255']),
            ImportColumn::make('meta_description')
                ->rules(['nullable', 'max:500']),
        ];
    }

    public function resolveRecord(): Product
    {
        $sku = $this->data['sku'] ?? null;

        if ($sku) {
            $existing = Product::query()->where('sku', $sku)->first();
            if ($existing) {
                return $existing;
            }
        }

        if (! empty($this->data['slug'])) {
            $existing = Product::query()->where('slug', $this->data['slug'])->first();
            if ($existing) {
                return $existing;
            }
        }

        return new Product();
    }

    protected function beforeCreate(): void
    {
        if (empty($this->record->slug) && ! empty($this->data['name'])) {
            $this->record->slug = Str::slug($this->data['name']);
        }

        if ($this->record->is_active === null) {
            $this->record->is_active = true;
        }

        if ($this->record->stock === null) {
            $this->record->stock = 0;
        }
    }

    public static function getCompletedNotificationBody(Import $import): string
    {
        $body = 'Your product import has completed and ' . Number::format($import->successful_rows) . ' ' . str('row')->plural($import->successful_rows) . ' imported.';

        if ($failedRowsCount = $import->getFailedRowsCount()) {
            $body .= ' ' . Number::format($failedRowsCount) . ' ' . str('row')->plural($failedRowsCount) . ' failed to import.';
        }

        return $body;
    }
}
