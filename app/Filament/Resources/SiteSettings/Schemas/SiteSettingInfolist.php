<?php

namespace App\Filament\Resources\SiteSettings\Schemas;

use Filament\Infolists\Components\ImageEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class SiteSettingInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('group'),
                TextEntry::make('key'),
                TextEntry::make('label'),
                TextEntry::make('type'),
                ImageEntry::make('value')
                    ->disk('public')
                    ->visible(fn ($record): bool => $record?->type === 'image')
                    ->columnSpanFull(),
                TextEntry::make('value')
                    ->placeholder('-')
                    ->visible(fn ($record): bool => $record?->type !== 'image')
                    ->columnSpanFull(),
                TextEntry::make('sort_order')
                    ->numeric(),
                TextEntry::make('created_at')
                    ->dateTime()
                    ->placeholder('-'),
                TextEntry::make('updated_at')
                    ->dateTime()
                    ->placeholder('-'),
            ]);
    }
}
