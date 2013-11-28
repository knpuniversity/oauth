<?php

namespace OAuth2Demo\Client\Controllers;

use Silex\Application;

class Homepage
{
    // Connects the routes in Silex
    public static function addRoutes($routing)
    {
        $routing->get('/', array(new self(), 'homepage'))->bind('homepage');
    }

    public function homepage(Application $app)
    {
        $client_id      = urlencode($app['parameters']['client_id']);
        $authorize_url  = $app['parameters']['authorize_url'];
        $scope          = $app['parameters']['scope'];
        $redirect_uri   = $app['parameters']['redirect_uri'];

        return $app['twig']->render('index.twig', [
            'client_id'     => $client_id,
            'authorize_url' => $authorize_url,
            'scope'         => $scope,
            'redirect_uri'  => $redirect_uri,
            'session_id'    => $app['session']->getId()
        ]);
    }
}
