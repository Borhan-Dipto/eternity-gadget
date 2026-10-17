<?php

namespace App\Console\Commands;

use App\Support\ImageLocalizer;
use Illuminate\Console\Command;

class CopyExternalImages extends Command
{
    protected $signature = 'images:copy-external';

    protected $description = 'Download product/category photos that still point to another website into this site\'s storage';

    public function handle(): int
    {
        $result = ImageLocalizer::run();
        $this->info("Copied {$result['copied']} image(s). Still external: {$result['external']}.");
        foreach ($result['failed'] as $name) {
            $this->warn("Could not download (upload a new photo): {$name}");
        }

        return self::SUCCESS;
    }
}
