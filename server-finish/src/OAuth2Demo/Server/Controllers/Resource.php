<?php

namespace OAuth2Demo\Server\Controllers;

use Silex\Application;
use Symfony\Component\HttpFoundation\Response;

class Resource
{
    // Connects the routes in Silex
    static public function addRoutes($routing)
    {
        $routing->get('/api/{action}', [new self(), 'resource'])->bind('api_action');
    }

    /**
     * This is called by the client app once the client has obtained an access
     * token for the current user.  If the token is valid, the resource (in this
     * case, the "friends" of the current user) will be returned to the client
     */
    public function resource(Application $app, $action)
    {
        // get the oauth server (configured in src/OAuth2Demo/Server/Server.php)
        $server = $app['oauth_server'];

        // get the oauth response (configured in src/OAuth2Demo/Server/Server.php)
        $response = $app['oauth_response'];

        if (!$server->verifyResourceRequest($app['request'], $response)) {
            if ('{}' == $response->getContent()) {
                $response->setData([
                    'error' => 'acess_denied',
                    'error_message' => 'you have to obtain an access token first',
                    'error_uri' => $app['url_generator']->generate('home', array(), true),
                ]);
            }
            return $response;
        } else {
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
}