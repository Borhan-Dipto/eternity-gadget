<?php

namespace App\Filament\Resources\ContactButtons\Pages;

use App\Filament\Resources\ContactButtons\ContactButtonResource;
use Filament\Resources\Pages\CreateRecord;

class CreateContactButton extends CreateRecord
{
    protected static string $resource = ContactButtonResource::class;
}
