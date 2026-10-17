{{-- Standalone page: used when the site itself has a problem, so it must not touch the database. --}}
<!DOCTYPE html>
<html lang="bn">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex">
    <title>{{ $title }} — Eternity Gadget</title>
    <style>
        @font-face { font-family: 'Hind Siliguri'; font-weight: 400; font-display: swap; src: url('/fonts/site/hind-siliguri-bengali-400-normal.woff2') format('woff2'); }
        @font-face { font-family: 'Hind Siliguri'; font-weight: 700; font-display: swap; src: url('/fonts/site/hind-siliguri-bengali-700-normal.woff2') format('woff2'); }
        body { margin: 0; min-height: 100vh; display: flex; align-items: center; justify-content: center; background: #f6f6f7; font-family: 'Hind Siliguri', system-ui, sans-serif; color: #111; }
        .box { max-width: 460px; margin: 16px; padding: 36px 24px; background: #fff; border-radius: 16px; text-align: center; box-shadow: 0 10px 30px rgba(0,0,0,.06); }
        .code { font-size: 56px; font-weight: 700; color: #F1592A; line-height: 1; }
        h1 { font-size: 21px; margin: 14px 0 8px; }
        p { color: #555; line-height: 1.7; margin: 0 0 20px; }
        a { display: inline-block; margin: 4px; padding: 11px 20px; border-radius: 10px; text-decoration: none; font-weight: 700; font-size: 14px; }
        .primary { background: #F1592A; color: #fff; }
        .secondary { border: 1px solid #ddd; color: #111; }
    </style>
</head>
<body>
    <div class="box">
        <div class="code">{{ $code }}</div>
        <h1>{{ $title }}</h1>
        <p>{{ $message }}</p>
        <a class="primary" href="/">আবার চেষ্টা করুন</a>
        <a class="secondary" href="https://wa.me/8801867683114" target="_blank" rel="noopener">WhatsApp-এ অর্ডার করুন</a>
    </div>
</body>
</html>
