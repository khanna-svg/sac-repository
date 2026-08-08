<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

$app = Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        //
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })
    ->create();

/*
|--------------------------------------------------------------------------
| Vercel Writable Directories
|--------------------------------------------------------------------------
|
| Vercel's deployed filesystem is read-only.
| Laravel needs writable directories for runtime files.
| We use /tmp when running on Vercel.
|
*/

if (isset($_SERVER['VERCEL'])) {

    $bootstrapPath = '/tmp/laravel-bootstrap';
    $storagePath = '/tmp/laravel-storage';

    // Bootstrap cache
    mkdir($bootstrapPath . '/cache', 0755, true);

    // Laravel storage directories
    mkdir($storagePath . '/app', 0755, true);
    mkdir($storagePath . '/framework/cache', 0755, true);
    mkdir($storagePath . '/framework/sessions', 0755, true);
    mkdir($storagePath . '/framework/views', 0755, true);
    mkdir($storagePath . '/logs', 0755, true);

    $app->useBootstrapPath($bootstrapPath);
    $app->useStoragePath($storagePath);
}

return $app;