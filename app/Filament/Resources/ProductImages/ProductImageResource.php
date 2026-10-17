<?php

namespace App\Filament\Resources\ProductImages;

use App\Filament\Resources\ProductImages\Pages\CreateProductImage;
use App\Filament\Resources\ProductImages\Pages\EditProductImage;
use App\Filament\Resources\ProductImages\Pages\ListProductImages;
use App\Filament\Resources\ProductImages\Pages\ViewProductImage;
use App\Filament\Resources\ProductImages\Schemas\ProductImageForm;
use App\Filament\Resources\ProductImages\Schemas\ProductImageInfolist;
use App\Filament\Resources\ProductImages\Tables\ProductImagesTable;
use App\Models\ProductImage;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class ProductImageResource extends Resource
{
    /** Managed inside the parent screen (product / order); hidden from the menu so nobody edits the raw rows by mistake. */
    protected static bool $shouldRegisterNavigation = false;

    protected static ?string $model = ProductImage::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedPhoto;

    protected static string|UnitEnum|null $navigationGroup = 'Catalog';

    protected static ?string $navigationLabel = 'Product Images';

    protected static ?string $modelLabel = 'Product Image';

    protected static ?string $pluralModelLabel = 'Product Images';

    public static function form(Schema $schema): Schema
    {
        return ProductImageForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return ProductImageInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ProductImagesTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListProductImages::route('/'),
            'create' => CreateProductImage::route('/create'),
            'view' => ViewProductImage::route('/{record}'),
            'edit' => EditProductImage::route('/{record}/edit'),
        ];
    }
}