<?php

namespace App\Observers;

use App\Models\Product;
use App\Services\HomeSectionProductSync;

class ProductObserver
{
    public function __construct(private HomeSectionProductSync $homeSectionProductSync) {}

    public function saved(Product $product): void
    {
        if ($product->wasRecentlyCreated
            || $product->wasChanged(['is_new', 'is_clearance', 'is_active', 'created_at'])) {
            $this->homeSectionProductSync->sync($product);
        }
    }

    public function deleted(Product $product): void
    {
        $product->homeSections()->detach();
    }
}
