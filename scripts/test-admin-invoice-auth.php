<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$user = App\Models\User::find(2);
auth()->login($user);
$order = App\Models\Order::with(['items.product.images'])->find(22);

try {
    $controller = new App\Http\Controllers\Admin\OrderInvoiceController();
    $response = $controller->download($order);
    echo 'OK status=' . $response->getStatusCode() . ' type=' . $response->headers->get('Content-Type') . ' len=' . strlen($response->getContent()) . PHP_EOL;
} catch (Throwable $e) {
    echo 'FAIL ' . $e->getMessage() . PHP_EOL;
    echo $e->getFile() . ':' . $e->getLine() . PHP_EOL;
}
