<?php

require_once __DIR__.'/../vendor/autoload.php';

use Silex\Application;
use OAuth2Demo\Client\Client;
use Symfony\Component\HttpFoundation\Request;

/** show all errors! */
ini_set('display_errors', 1);
error_reporting(E_ALL);

/** create the silex application object */
$app = new Application();
$app['debug'] = true;

/** set up routes / controllers */
$app->mount('/', new OAuth2Demo\Client\Client());

$request = Request::createFromGlobals();
$app->run($request);
