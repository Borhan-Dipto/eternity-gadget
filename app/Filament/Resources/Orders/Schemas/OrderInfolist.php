<?php

namespace App\Filament\Resources\Orders\Schemas;

use App\Support\AdSource;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\ViewEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class OrderInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                ViewEntry::make('invoice')
                    ->hiddenLabel()
                    ->view('filament.orders.invoice-panel')
                    ->columnSpanFull(),
                Section::make('Where this order came from')
                    ->collapsible()
                    ->columns(3)
                    ->columnSpanFull()
                    ->schema([
                        TextEntry::make('source_label')
                            ->label('Source')
                            ->state(fn ($record): string => AdSource::label($record->utm_source, $record->utm_medium, $record->fbclid, $record->gclid, $record->referrer))
                            ->badge(),
                        TextEntry::make('utm_campaign')->label('Campaign')->placeholder('-'),
                        TextEntry::make('utm_content')->label('Ad / content')->placeholder('-'),
                        TextEntry::make('utm_term')->label('Term')->placeholder('-'),
                        TextEntry::make('fbclid')->label('Facebook click')->formatStateUsing(fn (?string $state): string => $state ? 'Yes' : 'No')->placeholder('No'),
                        TextEntry::make('landing_page')->label('First page seen')->placeholder('-')->limit(80)->columnSpan(2),
                        TextEntry::make('referrer')->label('Came from site')->placeholder('-')->limit(80),
                    ]),
            ]);
    }
}
