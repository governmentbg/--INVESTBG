<?php

declare(strict_types=1);

use vakata\spreadsheet\Reader;
use zni\App;

require_once __DIR__ . '/../vendor/autoload.php';

/** @var App $app */
$app = App::init();
$config = $app->config();



/** Код, Наименование */
$fileNkdp = $config->getString('STORAGE_TMP') . DIRECTORY_SEPARATOR . 'nkpd.xlsx';
$readerNkpd = Reader::fromFile($fileNkdp);

foreach ($readerNkpd->toArray() as $v) {
    echo "INSERT INTO nom_nkpd (code, \"name\") VALUES('{$v[0]}', '{$v[1]}');\n";
}

$fileKid = $config->getString('STORAGE_TMP') . DIRECTORY_SEPARATOR . 'kid_2025.xlsx';
$readerKid = Reader::fromFile($fileKid);

foreach ($readerKid->toArray() as $v) {
    echo "INSERT INTO nom_kid (code, \"name\") VALUES('{$v[0]}', '{$v[1]}');\n";
}

/**
 * terminal
 *  php scripts/parse_nkpd_kid.php > ./nkpd_kid.sql
 */
