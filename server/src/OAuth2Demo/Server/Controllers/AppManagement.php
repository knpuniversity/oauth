<?php

namespace OAuth2Demo\Server\Controllers;

use Silex\Application;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\SecurityContextInterface;

class AppManagement
{
    // Connects the routes in Silex
    public static function addRoutes($routing)
    {
        $routing->get('/application', [new self(), 'create'])->bind('app_create');
        $routing->post('/application', [new self(), 'add'])->bind('app_add');
        $routing->get('/application/{name}', [new self(), 'show'])->bind('app_show');
        $routing->get('/application/edit/{name}', [new self(), 'edit'])->bind('app_edit');
        $routing->post('/application/edit/{name}', [new self(), 'update'])->bind('app_update');
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

        $this->saveClientDetailsFromRequest(
            $name,
            $app['request'],
            $app
        );

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

    public function edit(Application $app, $name)
    {
        if (!$client = $app['storage']->getClientDetails(urldecode($name))) {
            $app->abort(404, "Application \"$name\" does not exist.");
        }

        $client['scope_arr'] = explode(' ', $client['scope']);

        return $app['twig']->render('app\edit.twig', array(
            'client' => $client,
            'editName' => false,
        ));
    }

    public function update(Application $app, $name)
    {
        if (!$client = $app['storage']->getClientDetails(urldecode($name))) {
            $app->abort(404, "Application \"$name\" does not exist.");
        }

        $this->saveClientDetailsFromRequest($name, $app['request'], $app);

        $url = $app['url_generator']->generate('app_show', array('name' => $name));

        return new RedirectResponse($url);
    }

    private function saveClientDetailsFromRequest($clientId, Request $request, Application $app)
    {
        // get the requested client scope
        $scope = implode(' ', $request->request->get('scope', []));

        // get the requested redirect_uri
        $redirect_uri = $request->request->get('redirect_uri');

        $clientDetails = $app['storage']->getClientDetails(urldecode($clientId));
        if ($clientDetails) {
            $secret = $clientDetails['client_secret'];
        } else {
            // generate a random secret
            $secret = substr(md5(microtime()), 0, 32);
        }

        // get the logged-in user and tie it to the newly-created client
        $user = $app['security']->getToken()->getUser();

        // create the client
        $app['storage']->setClientDetails($clientId, $secret, $redirect_uri, null, $scope, $user->getUsername());
    }
}
