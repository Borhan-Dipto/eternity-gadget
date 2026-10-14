<?php

namespace App\Filament\Resources\ContactButtons\Pages;

use App\Filament\Resources\ContactButtons\ContactButtonResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewContactButton extends ViewRecord
{
    protected static string $resource = ContactButtonResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
