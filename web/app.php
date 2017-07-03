<?php

use Composer\Autoload\ClassLoader;
use Symfony\Component\HttpFoundation\Request;

/** @var ClassLoader $loader */
$loader = require __DIR__ . '/../app/autoload.php';

$kernel = new AppKernel('prod', false);
// HttpCache not used because it does not support HttpCacheBundle Tags so POST /events does not invalidate GET /events?gt=0

$request = Request::createFromGlobals();
$response = $kernel->handle($request);
$response->send();
$kernel->terminate($request, $response);
