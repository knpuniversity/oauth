<?php

namespace OAuth2Demo\Server\Controllers;

use Silex\Application;

class AppManagement
{
    // Connects the routes in Silex
    static public function addRoutes($routing)
    {
        $routing->get('/application', [new self(), 'index'])->bind('app_management');
        $routing->post('/application', [new self(), 'add']);
    }

    /**
     * Create a client application
     */
    public function index(Application $app)
    {
        return $app['twig']->render('app_management.twig');
    }

    /**
     * Create a client application
     */
    public function add(Application $app)
    {
        if (!$name = $app['request']->request->get('name')) {
            return $app['twig']->render('app_management.twig', ['error' => '"name" is required']);
        }
        $secret = substr(md5(microtime()), 0, 32);
        $app['storage']->setClientDetails($name, $secret, $app['request']->request->get('redirect_uri'));

        return $app['twig']->render('app_created.twig', [
            'name'    => $name,
            'secret'  => $secret,
        ]);
    }
}
