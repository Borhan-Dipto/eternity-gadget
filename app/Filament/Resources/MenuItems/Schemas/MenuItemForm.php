<?php

namespace App\Filament\Resources\MenuItems\Schemas;

use App\Models\MenuItem;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class MenuItemForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('menu_id')
                    ->relationship('menu', 'name')
                    ->required(),
                Select::make('parent_id')
                    ->label('Parent item')
                    ->options(fn () => MenuItem::query()
                        ->orderBy('sort_order')
                        ->get()
                        ->mapWithKeys(fn (MenuItem $item) => [
                            $item->id => trim(($item->parent_id ? '— ' : '').$item->label),
                        ]))
                    ->searchable()
                    ->nullable()
                    ->helperText('Leave empty for top-level. Nest under Mobile Cases → iPhone to show new models in the header.'),
                TextInput::make('label')
                    ->required()
                    ->helperText('Example: iPhone 18'),
                TextInput::make('url')
                    ->label('URL / path')
                    ->placeholder('/category/iphone-18')
                    ->helperText('Use a category path like /category/iphone-18 after creating the category.')
                    ->default(null),
                TextInput::make('badge')
                    ->default(null),
                TextInput::make('badge_type')
                    ->default(null),
                TextInput::make('sort_order')
                    ->required()
                    ->numeric()
                    ->default(0),
                Toggle::make('is_active')
                    ->required()
                    ->default(true),
            ]);
    }
}
