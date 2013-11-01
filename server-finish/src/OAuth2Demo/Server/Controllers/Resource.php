<?php

namespace OAuth2Demo\Server\Controllers;

use Silex\Application;
use Symfony\Component\HttpFoundation\Response;

class Resource
{
    // Connects the routes in Silex
    static public function addRoutes($routing)
    {
        $routing->get('/api/{action}', [new self(), 'get'])->bind('api_call');
        $routing->post('/api/{action}', [new self(), 'run']);
    }

    /**
     * This is called by the client app once the client has obtained an access
     * token for the current user.  If the token is valid, the resource (in this
     * case, the "friends" of the current user) will be returned to the client
     */
    public function get(Application $app, $action)
    {
        $token = $app['request']->query->get('access_token');
        return $app['twig']->render('api_call.twig', ['action' => $action, 'token' => $token]);
    }

    /**
     * This is called by the client app once the client has obtained an access
     * token for the current user.  If the token is valid, the resource (in this
     * case, the "friends" of the current user) will be returned to the client
     */
    public function run(Application $app, $action)
    {
        // get the oauth server (configured in src/OAuth2Demo/Server/Server.php)
        $server = $app['oauth_server'];

        // get the oauth response (configured in src/OAuth2Demo/Server/Server.php)
        $response = $app['oauth_response'];

        // the name of the action, i.e. "door-unlock" is also the name of the scope
        $scope = $action;

        if (!$server->verifyResourceRequest($app['request'], $response, $scope)) {
            if ($response->getContent() === '{}') {
                return new Response(json_encode(array(
                    'error' => 'access_denied',
                    'error_description' => 'an access token is required')
                ));
            }
            return $response;
        }

        // return a generic API response - not that exciting
        // @TODO return something more valuable, like the name of the logged in user
        $api_response = [
            'action' => $action,
            'success' => true,
            'message' => 'nice work!  You did it!',
        ];
        return new Response(json_encode($api_response));
    }
}