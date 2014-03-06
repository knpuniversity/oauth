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
        return $this->renderForm(true, array(), $app);
    }

    /**
     * Create a client application
     */
    public function add(Application $app)
    {
        if (!$name = $app['request']->request->get('name')) {
            return $this->renderForm(true, array('name' => 'name is required!'), $app);
        }

        return $this->saveClientDetailsFromRequest(
            null,
            $app['request'],
            $app
        );
    }

    /**
     * Create a client application
     */
    public function show(Application $app, $name)
    {
        $client = $this->getClientDetailsOr404($name, $app);

        return $app['twig']->render('app\show.twig', [
            'client' => $client,
        ]);
    }

    public function edit(Application $app, $name)
    {
        $client = $this->getClientDetailsOr404($name, $app);

        return $this->renderForm(false, array(), $app, $name);
    }

    public function update(Application $app, $name)
    {
        return $this->saveClientDetailsFromRequest($name, $app['request'], $app);
    }

    /**
     * @param string|null $clientId The client name for an existing client, else null
     * @param Request $request
     * @param Application $app
     * @return Response
     */
    private function saveClientDetailsFromRequest($clientId, Request $request, Application $app)
    {
        $isNew = !$clientId;
        $errors = array();

        if ($clientId) {
            // an existing app!
            $clientDetails = $this->getClientDetailsOr404($clientId, $app);

            $secret = $clientDetails['client_secret'];
        } else {
            // new app!
            $clientId = $request->request->get('name');

            // see if the app exists
            $clientDetails = $this->getClientDetails($clientId, $app);
            if ($clientDetails) {
                $errors['name'] = 'This application name is already taken - choose a unique application name!';
            }

            $secret = substr(md5(microtime()), 0, 32);
        }

        // get the requested client scope
        $scope = implode(' ', $request->request->get('scope', []));
        // get the requested redirect_uri
        $redirect_uri = $request->request->get('redirect_uri');

        // get the logged-in user and tie it to the newly-created client
        $user = $app['security']->getToken()->getUser();

        if (empty($errors)) {
            // create the client
            $app['storage']->setClientDetails($clientId, $secret, $redirect_uri, null, $scope, $user->getUsername());
        }

        // regardless, now return either the form or the show page
        if (empty($errors)) {
            return $app['twig']->render('app/show.twig', [
                'client' => $this->getClientDetails($clientId, $app),
                'message' => 'Congratulations!  You\'ve created your application!'
            ]);
        } else {
            return $this->renderForm($isNew, $errors, $app, $clientId);
        }
    }

    /**
     * Renders a new or edit form
     *
     * @param $isNew
     * @param array $errors
     * @param Application $app
     * @param null $clientId
     * @return mixed
     */
    private function renderForm($isNew, array $errors, Application $app, $clientId = null)
    {
        $formTemplate = 'app/' . ($isNew ? 'create.twig' : 'edit.twig');

        if ($isNew) {
            $clientDetails = array('client_id' => $clientId);
        } else {
            $clientDetails = $this->getClientDetails($clientId, $app);
        }

        return $app['twig']->render($formTemplate, array(
            // only show the existing client information on an edit
            'client' => $clientDetails,
            'errors' => $errors,
            'editName' => $isNew,
        ));
    }

    /**
     * @param $clientId
     * @param Application $app
     * @return array|null
     */
    private function getClientDetails($clientId, Application $app)
    {
        if ($clientId) {
            $clientDetails = $clientDetails = $app['storage']->getClientDetails(urldecode($clientId));
            if ($clientDetails) {
                $clientDetails['scope_arr'] = explode(' ', $clientDetails['scope']);
            }

            return $clientDetails;
        } else {
            return;
        }
    }

    private function getClientDetailsOr404($name, Application $app)
    {
        if (!$client = $this->getClientDetails($name, $app)) {
            $app->abort(404, "Application \"$name\" does not exist.");
        }

        return $client;
    }
}
