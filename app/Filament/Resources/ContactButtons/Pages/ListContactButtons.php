<?php

namespace App\Filament\Resources\ContactButtons\Pages;

use App\Filament\Resources\ContactButtons\ContactButtonResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListContactButtons extends ListRecords
{
    protected static string $resource = ContactButtonResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
