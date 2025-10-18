<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        // Register custom middleware aliases
        $middleware->alias([
            'validate.annotation.access' => \App\Http\Middleware\ValidateAnnotationAccess::class,
        ]);

        // Fix 419 CSRF errors with Livewire v3 + FilamentPHP v4
        // Recommended by FilamentPHP team: Livewire has built-in CSRF protection
        // See: https://github.com/filamentphp/filament/discussions/8574
        $middleware->validateCsrfTokens(except: [
            'livewire/*',           // All Livewire endpoints
            'livewire/upload-file', // File uploads specifically
        ]);

        // API rate limiting configuration
        // Using standard 'api' limiter instead of custom user-based limiter
        $middleware->throttleApi('api');
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
