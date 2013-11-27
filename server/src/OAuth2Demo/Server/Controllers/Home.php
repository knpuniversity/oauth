<?php

namespace OAuth2Demo\Server\Controllers;

use Silex\Application;

class Home
{
    // Connects the routes in Silex
    static public function addRoutes($routing)
    {
        $routing->get('/', array(new self(), 'home'))->bind('home');
        $routing->get('/api', array(new self(), 'apiHome'))->bind('api_home');
    }

    /**
     * Create a client application
     */
    public function home(Application $app)
    {
        if ($app['security']->isGranted('IS_AUTHENTICATED_FULLY')) {
            $user = $app['security']->getToken()->getUser();

            // we're logged in! Show the nice dashboard
            return $app['twig']->render('dashboard.twig', ['user' => $user]);
        } else {
            // we're anonymous, jsut show them some marketing jargon
            return $app['twig']->render('home.twig');
        }
    }

    public function apiHome(Application $app)
    {
        // this was the old homepage - it's being moved to an API area
        // not much work has been done on this yet, but the idea is that
        // the API docs will be here and all this functionality will
        // basically be an API playground

        $user    = $app['security']->getToken()->getUser();
        $clients = $app['storage']->getAllClientDetails($user->getUsername());

        return $app['twig']->render('apiHome.twig', ['clients' => $clients]);
    }
}
