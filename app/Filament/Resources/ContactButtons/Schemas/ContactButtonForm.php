<?php

namespace App\Filament\Resources\ContactButtons\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class ContactButtonForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('label')->required(),
            Select::make('type')->options([
                'telephone' => 'Phone Call',
                'messenger' => 'Messenger',
                'whatsapp' => 'WhatsApp',
                'email' => 'Email',
                'custom' => 'Custom Link',
            ])->required(),
            TextInput::make('url')->label('Link')->required()->columnSpanFull(),
            TextInput::make('sort_order')->numeric()->default(0),
            Toggle::make('is_active')->label('Show on Website')->default(true),
        ]);
    }
}