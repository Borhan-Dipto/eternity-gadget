<?php

namespace App\Filament\Resources\Pages\Schemas;

use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;

class PageForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('title')->required()->live(onBlur: true)->afterStateUpdated(fn ($state, $set) => $set('slug', Str::slug($state))),
            TextInput::make('slug')->required()->unique(ignoreRecord: true),
            TextInput::make('meta_title')->label('SEO Title'),
            Textarea::make('meta_description')->label('SEO Description')->rows(3)->columnSpanFull(),
            RichEditor::make('content')->label('Page Content')->columnSpanFull(),
            Toggle::make('is_published')->label('Published')->default(true),
        ]);
    }
}