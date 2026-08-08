<?php

// 1. Prepare writable directory paths inside Vercel's ephemeral /tmp folder
$tmpStorage = '/tmp/storage';
$tmpCache = '/tmp/bootstrap/cache';

$dirs = [
    $tmpStorage . '/framework/views',
    $tmpStorage . '/framework/cache/data',
    $tmpStorage . '/framework/sessions',
    $tmpStorage . '/logs',
    $tmpCache,
];

foreach ($dirs as $dir) {
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }
}

// 2. Set environment variables to force Laravel to use /tmp
$_ENV['APP_STORAGE_PATH'] = $tmpStorage;
putenv("APP_STORAGE_PATH={$tmpStorage}");

// 3. Load Composer Autoloader
require __DIR__ . '/../vendor/autoload.php';

// 4. Load application and customize storage/bootstrap paths
$app = require_once __DIR__ . '/../bootstrap/app.php';

$app->useStoragePath($tmpStorage);

// Force bootstrap cache path redirect if supported
if (method_exists($app, 'useBootstrapPath')) {
    $app->useBootstrapPath($tmpCache);
}

// 5. Handle incoming request
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);

$response = $kernel->handle(
    $request = Illuminate\Http\Request::capture()
);

$response->send();

$kernel->terminate($request, $response);