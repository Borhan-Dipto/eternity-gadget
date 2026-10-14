<?php

namespace App\Filament\Resources\WishlistItems;

use App\Filament\Resources\WishlistItems\Pages\CreateWishlistItem;
use App\Filament\Resources\WishlistItems\Pages\EditWishlistItem;
use App\Filament\Resources\WishlistItems\Pages\ListWishlistItems;
use App\Filament\Resources\WishlistItems\Pages\ViewWishlistItem;
use App\Filament\Resources\WishlistItems\Schemas\WishlistItemForm;
use App\Filament\Resources\WishlistItems\Schemas\WishlistItemInfolist;
use App\Filament\Resources\WishlistItems\Tables\WishlistItemsTable;
use App\Models\WishlistItem;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class WishlistItemResource extends Resource
{
    protected static ?string $model = WishlistItem::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedHeart;

    protected static string|UnitEnum|null $navigationGroup = 'Customers';

    protected static ?string $navigationLabel = 'Wishlists';

    protected static ?string $modelLabel = 'Wishlist Item';

    protected static ?string $pluralModelLabel = 'Wishlist Items';

    public static function form(Schema $schema): Schema
    {
        return WishlistItemForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return WishlistItemInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return WishlistItemsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListWishlistItems::route('/'),
            'create' => CreateWishlistItem::route('/create'),
            'view' => ViewWishlistItem::route('/{record}'),
            'edit' => EditWishlistItem::route('/{record}/edit'),
        ];
    }
}