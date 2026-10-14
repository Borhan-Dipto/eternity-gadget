<?php

namespace App\Jobs;

use App\Models\Product;
use App\Models\ProductOption;
use App\Models\User;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class CheckLowStockJob implements ShouldQueue
{
    use Queueable;

    public function __construct(public ?int $threshold = null) {}

    public function handle(): void
    {
        $threshold = $this->threshold ?? (int) setting('low_stock_threshold', '5');

        $lowProducts = Product::query()
            ->where('is_active', true)
            ->where('stock', '<=', $threshold)
            ->orderBy('stock')
            ->get(['id', 'name', 'sku', 'stock']);

        $lowOptions = ProductOption::query()
            ->where('stock', '<=', $threshold)
            ->with(['product:id,name,sku'])
            ->orderBy('stock')
            ->get(['id', 'product_id', 'name', 'value', 'stock']);

        if ($lowProducts->isEmpty() && $lowOptions->isEmpty()) {
            return;
        }

        $summary = [
            'threshold' => $threshold,
            'products' => $lowProducts->map(fn (Product $product) => [
                'id' => $product->id,
                'name' => $product->name,
                'sku' => $product->sku,
                'stock' => $product->stock,
            ])->all(),
            'options' => $lowOptions->map(fn (ProductOption $option) => [
                'id' => $option->id,
                'product' => $option->product?->name,
                'option' => $option->name . ': ' . $option->value,
                'stock' => $option->stock,
            ])->all(),
        ];

        Log::warning('Low stock alert', $summary);

        User::query()
            ->where(function ($query) {
                $query->where('role', User::ROLE_ADMIN)
                    ->orWhere('is_admin', true);
            })
            ->each(function (User $admin) use ($summary, $lowProducts, $lowOptions) {
                \App\Notifications\DatabaseNotification::notify(
                    $admin,
                    'low_stock',
                    'Low stock alert',
                    $lowProducts->count() . ' product(s) and ' . $lowOptions->count() . ' option(s) are at or below threshold.',
                    $summary,
                );
            });
    }
}
