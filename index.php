<?php

/**
 * Front controller when the web root is the project folder (Hostinger / Skyline-style).
 * Laragon should still use public/ as the vhost root; this file is for shared hosting
 * where the domain points at the folder that contains artisan.
 */
define('LARAVEL_START', microtime(true));

if (file_exists(__DIR__.'/storage/framework/maintenance.php')) {
    require __DIR__.'/storage/framework/maintenance.php';
}

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);

$response = $kernel->handle(
    $request = Illuminate\Http\Request::capture()
);

$response->send();

$kernel->terminate($request, $response);
