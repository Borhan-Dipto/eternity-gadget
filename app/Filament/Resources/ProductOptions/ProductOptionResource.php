<?php

namespace App\Filament\Resources\ProductOptions;

use App\Filament\Resources\ProductOptions\Pages\CreateProductOption;
use App\Filament\Resources\ProductOptions\Pages\EditProductOption;
use App\Filament\Resources\ProductOptions\Pages\ListProductOptions;
use App\Filament\Resources\ProductOptions\Pages\ViewProductOption;
use App\Filament\Resources\ProductOptions\Schemas\ProductOptionForm;
use App\Filament\Resources\ProductOptions\Schemas\ProductOptionInfolist;
use App\Filament\Resources\ProductOptions\Tables\ProductOptionsTable;
use App\Models\ProductOption;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class ProductOptionResource extends Resource
{
    /** Managed inside the parent screen (product / order); hidden from the menu so nobody edits the raw rows by mistake. */
    protected static bool $shouldRegisterNavigation = false;

    protected static ?string $model = ProductOption::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedAdjustmentsHorizontal;

    protected static string|UnitEnum|null $navigationGroup = 'Catalog';

    protected static ?string $navigationLabel = 'Product Options';

    protected static ?string $modelLabel = 'Product Option';

    protected static ?string $pluralModelLabel = 'Product Options';

    public static function form(Schema $schema): Schema
    {
        return ProductOptionForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return ProductOptionInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ProductOptionsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListProductOptions::route('/'),
            'create' => CreateProductOption::route('/create'),
            'view' => ViewProductOption::route('/{record}'),
            'edit' => EditProductOption::route('/{record}/edit'),
        ];
    }
}