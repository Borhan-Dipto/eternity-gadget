<?php

namespace App\Filament\Resources\Categories\Schemas;

use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;

class CategoryForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Select::make('parent_id')->label('Parent Category')->relationship('parent', 'name')->searchable()->preload(),
            TextInput::make('name')->required()->live(onBlur: true)->afterStateUpdated(fn ($state, $set) => $set('slug', Str::slug($state))),
            TextInput::make('slug')->required()->unique(ignoreRecord: true),
            FileUpload::make('image')->image()->disk('public')->directory('categories')->visibility('public')->columnSpanFull(),
            Textarea::make('description')->rows(4)->columnSpanFull(),
            TextInput::make('meta_title')->label('SEO Title'),
            Textarea::make('meta_description')->label('SEO Description')->rows(3)->columnSpanFull(),
            TextInput::make('sort_order')->numeric()->default(0),
            Toggle::make('is_featured')->label('Show in Shop By Categories'),
            Toggle::make('is_active')->label('Show on Website')->default(true),
        ]);
    }
}