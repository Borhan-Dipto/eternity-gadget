<?php

namespace App\Filament\Resources\ShippingMethods\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class ShippingMethodForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->required(),
                TextInput::make('code')
                    ->required(),
                TextInput::make('base_cost')
                    ->required()
                    ->numeric()
                    ->default(0.0)
                    ->prefix('$'),
                TextInput::make('cost_per_kg')
                    ->required()
                    ->numeric()
                    ->default(0.0),
                TextInput::make('min_days')
                    ->required()
                    ->numeric()
                    ->default(1),
                TextInput::make('max_days')
                    ->required()
                    ->numeric()
                    ->default(7),
                Toggle::make('is_active')
                    ->required(),
            ]);
    }
}
