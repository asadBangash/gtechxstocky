<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

/**
 * When APP_URL uses https://, redirect plain HTTP requests to HTTPS (301).
 * Required for PWA (service worker, install) on production behind SSL termination:
 * trust proxies (TRUSTED_PROXIES) so $request->secure() reflects the client scheme.
 */
class ForceHttpsIfAppUrlSecure
{
    public function handle(Request $request, Closure $next)
    {
        $appUrl = (string) config('app.url', '');
        if ($appUrl === '') {
            return $next($request);
        }

        $scheme = parse_url($appUrl, PHP_URL_SCHEME);
        if (! is_string($scheme) || strtolower($scheme) !== 'https') {
            return $next($request);
        }

        if ($request->secure()) {
            return $next($request);
        }

        $httpsUrl = 'https://'.$request->getHttpHost().$request->getRequestUri();

        return redirect()->away($httpsUrl, 301);
    }
}
