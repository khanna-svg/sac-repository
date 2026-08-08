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

// 4. Load Application
$app = require_once __DIR__ . '/../bootstrap/app.php';

// 5. Override storage path for Vercel
$app->useStoragePath($tmpStorage);

// 6. Handle request using Laravel 11/12 request handler
$app->handleRequest(Illuminate\Http\Request::capture());