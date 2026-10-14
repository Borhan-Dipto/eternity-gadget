<?php

require __DIR__.'/../vendor/autoload.php';

$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

Illuminate\Support\Facades\Artisan::call('key:generate', ['--force' => true]);

echo 'Done. APP_KEY is now set in .env. DELETE THIS FILE IMMEDIATELY.';