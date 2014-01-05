<?php

namespace OAuth2Demo\Client\Controllers;

use Silex\Application;

class Homepage extends BaseController
{
    // Connects the routes in Silex
    public static function addRoutes($routing)
    {
        $routing->get('/', array(new self(), 'homepage'))->bind('home');
    }

    public function homepage(Application $app)
    {
        // homepage when logged in
        if ($this->isUserLoggedIn()) {
            $clientId      = urlencode($app['parameters']['client_id']);
            $authorizeUrl  = $app['parameters']['coop_host'].'/authorize';
            $scope         = 'eggs-count';

            // generates an absolute URL like http://localhost/receive_authcode
            // /receive_authcode is the page that the OAuth server will redirect back to
            // see ReceiveAuthorizationCode.php
            $redirectUri = $this->generateUrl('authorize_redirect', array(), true);

            return $this->render('dashboard.twig', array(
                'client_id'     => $clientId,
                'authorize_url' => $authorizeUrl,
                'scope'         => $scope,
                'redirect_uri'  => $redirectUri,
                'session_id'    => $app['session']->getId(),
                'user'          => $this->getLoggedInUser(),
                'eggCount'      => $this->getTodaysEggCountForUser($this->getLoggedInUser()),
            ));
        }

        return $this->render('index.twig', array(
            'coopUrl' => $app['parameters']['coop_host'],
        ));
    }
}
