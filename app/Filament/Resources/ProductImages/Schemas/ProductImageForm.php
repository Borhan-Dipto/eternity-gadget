<?php

namespace App\Filament\Resources\ProductImages\Schemas;

use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class ProductImageForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Select::make('product_id')->label('Product')->relationship('product', 'name')->searchable()->preload()->required(),
            FileUpload::make('image')
                ->label('Image')
                ->image()
                ->disk('public')
                ->directory('products')
                ->visibility('public')
                ->required()
                ->columnSpanFull(),
            TextInput::make('alt_text')->label('Image Alt Text'),
            TextInput::make('sort_order')->numeric()->default(0),
            Toggle::make('is_primary')->label('Main Product Image'),
        ]);
    }

    public static function configureForCreate(Schema $schema): Schema
    {
        return $schema->components([
            Select::make('product_id')->label('Product')->relationship('product', 'name')->searchable()->preload()->required(),
            FileUpload::make('images')
                ->label('Images')
                ->image()
                ->multiple()
                ->reorderable()
                ->disk('public')
                ->directory('products')
                ->visibility('public')
                ->required()
                ->columnSpanFull()
                ->helperText('Select multiple files at once. Drag to set gallery order.'),
            TextInput::make('alt_text')->label('Image Alt Text'),
            Toggle::make('is_primary')->label('Set first upload as main image')->default(false),
        ]);
    }
}
