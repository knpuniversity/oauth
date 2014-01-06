<?php

use Silex\Application;
use Symfony\Component\HttpFoundation\Request;

$app = require_once __DIR__.'/../bootstrap.php';

$request = Request::createFromGlobals();
$app->run($request);
