<?php

namespace App\Filament\Resources\DeviceModels\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;

class DeviceModelForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Select::make('brand')
                ->label('Brand')
                ->options([
                    'Apple' => 'Apple',
                    'Samsung' => 'Samsung',
                    'Google' => 'Google',
                    'Xiaomi' => 'Xiaomi',
                    'OnePlus' => 'OnePlus',
                    'Other' => 'Other',
                ])
                ->searchable()
                ->helperText('Used to group devices (e.g. Apple → iPhone 18).'),
            TextInput::make('name')
                ->label('Model name')
                ->required()
                ->live(onBlur: true)
                ->afterStateUpdated(function ($state, callable $set, callable $get): void {
                    if (! filled($get('slug'))) {
                        $set('slug', Str::slug(($get('brand') ? $get('brand').'-' : '').$state));
                    }
                })
                ->helperText('Example: iPhone 18, Galaxy S26 Ultra, Apple Nitin.'),
            TextInput::make('slug')
                ->required()
                ->unique(ignoreRecord: true),
            TextInput::make('sort_order')
                ->numeric()
                ->default(0),
            Toggle::make('is_active')
                ->label('Available for products')
                ->default(true),
        ]);
    }
}
