<?php

namespace App\Filament\Resources\WishlistItems\Pages;

use App\Filament\Resources\WishlistItems\WishlistItemResource;
use Filament\Resources\Pages\CreateRecord;

class CreateWishlistItem extends CreateRecord
{
    protected static string $resource = WishlistItemResource::class;
}
