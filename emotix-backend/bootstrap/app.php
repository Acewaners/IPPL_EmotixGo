<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Middleware\HandleCors;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        // GLOBAL middleware
        $middleware->use([
            HandleCors::class,
        ]);

        // Alias middleware kustom
        $middleware->alias([
            'admin' => \App\Http\Middleware\AdminMiddleware::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        // Rendering kustom untuk error Unauthenticated (401)
        $exceptions->render(function (AuthenticationException $e, Request $request) {
            if ($request->is('api/*')) {
                // 🆕 Kirim detail ke Railway Logs
                Log::error('Auth Failure Debug:', [
                    'url'         => $request->fullUrl(),
                    'method'      => $request->method(),
                    'token_sent'  => $request->bearerToken() ? 'Yes (Starts with: '.substr($request->bearerToken(), 0, 5).'...)' : 'No Token',
                    'ip_address'  => $request->ip(),
                    'user_agent'  => $request->userAgent(),
                ]);

                return response()->json([
                    'message'    => 'Unauthenticated.',
                    'debug_info' => 'Check Railway Logs for Auth Failure Debug'
                ], 401);
            }
        });
    })
    ->create(); // <--- WAJIB ADA INI