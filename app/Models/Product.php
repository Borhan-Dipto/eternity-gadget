<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;
use App\Support\MediaPath;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Builder;

class Product extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'category_id',
        'name',
        'slug',
        'sku',
        'short_description',
        'description',
        'price',
        'sale_price',
        'sale_starts_at',
        'sale_ends_at',
        'stock',
        'allow_backorder',
        'is_preorder',
        'weight',
        'is_active',
        'is_featured',
        'is_new',
        'is_clearance',
        'video_url',
        'meta_title',
        'meta_description',
    ];

    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'sale_price' => 'decimal:2',
            'weight' => 'decimal:3',
            'sale_starts_at' => 'datetime',
            'sale_ends_at' => 'datetime',
            'allow_backorder' => 'boolean',
            'is_preorder' => 'boolean',
            'is_active' => 'boolean',
            'is_featured' => 'boolean',
            'is_new' => 'boolean',
            'is_clearance' => 'boolean',
        ];
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function images(): HasMany
    {
        return $this->hasMany(ProductImage::class)->orderBy('sort_order');
    }

    public function options(): HasMany
    {
        return $this->hasMany(ProductOption::class)->with('deviceModel')->orderBy('sort_order');
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(ProductReview::class);
    }

    public function questions(): HasMany
    {
        return $this->hasMany(ProductQuestion::class);
    }

    public function stockAlerts(): HasMany
    {
        return $this->hasMany(StockAlert::class);
    }

    public function inventoryAdjustments(): HasMany
    {
        return $this->hasMany(InventoryAdjustment::class);
    }

    public function homeSections(): BelongsToMany
    {
        return $this->belongsToMany(HomeSection::class, 'home_section_products')->withPivot('sort_order');
    }

    /**
     * Photo of the exact version chosen (colour / model): a gallery photo tagged with those options
     * or the photo set on an option row. The most specific match wins; otherwise the main photo.
     */
    public function imageForOptions(iterable $optionIds): ?string
    {
        $chosen = collect($optionIds)->map(fn ($id) => (int) $id)->filter()->unique();
        if ($chosen->isEmpty()) {
            return $this->primaryImage();
        }

        $candidates = collect();
        foreach (($this->relationLoaded('images') ? $this->images : $this->images()->get()) as $img) {
            $tags = collect((array) ($img->option_ids ?? []))->map(fn ($id) => (int) $id);
            if ($tags->isNotEmpty()) {
                $candidates->push(['image' => $img->image, 'tags' => $tags]);
            }
        }
        foreach (($this->relationLoaded('options') ? $this->options : $this->options()->get()) as $opt) {
            if ($opt->image) {
                $candidates->push(['image' => $opt->image, 'tags' => collect([(int) $opt->id])]);
            }
        }

        $best = $candidates
            ->filter(fn ($c) => $c['tags']->every(fn ($id) => $chosen->contains($id)))
            ->sortByDesc(fn ($c) => $c['tags']->count())
            ->first();

        return $best ? MediaPath::normalize($best['image']) : $this->primaryImage();
    }

    public function primaryImage(): ?string
    {
        if ($this->relationLoaded('images')) {
            $path = $this->images->firstWhere('is_primary', true)?->image
                ?? $this->images->sortBy('sort_order')->first()?->image;
        } else {
            $path = $this->images()->where('is_primary', true)->value('image')
                ?? $this->images()->orderBy('sort_order')->value('image');
        }

        return MediaPath::normalize($path);
    }

    public function displayPrice(?ProductOption $option = null): float
    {
        return $this->effectivePrice() + (float) ($option?->price_adjustment ?? 0);
    }

    public function pricingOptionForSelection(Collection $selectedOptions): ?ProductOption
    {
        return $selectedOptions->first(fn (ProductOption $option) => $option->isCompatibilityOption())
            ?? $selectedOptions->first(fn (ProductOption $option) => (float) $option->price_adjustment !== 0.0)
            ?? $selectedOptions->first();
    }

    public function effectivePrice(): float
    {
        if ($this->sale_price !== null && $this->isOnSale()) {
            return (float) $this->sale_price;
        }

        return (float) $this->price;
    }

    public function isOnSale(): bool
    {
        if ($this->sale_price === null) {
            return false;
        }

        $now = now();

        if ($this->sale_starts_at && $now->lt($this->sale_starts_at)) {
            return false;
        }

        if ($this->sale_ends_at && $now->gt($this->sale_ends_at)) {
            return false;
        }

        return true;
    }

    public function stockStatus(?ProductOption $option = null): string
    {
        $stock = $this->availableStock($option);

        if ($stock > 0) {
            return 'in_stock';
        }

        if ($this->is_preorder) {
            return 'preorder';
        }

        if ($this->allow_backorder) {
            return 'backorder';
        }

        return 'out_of_stock';
    }

    public function availableStock(?ProductOption $option = null): int
    {
        return $option ? (int) $option->stock : (int) $this->stock;
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeStorefrontOrder(Builder $query): Builder
    {
        return $query->orderByDesc('created_at')->orderByDesc('id');
    }
}
