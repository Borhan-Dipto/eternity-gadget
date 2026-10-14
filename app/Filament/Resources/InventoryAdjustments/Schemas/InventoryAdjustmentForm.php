<?php

namespace App\Filament\Resources\InventoryAdjustments\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class InventoryAdjustmentForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('product_id')
                    ->relationship('product', 'name')
                    ->required(),
                Select::make('product_option_id')
                    ->relationship('productOption', 'name')
                    ->default(null),
                TextInput::make('change_qty')
                    ->required()
                    ->numeric(),
                TextInput::make('reason')
                    ->required(),
                Select::make('admin_user_id')
                    ->relationship('adminUser', 'name')
                    ->default(null),
            ]);
    }
}
