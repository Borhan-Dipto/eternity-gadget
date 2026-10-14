<?php

namespace App\Filament\Resources\ContactButtons;

use App\Filament\Resources\ContactButtons\Pages\CreateContactButton;
use App\Filament\Resources\ContactButtons\Pages\EditContactButton;
use App\Filament\Resources\ContactButtons\Pages\ListContactButtons;
use App\Filament\Resources\ContactButtons\Pages\ViewContactButton;
use App\Filament\Resources\ContactButtons\Schemas\ContactButtonForm;
use App\Filament\Resources\ContactButtons\Schemas\ContactButtonInfolist;
use App\Filament\Resources\ContactButtons\Tables\ContactButtonsTable;
use App\Models\ContactButton;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class ContactButtonResource extends Resource
{
    protected static ?string $model = ContactButton::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedChatBubbleLeftRight;

    protected static string|UnitEnum|null $navigationGroup = 'Settings';

    protected static ?string $navigationLabel = 'Contact Buttons';

    protected static ?string $modelLabel = 'Contact Button';

    protected static ?string $pluralModelLabel = 'Contact Buttons';

    public static function form(Schema $schema): Schema
    {
        return ContactButtonForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return ContactButtonInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ContactButtonsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListContactButtons::route('/'),
            'create' => CreateContactButton::route('/create'),
            'view' => ViewContactButton::route('/{record}'),
            'edit' => EditContactButton::route('/{record}/edit'),
        ];
    }
}