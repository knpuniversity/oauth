<?php

namespace OAuth2Demo\Server\Controllers;

use Silex\Application;

class AppManagement
{
    // Connects the routes in Silex
    static public function addRoutes($routing)
    {
        $routing->get('/application', [new self(), 'create'])->bind('app_create');
        $routing->post('/application', [new self(), 'add'])->bind('app_add');
        $routing->get('/application/{name}', [new self(), 'show'])->bind('app_show');
    }

    /**
     * Create a client application
     */
    public function create(Application $app)
    {
        return $app['twig']->render('app\create.twig');
    }

    /**
     * Create a client application
     */
    public function add(Application $app)
    {
        if (!$name = $app['request']->request->get('name')) {
            return $app['twig']->render('app\create.twig', ['error' => '"name" is required']);
        }

        $secret = substr(md5(microtime()), 0, 32);
        $scope = implode(' ', $app['request']->request->get('scope', []));
        $redirect_uri = $app['request']->request->get('redirect_uri');

        $app['storage']->setClientDetails($name, $secret, $redirect_uri, null, $scope);

        return $app['twig']->render('app\show.twig', [
            'client' => $app['storage']->getClientDetails($name),
            'message' => 'Congratulations!  You\'ve created your application!'
        ]);
    }

    /**
     * Create a client application
     */
    public function show(Application $app, $name)
    {
        if (!$client = $app['storage']->getClientDetails(urldecode($name))) {
            $app->abort(404, "Application \"$name\" does not exist.");
        }

        return $app['twig']->render('app\show.twig', [
            'client' => $client,
        ]);
    }
}
