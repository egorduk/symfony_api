<?php

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Debug\Debug;

date_default_timezone_set('Europe/Minsk');

$isDebugEnabled = getenv('PHPAPP_IS_DEBUG_ENABLED') == '1';
$loader = require_once __DIR__.'/../app/bootstrap.php.cache';

if ($isDebugEnabled) {
    Debug::enable();
}

require_once __DIR__.'/../app/AppKernel.php';

$kernel = new AppKernel('docker', $isDebugEnabled);
$kernel->loadClassCache();
$request = Request::createFromGlobals();
$response = $kernel->handle($request);
$response->send();
$kernel->terminate($request, $response);
