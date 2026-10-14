<?php

namespace App\Filament\Resources\WishlistItems\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class WishlistItemForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('user_id')
                    ->numeric()
                    ->default(null),
                TextInput::make('session_id')
                    ->default(null),
                Select::make('product_id')
                    ->relationship('product', 'name')
                    ->required(),
            ]);
    }
}
