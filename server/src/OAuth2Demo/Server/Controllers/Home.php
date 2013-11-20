<?php

namespace OAuth2Demo\Server\Controllers;

use Silex\Application;

class Home
{
    // Connects the routes in Silex
    static public function addRoutes($routing)
    {
        $routing->get('/', array(new self(), 'home'))->bind('home');
    }

    /**
     * Create a client application
     */
    public function home(Application $app)
    {
        $clients = $app['storage']->getAllClientDetails();

        return $app['twig']->render('home.twig', ['clients' => $clients]);
    }
}
