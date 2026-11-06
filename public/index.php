<?php

/**
 * Laravel - A PHP Framework For Web Artisans
 *
 * @author   Taylor Otwell <taylor@laravel.com>
 */
define('LARAVEL_START', microtime(true));

/*
|--------------------------------------------------------------------------
| Register The Auto Loader
|--------------------------------------------------------------------------
|
| Composer provides a convenient, automatically generated class loader for
| our application. We just need to utilize it! We'll simply require it
| into the script here so that we don't have to worry about manual
| loading any of our classes later on. It feels great to relax.
|
*/

require __DIR__.'/../vendor/autoload.php';

/*
|--------------------------------------------------------------------------
| Turn On The Lights
|--------------------------------------------------------------------------
|
| We need to illuminate PHP development, so let us turn on the lights.
| This bootstraps the framework and gets it ready for use, then it
| will load up this application so that we can run it and send
| the responses back to the browser and delight our users.
|
*/

$app = require_once __DIR__.'/../bootstrap/app.php';

/*
|--------------------------------------------------------------------------
| Trust proxies (Hostinger, Cloudflare, etc.) before Request::capture()
|--------------------------------------------------------------------------
|
| So X-Forwarded-Proto: https is honored and $request->secure() is true
| when the client used HTTPS. Required for ForceHttpsIfAppUrlSecure and PWA.
|
*/
$basePath = dirname(__DIR__);
require_once $basePath.'/app/Support/configure_trusted_proxies.php';
if (class_exists(\Dotenv\Dotenv::class) && is_file($basePath.'/.env')) {
    \Dotenv\Dotenv::createImmutable($basePath)->safeLoad();
}
configure_trusted_proxies_from_env();

/*
|--------------------------------------------------------------------------
| Run The Application
|--------------------------------------------------------------------------
|
| Once we have the application, we can handle the incoming request
| through the kernel, and send the associated response back to
| the client's browser allowing them to enjoy the creative
| and wonderful application we have prepared for them.
|
*/

$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);

$response = $kernel->handle(
    $request = Illuminate\Http\Request::capture()
);

$response->send();

$kernel->terminate($request, $response);
