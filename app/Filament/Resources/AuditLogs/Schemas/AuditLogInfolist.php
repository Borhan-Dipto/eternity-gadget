<?php

namespace App\Filament\Resources\AuditLogs\Schemas;

use Filament\Infolists\Components\KeyValueEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class AuditLogInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('user.name')
                    ->label('User'),
                TextEntry::make('action'),
                TextEntry::make('auditable_type'),
                TextEntry::make('auditable_id'),
                KeyValueEntry::make('old_values')
                    ->label('Old Values'),
                KeyValueEntry::make('new_values')
                    ->label('New Values'),
                TextEntry::make('created_at')
                    ->dateTime(),
            ]);
    }
}
