#!/usr/bin/env php
<?php

/**
 * This script downloads all needed dev tools as phar archives.
 */

declare(strict_types=1);

$tools = [
    'phpunit.phar' => [
        'https://phar.phpunit.de/phpunit-11.0.4.phar',
        'd74b216d6ed1168d2719e4277e06c04f'
    ],
    'phpstan.phar' => [
        'https://github.com/phpstan/phpstan/releases/download/2.0.3/phpstan.phar',
        '57d51ed62b8d3d502b8f5cf3475de240'
    ],
    'phpcs.phar'   => [
        'https://github.com/PHPCSStandards/PHP_CodeSniffer/releases/download/3.11.3/phpcs.phar',
        '7fe22ca295eeb957f07a04654125bd73'
    ],
    'phpcbf.phar'  => [
        'https://github.com/PHPCSStandards/PHP_CodeSniffer/releases/download/3.11.3/phpcbf.phar',
        'af3e0aaaccea47d597f6b5b39a1402db'
    ],
    'composer.phar' => [
        'https://getcomposer.org/download/2.8.5/composer.phar',
        'df9878425edae0db14bff16c3ecf7414'
    ],
    'psalm.phar' => [
        'https://github.com/vimeo/psalm/releases/download/6.0.0/psalm.phar',
        '6ee84c2f8d175f5c0f215e5364a41c84'
    ],
    'phpmd.phar' => [
        'https://github.com/phpmd/phpmd/releases/download/2.15.0/phpmd.phar',
        '68870da3495769661a4db6c2420563f4'
    ]
];
foreach ($tools as $name => $data) {
    $file = __DIR__ . '/' . basename($name);
    if (file_exists($file) && md5_file($file) === $data[1]) {
        continue;
    }
    $temp = file_get_contents($data[0]);
    if ($temp !== false) {
        if (md5($temp) === $data[1]) {
            @file_put_contents($file, $temp);
        } else {
            echo "MD5 mismatch: " . basename($name) . " > " . md5($temp) . "\n";
        }
    }
}
