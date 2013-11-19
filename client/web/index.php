<?php

require_once __DIR__.'/../vendor/autoload.php';

use Silex\Application;
use Silex\Provider\UrlGeneratorServiceProvider;
use Silex\Provider\TwigServiceProvider;
use OAuth2Demo\Client\Client;
use Symfony\Component\HttpFoundation\Request;

/** show all errors! */
ini_set('display_errors', 1);
error_reporting(E_ALL);

/** set up the silex application object */
$app = new Application();
$app->register(new UrlGeneratorServiceProvider());
$app->register(new TwigServiceProvider(), array(
    'twig.path' => __DIR__.'/../views',
));

$app['debug'] = true;

/** load the parameters configuration */
$parameterFile = __DIR__.'/../data/parameters.json';
$app['environments'] = array();
if (!$parameters = json_decode(file_get_contents($parameterFile), true)) {
    throw new Exception('unable to parse parameters file: '.$parameterFile);
}
$app['parameters'] = $parameters;

/** set up routes / controllers */
$app->mount('/', new OAuth2Demo\Client\Client());

$request = Request::createFromGlobals();
$app->run($request);
