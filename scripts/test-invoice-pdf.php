<?php

require __DIR__ . '/../vendor/autoload.php';

$app = require __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$order = App\Models\Order::with(['items.product.images'])->latest('id')->first();

if (! $order) {
    fwrite(STDERR, "NO_ORDER\n");
    exit(1);
}

$html = view('pdf.order-invoice', ['order' => $order])->render();

$mpdf = new Mpdf\Mpdf([
    'mode' => 'utf-8',
    'format' => 'A4',
    'default_font' => 'dejavusans',
    'tempDir' => storage_path('app/mpdf-temp'),
]);

$mpdf->WriteHTML($html);
$path = storage_path('app/screenshots/invoice-sample.pdf');
file_put_contents($path, $mpdf->Output('', 'S'));

echo 'PDF_OK order=' . $order->order_number . ' bytes=' . filesize($path) . PHP_EOL;
