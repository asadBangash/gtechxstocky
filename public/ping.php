<?php

// Minimal check: if this fails with HTTP 500, the problem is NOT Laravel (PHP/host/htaccess).
header('Content-Type: text/plain; charset=utf-8');
echo "PING OK\n";
echo 'PHP '.PHP_VERSION."\n";
echo 'Time '.date('c')."\n";
