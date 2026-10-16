<?php

namespace App\Http\Middleware;

use App\Support\AdSource;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Remembers where a visitor came from (UTM tags, Facebook/Google click IDs, referring site)
 * for 30 days, so every order can be attributed to the ad or post that brought the customer.
 */
class CaptureAdSource
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if ($request->isMethod('GET') && ! $request->expectsJson() && ! $request->is('admin*', 'livewire*', 'api/*')) {
            AdSource::captureFrom($request, $response);
        }

        return $response;
    }
}
