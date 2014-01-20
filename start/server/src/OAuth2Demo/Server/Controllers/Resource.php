<?php

namespace OAuth2Demo\Server\Controllers;

use OAuth2Demo\Server\Security\User;
use Silex\Application;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class Resource
{
    // Connects the routes in Silex
    public static function addRoutes($routing)
    {
        $routing->get('/application/api/{action}', [new self(), 'get'])->bind('api_call_form');
        $routing->get('/api/me', [new self(), 'userInformationAction'])->bind('api_user_information');
        $routing->post('/api/{id}/{action}', [new self(), 'apiAction'])->bind('api_call');

        // actions taken on your house using your authenticated account instead of a token
        $routing->post('/house/{action}', [new self(), 'webAction'])->bind('web_call');
    }

    /**
     * Shows a form where you can test an endpoint
     */
    public function get(Application $app, $action)
    {
        $token = $app['request']->query->get('access_token');
        $user = $app['security']->getToken()->getUser();

        return $app['twig']->render('api_call.twig', ['action' => $action, 'token' => $token, 'user' => $user]);
    }

    /**
     * The actual API endpoint
     */
    public function apiAction(Application $app, $action, $id)
    {
        // the name of the action, i.e. "barn-unlock" is also the name of the scope
        $scope = $action;

        // test all the OAuth stuffs!
        $response = $this->verifyResourceRequest($app, $scope);
        if ($response) {
            return $response;
        }

        // get the username from the id in the URL
        $username = $app['storage']->findUsernameById($id);

        if ($response = $this->enforceSecurity($username, $app)) {
            return $response;
        }

        list($message, $data) = $this->doAction($app, $username, $action);

        // return a generic API response - not that exciting
        // @TODO return something more valuable, like the name of the logged in user
        $apiResponse = array(
            'action'  => $action,
            'success' => true,
            'message' => $message,
            'data'    => $data,
        );

        return new JsonResponse($apiResponse);
    }

    /**
     * Retrieves user information tied to the token
     *
     * @param  Application           $app
     * @return JsonResponse|Response
     */
    public function userInformationAction(Application $app)
    {
        $response = $this->verifyResourceRequest($app, 'profile');
        if ($response) {
            return $response;
        }

        /** @var \OAuth2\Server $server */
        $server = $app['oauth_server'];
        $token = $server->getResourceController()->getToken();
        $username = $token['user_id'];

        // an abuse of the UserProvider... but that's ok :)
        /** @var User $user */
        $user = $app['security.user_provider']->findUser($username);

        $apiResponse = array(
            'id'    => $user->id,
            'email' => $user->email,
            'firstName' => $user->firstName,
            'lastName' => $user->lastName,
        );

        return new JsonResponse($apiResponse);
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
                    $message = 'The barn is already wide open! Let\'s throw a party!';
                } else {
                    $message = 'You just unlocked your barn! Watch out for strangers!';
                    $app['storage']->logApiCall($username, $action);
                }
                break;
            case 'toiletseat-down':
                if ($app['storage']->wasApiCalledRecently($username, $action, 20)) {
                    $message = 'Yea, the toilet seat is already down... you slob!';
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
                $eggCount = (int) $app['storage']->getEggCount($username);
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

    /**
     * Verifies if all the token, scope things are in order!
     *
     * If this returns a Response, it's an error Response and should be returned
     *
     * @param  Application $app
     * @param  null        $scope
     * @return Response
     */
    private function verifyResourceRequest(Application $app, $scope = null)
    {
        // get the oauth server (configured in src/OAuth2Demo/Server/Server.php)
        /** @var \OAuth2\Server $server */
        $server = $app['oauth_server'];

        // get the oauth response (configured in src/OAuth2Demo/Server/Server.php)
        $response = $app['oauth_response'];

        if (!$server->verifyResourceRequest($app['request'], $response, $scope)) {
            if ($response->getContent() === '{}') {
                return new Response(json_encode(array(
                    'error' => 'access_denied',
                    'error_description' => 'an access token is required')
                ));
            }

            return $response;
        }

        return false;
    }

    /**
     * Prevents you from making API requests to a user other than the user
     * represented by the token.
     *
     * @param $requestingUserId
     * @param Application $app
     * @return bool|JsonResponse
     * @throws \Exception
     */
    private function enforceSecurity($requestingUserId, Application $app)
    {
        /** @var \OAuth2\Server $server */
        $server = $app['oauth_server'];

        // get the oauth response (configured in src/OAuth2Demo/Server/Server.php)
        $response = $app['oauth_response'];

        $token = $server->getResourceController()->getAccessTokenData(
            $app['request'],
            $response
        );

        if (!$token) {
            throw new \Exception('You should verify the access token before checking security!');
        }

        if ($token['user_id'] != $requestingUserId) {
            return new JsonResponse(array(
                'error' => 'access_denied',
                'error_message' => 'You do not have access to take this action on behalf of this user'
            ), 401);
        }

        return false;
    }
}
