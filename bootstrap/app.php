<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        channels: __DIR__.'/../routes/channels.php',
        health: '/up',
        then: function () {
            \Illuminate\Support\Facades\Route::middleware('api')
                ->prefix('api/admin')
                ->group(base_path('routes/api_admin.php'));

            \Illuminate\Support\Facades\Route::middleware('api')
                ->prefix('api/mobile')
                ->group(base_path('routes/api_mobile.php'));

            \Illuminate\Support\Facades\Route::middleware('api')
                ->prefix('api/access')
                ->group(base_path('routes/api_access.php'));
        },
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->api(prepend: [
            \App\Http\Middleware\RequireJsonHeader::class,
        ]);
        $middleware->api(append: [
            \App\Http\Middleware\LogApiRequests::class,
        ]);
        
        $middleware->redirectGuestsTo(fn (Request $request) => 
            $request->is('api/*') ? null : route('login')
        );
        $middleware->alias([
            'not.banned' => \App\Http\Middleware\EnsureNotBanned::class,
            'admin' => \App\Http\Middleware\EnsureIsAdmin::class,
            'mobile' => \App\Http\Middleware\EnsureIsMobileUser::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*'),
        );
    })->create();
