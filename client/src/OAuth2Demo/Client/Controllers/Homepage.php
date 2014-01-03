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
        // homepage when logged in
        if ($app['security']->isGranted('IS_AUTHENTICATED_FULLY')) {
            $client_id      = urlencode($app['parameters']['client_id']);
            $authorize_url  = $app['parameters']['coop_host'].'/authorize';
            $scope          = 'barn-unlock';

            // generates an absolute URL like http://localhost/receive_authcode
            // /receive_authcode is the page that the OAuth server will redirect back to
            // see ReceiveAuthorizationCode.php
            $redirect_uri   = $app['url_generator']->generate(
                'authorize_redirect',
                array(),
                UrlGeneratorInterface::ABSOLUTE_URL
            );

            return $app['twig']->render('dashboard.twig', array(
                'client_id'     => $client_id,
                'authorize_url' => $authorize_url,
                'scope'         => $scope,
                'redirect_uri'  => $redirect_uri,
                'session_id'    => $app['session']->getId()
            ));
        }

        return $app['twig']->render('index.twig');
    }
}
