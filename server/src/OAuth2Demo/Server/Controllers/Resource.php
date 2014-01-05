<?php

namespace OAuth2Demo\Server\Controllers;

use Silex\Application;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class Resource
{
    // Connects the routes in Silex
    public static function addRoutes($routing)
    {
        $routing->get('/api/{action}', [new self(), 'get'])->bind('api_call');
        $routing->post('/api/{action}', [new self(), 'apiAction']);
        // actions taken on your house using your authenticated account instead of a token
        $routing->post('/house/{action}', [new self(), 'webAction'])->bind('web_call');
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
    public function apiAction(Application $app, $action)
    {
        // get the oauth server (configured in src/OAuth2Demo/Server/Server.php)
        /** @var \OAuth2\Server $server */
        $server = $app['oauth_server'];

        // get the oauth response (configured in src/OAuth2Demo/Server/Server.php)
        $response = $app['oauth_response'];

        // the name of the action, i.e. "barn-unlock" is also the name of the scope
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

        $token = $server->getResourceController()->getToken();
        list($message, $data) = $this->doAction($app, $token['user_id'], $action);

        // return a generic API response - not that exciting
        // @TODO return something more valuable, like the name of the logged in user
        $api_response = array(
            'action'  => $action,
            'success' => true,
            'message' => $message,
            'data'    => $data,
        );

        return new Response(json_encode($api_response));
    }

    public function webAction(Application $app, $action)
    {
        $user = $app['security']->getToken()->getUser();

        list($message, $data) = $this->doAction($app, $user->getUsername(), $action);

        return $app['twig']->render('webAction.twig', array('message' => $message));
    }

    private function doAction($app, $username, $action)
    {
        $data = null;
        switch ($action) {
            case 'barn-unlock':
                if ($app['storage']->wasApiCalledRecently($username, $action, 20)) {
                    $message = 'The barn is now locked.  Just to be safe.';
                } else {
                    $message = 'You just unlocked your barn! Watch out for strangers!';
                    $app['storage']->logApiCall($username, $action);
                }
                break;
            case 'toiletseat-down':
                if ($app['storage']->wasApiCalledRecently($username, $action, 20)) {
                    $message = 'You put the toilet seat back up, for no good reason';
                } else {
                    $message = 'You just put the toilet seat down. You\'re a wonderful roommate!';
                    $app['storage']->logApiCall($username, $action);
                }
                break;
            case 'chickens-feed':
                if ($app['storage']->wasApiCalledRecently($username, $action, 20)) {
                    $message = 'You just fed them! Do you want them to explode??';
                } else {
                    $message = 'Your chickens are now full and happy';
                    $app['storage']->logApiCall($username, $action);
                }
                break;
            case 'eggs-collect':
                if ($app['storage']->wasApiCalledRecently($username, $action, 20)) {
                    $message = 'Hey, give the ladies a break. Makin\' eggs ain\'t easy!';
                } else {
                    $eggCount = rand(2, 5);
                    $app['storage']->addEggCount($username, $eggCount);
                    $message = sprintf('Hey look at that, %s eggs have been collected!', $eggCount);
                    $data = $eggCount;
                    $app['storage']->logApiCall($username, $action);
                }
                break;
            case 'eggs-count':
                $eggCount = $app['storage']->getEggCount($username);
                $message = sprintf('You have collected a total of %s eggs today', intval($eggCount));
                $data = $eggCount;
                $app['storage']->logApiCall($username, $action);
                break;
            default:
                throw new NotFoundHttpException('Unsupported action '.$action);
        }

        return array($message, $data);
    }

    private function markAsCalled($app, $action)
    {
        $app['session']->set(sprintf('api.%s.last_called', $action), time());
    }

    private function calledInLast($app, $action, $seconds)
    {
        if ($timestamp = $app['session']->get(sprintf('api.%s.last_called', $action))) {
            return $seconds > time() - $timestamp;
        }

        return false;
    }
}
