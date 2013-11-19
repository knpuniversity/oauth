<?php

require_once __DIR__.'/../vendor/autoload.php';

use Silex\Application;
use Silex\Provider\TwigServiceProvider;
use OAuth2Demo\Server\Server;
use OAuth2\HttpFoundationBridge\Request;
use OAuth2Demo\Server\Security\UserProvider;
use Symfony\Component\Security\Core\Encoder\MessageDigestPasswordEncoder;

/** show all errors! */
ini_set('display_errors', 1);
error_reporting(E_ALL);

/** set up the silex application object */
$app = new Application();
$app->register(new Silex\Provider\UrlGeneratorServiceProvider());
$app->register(new TwigServiceProvider(), array(
    'twig.path' => __DIR__.'/../views',
));
$app->register(new \Silex\Provider\SessionServiceProvider());
$app->register(new Silex\Provider\SecurityServiceProvider(), array(
    'security.firewalls' => array(
        'main' => array(
            'pattern' => '^/',
            //'http' => true,
            'form' => true,
            'users' => $app->share(function () use ($app) {
                return new UserProvider($app['storage']);
            }),
            'anonymous' => true,
            'logout' => true,
        ),
    )
));
// configure the password hashing to be a simple sha1, to match with the OAuthServer
$app['security.encoder.digest'] = $app->share(function ($app) {
    // use the sha1 algorithm
    // don't base64 encode the password
    // use only 1 iteration
    return new MessageDigestPasswordEncoder('sha1', false, 1);
});
$app['debug'] = true;

/** set up routes / controllers */
$app->mount('/', new Server());

$request = Request::createFromGlobals();
$app->run($request);
