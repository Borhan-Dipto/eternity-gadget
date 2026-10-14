<?php

namespace App\Console\Commands;

use App\Models\Product;
use App\Models\ProductOption;
use Illuminate\Console\Command;

class ImportLegacyProductOptions extends Command
{
    protected $signature = 'catalog:import-legacy-options {--source= : Path to folder with index*.html product pages}';

    protected $description = 'Import product variant options from legacy OpenCart HTML product pages';

    public function handle(): int
    {
        $source = $this->option('source') ?: base_path('..');
        $source = rtrim(str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $source), DIRECTORY_SEPARATOR);

        if (! is_dir($source)) {
            $this->error("Source directory not found: {$source}");

            return self::FAILURE;
        }

        $files = glob($source . DIRECTORY_SEPARATOR . 'index*.html') ?: [];
        $imported = 0;
        $skipped = 0;

        foreach ($files as $file) {
            if (str_contains($file, DIRECTORY_SEPARATOR . 'schema.org' . DIRECTORY_SEPARATOR)
                || str_contains($file, DIRECTORY_SEPARATOR . 'signals' . DIRECTORY_SEPARATOR)) {
                continue;
            }

            $html = file_get_contents($file);
            if ($html === false || ! str_contains($html, 'option-new-radio')) {
                continue;
            }

            if (! preg_match('/product_id=(\d+)/', $html, $productMatch)) {
                $skipped++;
                continue;
            }

            $product = Product::query()->where('sku', 'IG-' . $productMatch[1])->first();
            if (! $product) {
                $this->warn('Product not found for IG-' . $productMatch[1] . ' (' . basename($file) . ')');
                $skipped++;
                continue;
            }

            if (! preg_match_all(
                '/<label class="control-label">([^<]+)<\/label><div id="input-option\d+"><ul class="option-new-radio">(.*?)<\/ul>/s',
                $html,
                $groups,
                PREG_SET_ORDER
            )) {
                continue;
            }

            $groupCount = 0;

            foreach ($groups as $groupIndex => $group) {
                $groupName = trim(html_entity_decode(strip_tags($group[1])));

                if ($groupName === '' || ! preg_match_all(
                    '/<li class="option-size[^"]*"[^>]*title="([^"]*)"[^>]*>.*?value="\d+"[^>]*\/?>([^<]*)<\/li>/s',
                    $group[2],
                    $items,
                    PREG_SET_ORDER
                )) {
                    continue;
                }

                foreach ($items as $itemIndex => $item) {
                    $value = trim(html_entity_decode($item[2] ?: $item[1]));
                    if ($value === '') {
                        continue;
                    }

                    ProductOption::updateOrCreate(
                        [
                            'product_id' => $product->id,
                            'name' => $groupName,
                            'value' => $value,
                        ],
                        [
                            'price_adjustment' => 0,
                            'stock' => max(0, (int) $product->stock),
                            'sort_order' => ($groupIndex * 100) + $itemIndex,
                        ]
                    );

                    $groupCount++;
                }
            }

            if ($groupCount > 0) {
                $imported++;
                $this->line("Imported {$groupCount} options for {$product->name}");
            }
        }

        $this->info("Done. Products updated: {$imported}, skipped: {$skipped}.");

        return self::SUCCESS;
    }
}
