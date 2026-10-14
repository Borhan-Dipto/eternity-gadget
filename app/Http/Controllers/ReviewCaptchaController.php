<?php

namespace App\Http\Controllers;

use Illuminate\Http\Response;
use Illuminate\Support\Str;

class ReviewCaptchaController extends Controller
{
    public function show(): Response
    {
        return $this->renderCaptcha('review_captcha');
    }

    public function showRegister(): Response
    {
        return $this->renderCaptcha('register_captcha');
    }

    private function renderCaptcha(string $sessionKey): Response
    {
        $code = Str::lower(Str::random(6));
        session([$sessionKey => $code]);

        $width = 130;
        $height = 44;
        $colors = ['#e74c3c88', '#3498db88', '#f39c1288', '#9b59b688'];

        $svg = '<svg xmlns="http://www.w3.org/2000/svg" width="' . $width . '" height="' . $height . '">';
        $svg .= '<rect width="100%" height="100%" fill="#f8f8f8" stroke="#ddd"/>';

        for ($i = 0; $i < 7; $i++) {
            $cx = random_int(12, $width - 12);
            $cy = random_int(8, $height - 8);
            $r = random_int(10, 22);
            $svg .= '<circle cx="' . $cx . '" cy="' . $cy . '" r="' . $r . '" fill="' . $colors[array_rand($colors)] . '"/>';
        }

        $svg .= '<text x="50%" y="54%" dominant-baseline="middle" text-anchor="middle" font-family="Courier New, monospace" font-size="20" font-weight="700" fill="#222" letter-spacing="3">'
            . htmlspecialchars($code, ENT_QUOTES, 'UTF-8')
            . '</text>';
        $svg .= '</svg>';

        return response($svg, 200)
            ->header('Content-Type', 'image/svg+xml')
            ->header('Cache-Control', 'no-store, no-cache, must-revalidate');
    }
}
