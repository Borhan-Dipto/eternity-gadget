<?php

namespace App\Filament\Resources\ContactButtons\Pages;

use App\Filament\Resources\ContactButtons\ContactButtonResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditContactButton extends EditRecord
{
    protected static string $resource = ContactButtonResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
        ];
    }
}
