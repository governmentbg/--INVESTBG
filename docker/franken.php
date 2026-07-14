<?php

// rename this to public/index.php to use as frankenphp worker
// frankenphp php-server --worker ./public/index.php -r ./public/ -l 0.0.0.0:8080

declare(strict_types=1);

ignore_user_abort(true);

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../preload.php';

use vakata\http\Emitter;
use webadmin\App;

$app = App::init();
$emitter = new Emitter();
$stack = iterator_to_array($app->stack());

$handler = static function () use ($app, $emitter, &$stack): void {
    $req = \vakata\http\Request::fromGlobals();
    $req->getUrl()->setBasePath('/');
    reset($stack);
    $emitter->emit($app->runArray($stack, $req));
};

$max = (int)($_SERVER['MAX_REQUESTS'] ?? 10000);
for ($i = 0; !$max || $i < $max; $i++) {
    if (!\frankenphp_handle_request($handler)) {
        break;
    }
    if ($i % 100 === 0) {
        gc_collect_cycles();
    }
}
