<?php

require_once __DIR__.'/../vendor/autoload.php';

use Silex\Application;
use Silex\Provider\TwigServiceProvider;
use OAuth2Demo\Server\Server;
use OAuth2\HttpFoundationBridge\Request;

/** show all errors! */
ini_set('display_errors', 1);
error_reporting(E_ALL);

/** set up the silex application object */
$app = new Application();
$app->register(new Silex\Provider\UrlGeneratorServiceProvider());
$app->register(new TwigServiceProvider(), array(
    'twig.path' => __DIR__.'/../views',
));
$app['debug'] = true;

/** set up routes / controllers */
$app->mount('/', new Server());

$request = Request::createFromGlobals();
$app->run($request);
