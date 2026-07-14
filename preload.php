<?php

declare(strict_types=1);

require_once __DIR__ . '/vendor/autoload.php';

if (!\function_exists('opcache_compile_file')) {
    return;
}
if (!\ini_get('opcache.enable')) {
    return;
}
$dirs = [
    realpath(__DIR__ . '/vendor'),
    realpath(__DIR__ . '/storage/cache'),
    realpath(__DIR__ . '/storage/intl'),
    realpath(__DIR__ . '/app'),
    realpath(__DIR__ . '/public'),
];
foreach ($dirs as $dir) {
    preloadDirectory($dir);
}
function preloadDirectory(string $dir): void
{
    static $included = null;
    if (!isset($included)) {
        $included = get_included_files();
    }
    if (!\is_dir($dir)) {
        return;
    }
    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($dir)
    );
    foreach ($iterator as $file) {
        if ($file->isFile() && $file->getExtension() === 'php') {
            try {
                if (!in_array($file->getRealPath(), $included)) {
                    \opcache_compile_file($file->getRealPath());
                }
            } catch (Throwable) {
            }
        }
    }
}

// opcache ini settings
// [opcache]
// opcache.enable=1
// opcache.memory_consumption=256
// opcache.interned_strings_buffer=32
// opcache.max_accelerated_files=262237
// opcache.validate_timestamps=0
// opcache.preload=/var/www/my-app/preload.php
// opcache.preload_user=www-data
