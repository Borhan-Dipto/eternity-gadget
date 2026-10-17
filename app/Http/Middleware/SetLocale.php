<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * The shop is always shown in Bangla (buttons, forms, messages, validation errors);
 * menu and product names stay in English because they come from the database.
 * The admin panel stays in English.
 */
class SetLocale
{
    public function handle(Request $request, Closure $next): Response
    {
        $isAdmin = $request->is('admin', 'admin/*', 'livewire/*', 'filament/*');

        app()->setLocale($isAdmin ? 'en' : 'bn');

        return $next($request);
    }
}
