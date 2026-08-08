<?php

// Ensure writable directories exist in Vercel's ephemeral /tmp folder
$storageFolders = [
    '/tmp/storage/framework/views',
    '/tmp/storage/framework/cache/data',
    '/tmp/storage/framework/sessions',
    '/tmp/storage/logs',
    '/tmp/bootstrap/cache',
];

foreach ($storageFolders as $folder) {
    if (!is_dir($folder)) {
        mkdir($folder, 0755, true);
    }
}

// Bind custom storage path
$_ENV['APP_STORAGE_PATH'] = '/tmp/storage';

require __DIR__ . '/../public/index.php';