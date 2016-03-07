<?php

use Symfony\Component\ClassLoader\ApcClassLoader;
use Symfony\Component\HttpFoundation\Request;

/**
 * @var Composer\Autoload\ClassLoader
 */
$loader = require __DIR__ . '/../app/autoload.php';
include_once __DIR__ . '/../var/bootstrap.php.cache';

// Enable APC for autoloading to improve performance.
// You should change the ApcClassLoader first argument to a unique prefix
// in order to prevent cache key conflicts with other applications
// also using APC.
$apcLoader = new ApcClassLoader('battleships-api-prod', $loader);
$loader->unregister();
$apcLoader->register(true);

$kernel = new AppKernel('prod', false);
$kernel->loadClassCache();
// HttpCache not used because it does not support HttpCacheBundle Tags so POST /events does not invalidate GET /events?gt=0

$request = Request::createFromGlobals();
$response = $kernel->handle($request);
$response->send();
$kernel->terminate($request, $response);
