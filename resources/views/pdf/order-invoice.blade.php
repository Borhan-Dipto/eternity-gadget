<!DOCTYPE html>
<html lang="bn">
<head>
    <meta charset="UTF-8">
    <title>Invoice {{ $order->order_number }}</title>
    @include('invoices._styles')
    <style> body, .inv, .inv * { font-family: hindsiliguri; } </style>
</head>
<body>
    @include('invoices._body', ['order' => $order, 'forPdf' => true])
</body>
</html>
