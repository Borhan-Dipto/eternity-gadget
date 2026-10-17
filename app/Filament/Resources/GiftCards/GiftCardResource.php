<?php

namespace App\Filament\Resources\GiftCards;

use App\Filament\Resources\GiftCards\Pages\CreateGiftCard;
use App\Filament\Resources\GiftCards\Pages\EditGiftCard;
use App\Filament\Resources\GiftCards\Pages\ListGiftCards;
use App\Filament\Resources\GiftCards\Schemas\GiftCardForm;
use App\Filament\Resources\GiftCards\Tables\GiftCardsTable;
use App\Models\GiftCard;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class GiftCardResource extends Resource
{
    /** Managed inside the parent screen (product / order); hidden from the menu so nobody edits the raw rows by mistake. */
    protected static bool $shouldRegisterNavigation = false;

    protected static ?string $model = GiftCard::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedGift;

    protected static string|UnitEnum|null $navigationGroup = 'Sales';

    protected static ?string $navigationLabel = 'Gift Cards';

    public static function form(Schema $schema): Schema
    {
        return GiftCardForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return GiftCardsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListGiftCards::route('/'),
            'create' => CreateGiftCard::route('/create'),
            'edit' => EditGiftCard::route('/{record}/edit'),
        ];
    }
}
