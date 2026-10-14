<?php

namespace App\Filament\Resources\Sliders\Schemas;

use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class SliderForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('title')->label('Main Heading'),
            TextInput::make('subtitle')->label('Small Heading'),
            Textarea::make('description')->rows(3)->columnSpanFull(),
            TextInput::make('button_text')->label('Button Text'),
            TextInput::make('button_url')->label('Button Link'),
            FileUpload::make('image')->image()->disk('public')->directory('sliders')->visibility('public')->required()->columnSpanFull(),
            TextInput::make('sort_order')->numeric()->default(0),
            Toggle::make('is_active')->label('Show on Website')->default(true),
        ]);
    }
}