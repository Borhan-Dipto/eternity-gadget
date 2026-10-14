<?php

namespace App\Filament\Exports;

use App\Models\Product;
use Filament\Actions\Exports\ExportColumn;
use Filament\Actions\Exports\Exporter;
use Filament\Actions\Exports\Models\Export;
use Illuminate\Support\Number;

class ProductExporter extends Exporter
{
    protected static ?string $model = Product::class;

    public static function getColumns(): array
    {
        return [
            ExportColumn::make('id'),
            ExportColumn::make('category_id'),
            ExportColumn::make('name'),
            ExportColumn::make('slug'),
            ExportColumn::make('sku')->label('SKU'),
            ExportColumn::make('short_description'),
            ExportColumn::make('price'),
            ExportColumn::make('sale_price'),
            ExportColumn::make('stock'),
            ExportColumn::make('weight'),
            ExportColumn::make('video_url'),
            ExportColumn::make('is_active'),
            ExportColumn::make('is_featured'),
            ExportColumn::make('is_new'),
            ExportColumn::make('is_clearance'),
            ExportColumn::make('allow_backorder'),
            ExportColumn::make('is_preorder'),
            ExportColumn::make('meta_title'),
            ExportColumn::make('meta_description'),
        ];
    }

    public static function getCompletedNotificationBody(Export $export): string
    {
        $body = 'Your product export has completed and ' . Number::format($export->successful_rows) . ' ' . str('row')->plural($export->successful_rows) . ' exported.';

        if ($failedRowsCount = $export->getFailedRowsCount()) {
            $body .= ' ' . Number::format($failedRowsCount) . ' ' . str('row')->plural($failedRowsCount) . ' failed to export.';
        }

        return $body;
    }
}
