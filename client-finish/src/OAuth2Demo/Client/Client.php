<?php

namespace OAuth2Demo\Client;

use Silex\Application;
use Silex\ControllerProviderInterface;
use Silex\Provider\SessionServiceProvider;
use Guzzle\Http\Client as GuzzleClient;

class Client implements ControllerProviderInterface
{
    public function connect(Application $app)
    {
        // sets twig extension for client debug rendering
        $app['twig']->addExtension(new Twig\JsonStringifyExtension());

        // create http client
        $app['http_client'] = new GuzzleClient();

        // create the session
        $app->register(new SessionServiceProvider());

        if (!$app['session']->isStarted()) {
            $app['session']->start();
        }

        // creates a new controller based on the default route
        $routing = $app['controllers_factory'];

        // Set corresponding endpoints on the controller classes
        Controllers\Homepage::addRoutes($routing);
        Controllers\ReceiveAuthorizationCode::addRoutes($routing);
        Controllers\RequestToken::addRoutes($routing);
        Controllers\RequestResource::addRoutes($routing);
        Controllers\ReceiveImplicitToken::addRoutes($routing);

        return $routing;
    }
}
