<?php

namespace App\Filament\Resources\WishlistItems\Pages;

use App\Filament\Resources\WishlistItems\WishlistItemResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewWishlistItem extends ViewRecord
{
    protected static string $resource = WishlistItemResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
