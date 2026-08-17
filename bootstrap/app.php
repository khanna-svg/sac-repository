<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

$app = Application::configure(
    basePath: dirname(__DIR__)
)
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        api: __DIR__ . '/../routes/api.php',
        apiPrefix: 'backend',
        commands: __DIR__ . '/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {

        $middleware->alias([

            // Requires the user to be logged in
            'sac.auth' =>
                \App\Http\Middleware\RequireSacLogin::class,

            // Requires the logged-in user to be an admin
            'sac.admin' =>
                \App\Http\Middleware\RequireSacAdmin::class,

        ]);

    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })
    ->create();

if (
    isset($_ENV['VERCEL']) ||
    getenv('VERCEL')
) {

    $app->useStoragePath(
        '/tmp/storage'
    );

}


return $app;