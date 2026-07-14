<?php

// this file is designed to work with the built-in PHP web server:
// php -S 127.0.0.1:8000 -t ./public router.php

declare(strict_types=1);

if (php_sapi_name() !== 'cli-server') {
    echo 'ONLY USED IN DEV SERVER';
    die();
}

if (is_file(__DIR__ . '/public' . str_replace('index.php', '', $_SERVER['REQUEST_URI']))) {
    return false;
}
/** @psalm-suppress RedundantCast */
$asset = preg_replace('(\.\d+\.(js|css))', '.$1', str_replace('index.php', '', (string)$_SERVER['REQUEST_URI']));
if (is_file(__DIR__ . '/public' . $asset)) {
    header('Content-Type: text/' . (strpos($asset ?? '', '.js') ? 'javascript' : 'css'));
    readfile(__DIR__ . '/public' . $asset);
    return;
}
$_SERVER['PHP_SELF'] = preg_replace('(\.php\/.*$)i', '.php', $_SERVER['PHP_SELF']);
include __DIR__ . '/public/index.php';
