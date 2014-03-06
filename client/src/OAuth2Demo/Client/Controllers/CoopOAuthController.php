<?php

namespace OAuth2Demo\Client\Controllers;

use Silex\Application;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Guzzle\Http\Client;

class CoopOAuthController extends BaseController
{
    public static function addRoutes($routing)
    {
        $routing->get('/coop/oauth/start', array(new self(), 'redirectToAuthorization'))->bind('coop_authorize_start');
        $routing->get('/coop/oauth/handle', array(new self(), 'receiveAuthorizationCode'))->bind('coop_authorize_redirect');
    }

    /**
     * This page actually redirects to the COOP authorize page and begins
     * the typical, "auth code" OAuth grant type flow.
     *
     * @param Request $request
     * @return RedirectResponse
     */
    public function redirectToAuthorization(Request $request)
    {
        $redirectUrl = $this->generateUrl('coop_authorize_redirect', array(), true);

        $url = 'http://coop.apps.knpuniversity.com/authorize?'.http_build_query(array(
            'response_type' => 'code',
            'client_id' => 'TopCluck',
            'redirect_uri' => $redirectUrl,
            'scope' => 'eggs-count profile'
        ));

        return $this->redirect($url);
    }

    /**
     * This is the URL that COOP will redirect back to after the user approves/denies access
     *
     * Here, we will get the authorization code from the request, exchange
     * it for an access token, and maybe do some other setup things.
     *
     * @param  Application             $app
     * @param  Request                 $request
     * @return string|RedirectResponse
     */
    public function receiveAuthorizationCode(Application $app, Request $request)
    {
        // equivalent to $_GET['code']
        $code = $request->get('code');

        if (!$code) {
            $error = $request->get('error');
            $errorDescription = $request->get('error_description');

            return $this->render('failed_authorization.twig', array(
                'response' => array(
                    'error' => $error,
                    'error_description' => $errorDescription
                )
            ));
        }

        $http = new Client('http://coop.apps.knpuniversity.com', array(
            'request.options' => array(
                'exceptions' => false,
            )
        ));

        $request = $http->post('/token', null, array(
            'client_id'     => 'TopCluck',
            'client_secret' => '2e2dfd645da38940b1ff694733cc6be6',
            'grant_type'    => 'authorization_code',
            'code'          => $code,
            'redirect_uri'  => $this->generateUrl('coop_authorize_redirect', array(), true),
        ));

        // make a request to the token url
        $response = $request->send();
        $responseBody = $response->getBody(true);
        $responseArr = json_decode($responseBody, true);
        var_dump($responseArr);die;

        // if there is no access_token, we have a problem!!!
        if (!isset($responseArr['access_token'])) {
            return $this->render('failed_token_request.twig', array(
                'response' => $responseArr ? $responseArr : $response
            ));
        }

        $accessToken = $responseArr['access_token'];
        $expiresIn = $responseArr['expires_in'];
        $expiresAt = new \DateTime('+'.$expiresIn.' seconds');

        $request = $http->get('/api/me');
        $request->addHeader('Authorization', 'Bearer '.$accessToken);
        $response = $request->send();
        $json = json_decode($response->getBody(), true);

        if ($this->isUserLoggedIn()) {
            $user = $this->getLoggedInUser();
        } else {
            $user = $this->findOrCreateUser($json);

            $this->loginUser($user);
        }
        $user->coopAccessToken = $accessToken;
        $user->coopUserId = $json['id'];
        $user->coopAccessExpiresAt = $expiresAt;
        $this->saveUser($user);

        // redirect back to the homepage
        return $this->redirect($this->generateUrl('home'));
    }

    private function findOrCreateUser(array $meData)
    {
        if ($user = $this->findUserByCOOPId($meData['id'])) {
            // this is an existing user. Yay!
            return $user;
        }

        if ($user = $this->findUserByEmail($meData['email'])) {
            // we match by email
            // we have to think if we should trust this. Is it possible to
            // register at COOP with someone else's email?
            return $user;
        }

        $user = $this->createUser(
            $meData['email'],
            // a blank password - this user hasn't created a password yet!
            '',
            $meData['firstName'],
            $meData['lastName']
        );

        return $user;
    }
}
