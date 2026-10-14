<?php

namespace App\Console\Commands;

use App\Jobs\CheckLowStockJob;
use Illuminate\Console\Command;

class CheckLowStockAlerts extends Command
{
    protected $signature = 'inventory:check-low-stock {--threshold= : Override low stock threshold}';

    protected $description = 'Check for products and options at or below the low stock threshold';

    public function handle(): int
    {
        $threshold = $this->option('threshold') !== null
            ? (int) $this->option('threshold')
            : null;

        CheckLowStockJob::dispatch($threshold);

        $this->info('Low stock check queued.');

        return self::SUCCESS;
    }
}
