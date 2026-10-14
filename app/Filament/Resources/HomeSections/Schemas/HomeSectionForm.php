<?php

namespace App\Filament\Resources\HomeSections\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;

class HomeSectionForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('title')->required()->live(onBlur: true)->afterStateUpdated(fn ($state, $set) => $set('slug', Str::slug($state))),
            TextInput::make('slug')->required()->unique(ignoreRecord: true),
            Select::make('type')->options([
                'products' => 'Product Carousel (manual)',
                'new_arrivals' => 'New Arrivals (auto)',
                'clearance' => 'Clearance Sale (auto)',
                'featured' => 'Featured Products (auto)',
                'categories' => 'Category Tiles',
            ])->default('products')->required()->live(),
            Select::make('products')
                ->relationship('products', 'name')
                ->multiple()
                ->searchable()
                ->preload()
                ->visible(fn (Get $get): bool => $get('type') === 'products')
                ->helperText('Manual sections only. New Arrival / Clearance / Featured sections load products automatically from product flags.')
                ->columnSpanFull(),
            TextInput::make('view_more_url')->label('View More Link'),
            TextInput::make('sort_order')->numeric()->default(0),
            Toggle::make('is_active')->label('Show on Homepage')->default(true),
        ]);
    }
}