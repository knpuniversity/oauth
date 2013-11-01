<?php

namespace OAuth2Demo\Client\Controllers;

use Silex\Application;

class Homepage
{
    // Connects the routes in Silex
    static public function addRoutes($routing)
    {
        $routing->get('/', array(new self(), 'homepage'))->bind('homepage');
    }

    public function homepage(Application $app)
    {
        return $app['twig']->render('index.twig', array('session_id' => $app['session']->getId()));
    }
}