<?php

namespace App\Providers;

use App\Models\Category;
use App\Models\ContactButton;
use App\Models\Menu;
use App\Models\Page;
use App\Models\Product;
use App\Models\SiteSetting;
use App\Observers\ProductObserver;
use App\Support\MediaUploadLogger;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        // Stops bots from flooding the shop with fake cash-on-delivery orders.
        // Per phone number as well as per IP, because many Bangladeshi mobile users share one IP.
        RateLimiter::for('checkout', function (Request $request) {
            $phone = preg_replace('/\D/', '', (string) $request->input('customer_phone'));

            return [
                Limit::perMinute(10)->by('checkout-ip:' . $request->ip()),
                Limit::perHour(4)->by('checkout-phone:' . ($phone !== '' ? $phone : $request->ip())),
            ];
        });

        // Folder/symlink check: once every 6 hours instead of on every single request
        if (! Cache::has('media_storage_ready')) {
            $this->ensureMediaStorageReady();
            Cache::put('media_storage_ready', true, now()->addHours(6));
        }

        Product::observe(ProductObserver::class);
        // menus re-check which categories have products whenever products or categories change
        foreach ([Product::class, Category::class, \App\Models\ProductOption::class] as $model) {
            $model::saved(fn () => \App\Support\CategoryVisibility::forget());
            $model::deleted(fn () => \App\Support\CategoryVisibility::forget());
        }

        View::composer('layouts.storefront', function ($view) {
            if (! $this->storefrontTablesReady()) {
                return;
            }

            $view->with([
                'siteSettings' => SiteSetting::query()->pluck('value', 'key'),
                'mainMenu' => tap(Menu::query()
                    ->where('location', 'main')
                    ->where('is_active', true)
                    ->with([
                        'items' => fn ($query) => $query
                            ->where('is_active', true)
                            ->with([
                                'children' => fn ($childQuery) => $childQuery
                                    ->where('is_active', true)
                                    ->with(['children' => fn ($grandQuery) => $grandQuery->where('is_active', true)]),
                            ]),
                    ])
                    ->first(), fn ($menu) => $menu?->setRelation('items', \App\Support\CategoryVisibility::pruneMenuItems($menu->items))),
                'layoutCategories' => Category::query()->where('is_active', true)->orderBy('sort_order')
                    ->with(['children' => fn ($q) => $q->where('is_active', true)->orderBy('sort_order')])->withCount('products')->get()
                    ->filter(fn ($c) => \App\Support\CategoryVisibility::hasProducts($c->slug))
                    ->each(fn ($c) => $c->setRelation('children', $c->children->filter(fn ($ch) => \App\Support\CategoryVisibility::hasProducts($ch->slug))->values()))
                    ->values(),
                'layoutContactButtons' => ContactButton::query()->where('is_active', true)->orderBy('sort_order')->get(),
                'footerPages' => Page::query()
                    ->where('is_published', true)
                    ->orderBy('title')
                    ->limit(8)
                    ->get(['id', 'title', 'slug']),
            ]);
        });
    }

    private function storefrontTablesReady(): bool
    {
        return Cache::rememberForever('storefront_tables_ready', function () {
            return SiteSetting::query()->getConnection()->getSchemaBuilder()->hasTable('site_settings');
        });
    }

    private function ensureMediaStorageReady(): void
    {
        try {
            $disk = Storage::disk('public');

            foreach (['products', 'categories', 'sliders', 'branding', 'avatars', 'review-images', 'settings', 'livewire-tmp'] as $directory) {
                $disk->makeDirectory($directory);
            }

            $this->ensurePublicStorageLink();

            $this->logStorageAuditIfChanged();
        } catch (\Throwable $e) {
            MediaUploadLogger::error('storage.setup_failed', [
                'message' => $e->getMessage(),
                'exception' => $e::class,
            ]);
        }
    }

    private function ensurePublicStorageLink(): void
    {
        $link = public_path('storage');
        $target = storage_path('app/public');

        if (! is_dir($target)) {
            return;
        }

        if (is_link($link) && realpath($link) === realpath($target)) {
            return;
        }

        if (file_exists($link) && ! is_link($link)) {
            MediaUploadLogger::warning('storage.real_directory', [
                'path' => $link,
                'message' => 'public/storage is a real folder (not symlink). Images are served via /storage Laravel route. Delete this folder and run: php artisan storage:link',
            ]);

            return;
        }

        if (is_link($link)) {
            @unlink($link);
        }

        if (@symlink($target, $link)) {
            MediaUploadLogger::info('storage.symlink_created', [
                'message' => 'Created public/storage symlink via PHP',
            ]);

            return;
        }

        Artisan::call('storage:link');
        MediaUploadLogger::info('storage.link_artisan', [
            'output' => trim(Artisan::output()),
        ]);
    }

    private function logStorageAuditIfChanged(): void
    {
        $state = [
            'symlink_is_link' => is_link(public_path('storage')),
            'symlink_exists' => file_exists(public_path('storage')),
            'products_writable' => is_writable(storage_path('app/public/products')),
        ];

        $hash = md5(json_encode($state));

        if (Cache::get('media_storage_audit_hash') === $hash) {
            return;
        }

        Cache::put('media_storage_audit_hash', $hash, now()->addDay());
        MediaUploadLogger::auditStorageSetup();
    }
}
