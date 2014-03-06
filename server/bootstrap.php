<?php

require_once __DIR__.'/vendor/autoload.php';

use OAuth2Demo\Server\Server;
use Silex\Application;
use Silex\Provider\TwigServiceProvider;
use OAuth2Demo\Server\Security\UserProvider;
use Symfony\Component\Security\Core\Encoder\MessageDigestPasswordEncoder;

/** show all errors! */
ini_set('display_errors', 1);
error_reporting(E_ALL);

/** set up the silex application object */
$app = new Application();
$app->register(new Silex\Provider\UrlGeneratorServiceProvider());
$app->register(new TwigServiceProvider(), array(
    'twig.path' => __DIR__.'/views',
));
$app['security.user_provider'] = $app->share(function () use ($app) {
    return new UserProvider($app['storage']);
});
$app->register(new \Silex\Provider\SessionServiceProvider());
$app['session.storage.options'] = array(
    'name' => 'oauth_server',
);
$app->register(new Silex\Provider\SecurityServiceProvider(), array(
    'security.firewalls' => array(
        'main' => array(
            'pattern' => '^/',
            'form' => true,
            'users' => $app->share(function () use ($app) {
                return $app['security.user_provider'];
            }),
            'anonymous' => true,
            'logout' => true,
        ),
    )
));

// require login for application management
$app['security.access_rules'] = array(
    array('^/application', 'IS_AUTHENTICATED_FULLY'),
    array('^/authorize', 'IS_AUTHENTICATED_FULLY'),
);

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

return $app;
