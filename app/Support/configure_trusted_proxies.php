<?php

use Symfony\Component\HttpFoundation\Request;

/**
 * Trust reverse-proxy headers (X-Forwarded-Proto, etc.) when TRUSTED_PROXIES is set.
 * Comma-separated IPs/hostnames, or Symfony token PRIVATE_SUBNETS / REMOTE_ADDR.
 *
 * @see https://symfony.com/doc/current/deployment/proxies.html
 */
if (! function_exists('configure_trusted_proxies_from_env')) {
    function configure_trusted_proxies_from_env(): void
    {
        $raw = $_ENV['TRUSTED_PROXIES'] ?? $_SERVER['TRUSTED_PROXIES'] ?? getenv('TRUSTED_PROXIES');
        $raw = is_string($raw) ? $raw : '';
        if (! is_string($raw) || trim($raw) === '') {
            return;
        }

        $proxies = array_values(array_filter(array_map('trim', explode(',', $raw))));
        if ($proxies === []) {
            return;
        }

        $headerSet = Request::HEADER_X_FORWARDED_FOR
            | Request::HEADER_X_FORWARDED_HOST
            | Request::HEADER_X_FORWARDED_PORT
            | Request::HEADER_X_FORWARDED_PROTO
            | Request::HEADER_X_FORWARDED_PREFIX;

        Request::setTrustedProxies($proxies, $headerSet);
    }
}
