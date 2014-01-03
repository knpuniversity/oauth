<?php

namespace OAuth2Demo\Client\Controllers;

use Silex\Application;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class Homepage
{
    // Connects the routes in Silex
    public static function addRoutes($routing)
    {
        $routing->get('/', array(new self(), 'homepage'))->bind('home');
    }

    public function homepage(Application $app)
    {
        $client_id      = urlencode($app['parameters']['client_id']);
        $authorize_url  = $app['parameters']['authorize_url'];
        $scope          = $app['parameters']['scope'];
        // generates an absolute URL like http://localhost/receive_authcode
        // /receive_authcode is the page that the OAuth server will redirect back to
        // see ReceiveAuthorizationCode.php
        $redirect_uri   = $app['url_generator']->generate(
            'authorize_redirect',
            array(),
            UrlGeneratorInterface::ABSOLUTE_URL
        );

        return $app['twig']->render('index.twig', [
            'client_id'     => $client_id,
            'authorize_url' => $authorize_url,
            'scope'         => $scope,
            'redirect_uri'  => $redirect_uri,
            'session_id'    => $app['session']->getId()
        ]);
    }
}
