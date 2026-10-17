<?php

use App\Console\Commands\ConfirmPendingOrders;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
        then: function () {
            Route::middleware('web')
                ->prefix('api')
                ->group(base_path('routes/api.php'));
        },
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->web(append: [
            \App\Http\Middleware\SetLocale::class,
            \App\Http\Middleware\SecurityHeaders::class,
            \App\Http\Middleware\CaptureAdSource::class,
        ]);

        // Meta Pixel sets these in the browser; Laravel must read them as-is.
        $middleware->encryptCookies(except: ['_fbp', '_fbc']);

        $middleware->redirectGuestsTo(fn () => route('account.login'));
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*'),
        );
        // Real site errors (not 404s / form mistakes) → the owner's Telegram, same error at most once per 30 minutes.
        $exceptions->report(function (\Throwable $e) {
            \App\Support\ErrorAlert::send($e);
        });
    })
    ->withSchedule(function (Schedule $schedule): void {
        $schedule->command(ConfirmPendingOrders::class)->everyMinute();
        // Needs one cPanel Cron Job: * * * * * php /home/USER/eternitygadget.shop/artisan schedule:run
        $schedule->command('carts:recover-abandoned')->hourly()->withoutOverlapping();
        $schedule->command('inventory:check-low-stock')->dailyAt('09:00')->timezone('Asia/Dhaka');
        $schedule->command('backup:database')->dailyAt('03:00')->timezone('Asia/Dhaka')->withoutOverlapping();
    })->create();
