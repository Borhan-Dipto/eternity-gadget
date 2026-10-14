<?php

namespace App\Services;

use App\Models\HomeSection;
use App\Models\Product;
use Illuminate\Support\Collection;

class HomeSectionProductSync
{
    /** @var array<string, string> section slug => product flag column */
    private const AUTO_SECTIONS = [
        'new-arrival' => 'is_new',
        'clearances-sale' => 'is_clearance',
    ];

    public function sync(Product $product): void
    {
        foreach (self::AUTO_SECTIONS as $slug => $flag) {
            $this->syncFlagSection($product, $slug, $flag);
        }
    }

    public function resyncAllAutoSections(): void
    {
        foreach (self::AUTO_SECTIONS as $slug => $flag) {
            $section = HomeSection::query()->where('slug', $slug)->first();

            if (! $section) {
                continue;
            }

            $productIds = Product::query()
                ->where('is_active', true)
                ->where($flag, true)
                ->storefrontOrder()
                ->limit(48)
                ->pluck('id');

            $sync = [];
            foreach ($productIds as $index => $id) {
                $sync[$id] = ['sort_order' => $index];
            }

            $section->products()->sync($sync);
        }
    }

    private function syncFlagSection(Product $product, string $sectionSlug, string $flag): void
    {
        $section = HomeSection::query()->where('slug', $sectionSlug)->first();

        if (! $section) {
            return;
        }

        if (! $product->is_active || ! $product->{$flag}) {
            $section->products()->detach($product->id);

            return;
        }

        $section->products()->syncWithoutDetaching([
            $product->id => ['sort_order' => 0],
        ]);

        $orderedIds = $section->products()
            ->reorder()
            ->orderByDesc('products.created_at')
            ->orderByDesc('products.id')
            ->pluck('products.id');

        $sync = [];
        foreach ($orderedIds as $index => $id) {
            $sync[$id] = ['sort_order' => $index];
        }

        $section->products()->sync($sync);
    }
}
