<?php

/**
 * @var \vakata\views\View $this
 * @var \vakata\http\Request $req
 * @var string $cspNonce
 * @var callable (string): string $asset
 * @var \vakata\intl\Intl $intl
 * @var \vakata\http\Uri $url
 * @var callable (string): mixed $config
*/
?>
<!DOCTYPE html>
<html lang="<?= $this->e($req->getAttribute('locale') ?? 'en') ?>">
<head>
    <meta charset="UTF-8">
    <title><?= $this->e(strip_tags($intl($config('APPNAME')))) ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1" />

    <link rel="icon" href="<?= $this->e($url('zni_favicon.ico')) ?>" sizes="any">
    <!-- <link rel="icon" href="<?= $this->e($url('favicon.svg')) ?>" type="image/svg+xml"> -->
    <link rel="apple-touch-icon" href="<?= $this->e($url('apple-touch-icon.png')) ?>">

    <link rel="stylesheet" href="<?= $asset('assets/static/fomantic-ui-css/semantic.min.css') ?>">
    <script src="<?= $asset('assets/static/jquery/jquery.min.js') ?>"></script>
    <script src="<?= $asset('assets/static/fomantic-ui-css/semantic.min.js') ?>"></script>

    <?= $this->section('head'); ?>
</head>
<body>

    <?= $this->section('content'); ?>

    <script nonce="<?= $this->e($cspNonce) ?>">
        // ensure a reload once the session expires - meaning:
        // 1) inactive logged in users will be sent to the login screen
        // 2) the login form will be refreshed and a new CSRF token will be generated
        // may need a revisit if the system is migrated to AJAX requests
        (function () {
            var lastCheck = Date.now();
            var period = parseInt(JSON.parse('<?= (int)$config('SESSION_TIMEOUT') ?>'), 10) * 1000;
            var timeout = null;
            var check = function () {
                lastCheck = Date.now();
                $.get('<?= $this->e($url($config('LOGIN_URL'))) ?>')
                    .done(function (data) {
                        if (!data.user) {
                            window.location.reload();
                        }
                    })
                    .fail(function () {
                        window.location.reload();
                    });
                to();
            };
            var to = function () {
                if (timeout) {
                    clearTimeout(timeout);
                }
                timeout = setTimeout(
                    function () {
                        check();
                    },
                    Math.max(0, (lastCheck + period + 2000) - Date.now())
                );
            };
            $(window)
                .on('focus', function () {
                    to();
                })
                .on('blur', function () {
                    if (timeout) {
                        clearTimeout(timeout);
                    }
                });
            to();
        }());
    </script>
</body>
</html>
