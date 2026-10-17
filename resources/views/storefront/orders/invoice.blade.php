<!DOCTYPE html>
<html lang="bn">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex">
    <title>Invoice #{{ $order->order_number }} — {{ setting('site_name', 'Eternity Gadget') }}</title>
    <style>
        @font-face { font-family: 'Inter'; font-weight: 100 900; font-display: swap; src: url('/fonts/site/inter-latin-wght-normal.woff2') format('woff2'); }
        @font-face { font-family: 'Hind Siliguri'; font-weight: 400; font-display: swap; src: url('/fonts/site/hind-siliguri-bengali-400-normal.woff2') format('woff2'); unicode-range: U+0980-09FE; }
        @font-face { font-family: 'Hind Siliguri'; font-weight: 700; font-display: swap; src: url('/fonts/site/hind-siliguri-bengali-700-normal.woff2') format('woff2'); unicode-range: U+0980-09FE; }
        * { box-sizing: border-box; }
        body { margin: 0; background: #eef0f3; font-family: 'Inter', 'Hind Siliguri', system-ui, sans-serif; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
        .sheet { max-width: 820px; margin: 24px auto; background: #fff; padding: 36px 40px; border-radius: 14px; box-shadow: 0 10px 40px rgba(15, 23, 42, .08); }
        .toolbar { max-width: 820px; margin: 18px auto 0; display: flex; gap: 10px; justify-content: flex-end; padding: 0 12px; }
        .toolbar button, .toolbar a { border: 0; border-radius: 10px; padding: 10px 16px; font: 600 14px inherit; cursor: pointer; text-decoration: none; }
        .btn-print { background: #F1592A; color: #fff; }
        .btn-back { background: #fff; color: #111; border: 1px solid #ddd !important; }
        @media (max-width: 640px) {
            .sheet { margin: 10px; padding: 20px 16px; border-radius: 12px; }
            .inv-facts td { display: block; width: 100% !important; }
            .inv-meta td, .inv-bottom > tbody > tr > td { display: block; width: 100% !important; }
            .inv-notes { padding: 0 0 14px !important; }
            .inv-word { font-size: 22px !important; }
        }
        @media print {
            @page { size: A4; margin: 12mm; }
            body { background: #fff; }
            .toolbar { display: none; }
            .sheet { margin: 0; padding: 0; box-shadow: none; border-radius: 0; max-width: none; }
        }
    </style>
    @include('invoices._styles')
</head>
<body>
    <div class="toolbar">
        <a class="btn-back" href="{{ route('orders.show', $order) }}">← অর্ডার</a>
        <button class="btn-print" onclick="window.print()">প্রিন্ট / PDF সেভ</button>
    </div>
    <div class="sheet">
        @include('invoices._body', ['order' => $order, 'forPdf' => false])
    </div>
</body>
</html>
