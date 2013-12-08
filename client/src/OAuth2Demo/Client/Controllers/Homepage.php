<?php

namespace OAuth2Demo\Client\Controllers;

use Silex\Application;

class Homepage extends ActionableController
{
    public function __invoke(Application $app)
    {
        $client_id      = urlencode($app['parameters']['client_id']);
        $authorize_url  = $app['parameters']['authorize_url'];
        $scope          = $app['parameters']['scope'];
        $redirect_uri   = $app['parameters']['redirect_uri'];

        return $app['twig']->render('index.twig', array(
            'client_id'     => $client_id,
            'authorize_url' => $authorize_url,
            'scope'         => $scope,
            'redirect_uri'  => $redirect_uri,
            'session_id'    => $app['session']->getId(),
        ));
    }
}
