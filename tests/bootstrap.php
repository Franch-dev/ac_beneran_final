<?php

$testingDatabaseDir = __DIR__.'/../database/testing';

if (! is_dir($testingDatabaseDir)) {
    mkdir($testingDatabaseDir, 0775, true);
}

foreach (['default.sqlite', 'main.sqlite', 'ac_service.sqlite', 'ac_anggota.sqlite', 'inventory.sqlite'] as $database) {
    $path = $testingDatabaseDir.'/'.$database;

    if (is_file($path)) {
        unlink($path);
    }

    touch($path);
}

require __DIR__.'/../vendor/autoload.php';
require __DIR__.'/TestCase.php';
