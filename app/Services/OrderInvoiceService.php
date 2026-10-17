<?php

namespace App\Services;

use App\Models\Order;
use Mpdf\Mpdf;
use Symfony\Component\HttpFoundation\Response;

class OrderInvoiceService
{
    public function download(Order $order): Response
    {
        $order->loadMissing(['items.product.images']);

        if (! is_dir(storage_path('app/mpdf-temp'))) {
            mkdir(storage_path('app/mpdf-temp'), 0755, true);
        }

        $html = view('pdf.order-invoice', compact('order'))->render();

        $mpdf = new Mpdf([
            'mode' => 'utf-8',
            'format' => 'A4',
            'margin_left' => 12,
            'margin_right' => 12,
            'margin_top' => 12,
            'margin_bottom' => 12,
            // Hind Siliguri (Latin + Bangla, regular + bold) for the whole invoice, same font as the website
            'fontDir' => array_merge((new \Mpdf\Config\ConfigVariables())->getDefaults()['fontDir'], [resource_path('fonts/pdf')]),
            'fontdata' => array_merge((new \Mpdf\Config\FontVariables())->getDefaults()['fontdata'], [
                'hindsiliguri' => ['R' => 'HindSiliguri-Regular.ttf', 'B' => 'HindSiliguri-Bold.ttf', 'useOTL' => 0xFF],
            ]),
            'default_font' => 'hindsiliguri',
            'tempDir' => storage_path('app/mpdf-temp'),
        ]);

        // Allow remote CDN product images in invoices (common for imported catalog assets).
        $mpdf->showImageErrors = false;
        $mpdf->curlAllowUnsafeSslRequests = true;

        $mpdf->SetTitle('Invoice '.$order->order_number);
        $mpdf->WriteHTML($html);

        $filename = 'invoice-'.$order->order_number.'.pdf';

        return response($mpdf->Output($filename, 'S'), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="'.$filename.'"',
        ]);
    }
}
