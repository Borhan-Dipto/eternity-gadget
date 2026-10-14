<?php

namespace App\Filament\Resources\Orders\Schemas;

use Filament\Infolists\Components\ViewEntry;
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
            ]);
    }
}
