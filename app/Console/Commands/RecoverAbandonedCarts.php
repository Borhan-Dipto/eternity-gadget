<?php

namespace App\Console\Commands;

use App\Jobs\SendAbandonedCartRecoveryJob;
use App\Models\AbandonedCart;
use Illuminate\Console\Command;

class RecoverAbandonedCarts extends Command
{
    protected $signature = 'carts:recover-abandoned {--hours=24 : Hours since last update before recovery email}';

    protected $description = 'Send recovery emails for abandoned carts';

    public function handle(): int
    {
        $hours = max(1, (int) $this->option('hours'));
        $cutoff = now()->subHours($hours);

        $carts = AbandonedCart::query()
            ->whereNull('emailed_at')
            ->where('updated_at', '<=', $cutoff)
            ->whereNotNull('user_id')
            ->get();

        $dispatched = 0;

        foreach ($carts as $cart) {
            SendAbandonedCartRecoveryJob::dispatch($cart);
            $dispatched++;
        }

        $this->info("Dispatched {$dispatched} abandoned cart recovery job(s).");

        return self::SUCCESS;
    }
}
