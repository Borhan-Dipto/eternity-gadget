<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Collection;

class HomeSection extends Model
{
    protected $fillable = ['title', 'slug', 'type', 'view_more_url', 'sort_order', 'is_active'];

    public function products(): BelongsToMany
    {
        return $this->belongsToMany(Product::class, 'home_section_products')
            ->withPivot('sort_order')
            ->orderByPivot('sort_order')
            ->orderByDesc('products.created_at')
            ->orderByDesc('products.id');
    }

    public function listingMode(): string
    {
        if ($this->slug === 'new-arrival' || $this->type === 'new_arrivals') {
            return 'new_arrivals';
        }

        if ($this->slug === 'clearances-sale' || in_array($this->type, ['clearance', 'clearances'], true)) {
            return 'clearance';
        }

        if ($this->type === 'featured') {
            return 'featured';
        }

        return 'manual';
    }

    public function isAutoListing(): bool
    {
        return $this->listingMode() !== 'manual';
    }

    public function resolvedProducts(int $limit = 24): Collection
    {
        if ($this->type === 'categories') {
            return collect();
        }

        $query = Product::query()
            ->where('is_active', true)
            ->with('images');

        return match ($this->listingMode()) {
            'new_arrivals' => $query->where('is_new', true)->storefrontOrder()->limit($limit)->get(),
            'clearance' => $query->where('is_clearance', true)->storefrontOrder()->limit($limit)->get(),
            'featured' => $query->where('is_featured', true)->storefrontOrder()->limit($limit)->get(),
            default => $this->products()
                ->where('is_active', true)
                ->with('images')
                ->reorder()
                ->orderBy('home_section_products.sort_order')
                ->orderByDesc('products.created_at')
                ->orderByDesc('products.id')
                ->limit($limit)
                ->get(),
        };
    }
}
