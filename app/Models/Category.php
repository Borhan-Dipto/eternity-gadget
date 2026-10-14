<?php

namespace App\Models;

use App\Models\Concerns\NormalizesMediaPath;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Category extends Model
{
    use NormalizesMediaPath;

    protected $fillable = ['parent_id', 'name', 'slug', 'image', 'description', 'meta_title', 'meta_description', 'sort_order', 'is_featured', 'is_active'];

    public function parent(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(Category::class, 'parent_id')->orderBy('sort_order');
    }

    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }
}