<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Services\OrderInvoiceService;
use Filament\Facades\Filament;
use Symfony\Component\HttpFoundation\Response;

class OrderInvoiceController extends Controller
{
    public function download(Order $order, OrderInvoiceService $invoices): Response
    {
        $user = auth()->user();

        abort_unless(
            $user && $user->canAccessPanel(Filament::getPanel('admin')),
            403
        );

        return $invoices->download($order);
    }
}
