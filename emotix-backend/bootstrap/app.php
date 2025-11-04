<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

// middleware bawaan yang kita pakai
use Illuminate\Http\Middleware\HandleCors;
// use Laravel\Sanctum\Http\Middleware\EnsureFrontendRequestsAreStateful; // optional, kalau mau SPA cookie

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        // GLOBAL middleware pakai array
        $middleware->use([
            HandleCors::class,
        ]);

        // alias middleware kustom (biar bisa 'admin' di routes)
        $middleware->alias([
            'admin' => \App\Http\Middleware\AdminMiddleware::class,
        ]);

        // Kalau mau SPA pakai cookie session (bukan Bearer token), baru aktifkan ini:
        // $middleware->appendToGroup('api', EnsureFrontendRequestsAreStateful::class);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
