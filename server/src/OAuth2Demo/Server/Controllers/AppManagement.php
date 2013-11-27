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

        // get the requested client scope
        $scope = implode(' ', $app['request']->request->get('scope', []));

        // get the requested redirect_uri
        $redirect_uri = $app['request']->request->get('redirect_uri');

        // generate a random secret
        $secret = substr(md5(microtime()), 0, 32);

        // get the logged-in user and tie it to the newly-created client
        $user = $app['security']->getToken()->getUser();

        // create the client
        $app['storage']->setClientDetails($name, $secret, $redirect_uri, null, $scope, $user->getUsername());

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
