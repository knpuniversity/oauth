<?php

namespace OAuth2Demo\Server\Controllers;

use Silex\Application;
use OAuth2\HttpFoundationBridge\Request;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class Token
{
    // Connects the routes in Silex
    public static function addRoutes($routing)
    {
        $routing->post('/token', array(new self(), 'tokenPost'))->bind('token_post');
        $routing->get('/token', array(new self(), 'tokenGet'))->bind('token_get');
    }

    /**
     * This is called by the client app once the client has obtained
     * an authorization code from the Authorize Controller (@see OAuth2Demo\Server\Controllers\Authorize).
     * If the request is valid, an access token will be returned
     */
    public function tokenPost(Application $app)
    {
        // get the oauth server (configured in src/OAuth2Demo/Server/Server.php)
        /** @var \OAuth2\Server $server */
        $server = $app['oauth_server'];

        // get the oauth response (configured in src/OAuth2Demo/Server/Server.php)
        $response = $app['oauth_response'];

        // let the oauth2-server-php library do all the work!
        return $server->handleTokenRequest($app['request'], $response);
    }

    /**
     * Provide a form in the browser for the user to submit an authorization code.
     * If the request is valid, an access token will be returned
     */
    public function tokenGet(Application $app)
    {
        // render the proper view based on the supplied "grant_type" parameter
        switch ($app['request']->query->get('grant_type')) {
            case 'client_credentials':
                $subRequest = Request::create('/token', 'POST', $app['request']->query->all());
                $response = $app->handle($subRequest, HttpKernelInterface::SUB_REQUEST, false);

                if (!$token = $response->getParameter('access_token')) {
                    throw new \Exception('failed to get access token from client credentials');
                }

                return $app['twig']->render('token/client_credentials.twig', [
                    'token' => $token,
                    'client_id' => $app['request']->query->get('client_id'),
                    'user_id'   => $response->getParameter('user_id'),
                ]);

            default:
                throw new NotFoundHttpException('Unsupported grant type');
        }
    }
}
