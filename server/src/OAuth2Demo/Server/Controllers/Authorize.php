<?php

namespace OAuth2Demo\Server\Controllers;

use Silex\Application;
use OAuth2Demo\Server\Security\User;

class Authorize
{
    // Connects the routes in Silex
    public static function addRoutes($routing)
    {
        $routing->get('/authorize', [new self(), 'authorize'])->bind('authorize');
        $routing->get('/authorize/submit', [new self(), 'authorizeSubmit'])->bind('authorize_submit');
    }

    /**
     * The user is directed here by the client in order to authorize the client app
     * to access his/her data
     */
    public function authorize(Application $app)
    {
        // get the oauth server (configured in src/OAuth2Demo/Server/Server.php)
        $server = $app['oauth_server'];

         // get the oauth response (configured in src/OAuth2Demo/Server/Server.php)
        $response = $app['oauth_response'];

        // validate the authorize request.
        $error = null;
        if (!$server->validateAuthorizeRequest($app['request'], $response)) {
            // if this is an error with the client, do not redirect the user back there, as it may be malicious
            // otherwise, redirect back to the client with the errors in tow
            if (!in_array($response->getParameter('error'), ['redirect_uri_mismatch', 'invalid_uri', 'invalid_client'])) {
                return $response;
            }
            $error = $response->getParameter('error_description');
        }

        $scope = $server->getAuthorizeController()->getScope();

        // dispaly the "do you want to authorize?" form
        return $app['twig']->render('authorize.twig', [
            'client_id' => $app['request']->query->get('client_id'),
            'scope'     => $scope,
            'error'     => $error,
        ]);
    }

    /**
     * This is called once the user decides to authorize or cancel the client app's
     * authorization request
     */
    public function authorizeSubmit(Application $app)
    {
        // get the oauth server (configured in src/OAuth2Demo/Server/Server.php)
        /** @var \OAuth2\Server $server */
        $server = $app['oauth_server'];

         // get the oauth response (configured in src/OAuth2Demo/Server/Server.php)
        $response = $app['oauth_response'];

        // check the form data to see if the user authorized the request
        $authorized = (bool) $app['request']->query->get('authorize');

        /** @var User $user */
        $user = $app['security']->getToken()->getUser();

        // call the oauth server and return the response
        return $server->handleAuthorizeRequest($app['request'], $response, $authorized, $user->getUsername());
    }
}
