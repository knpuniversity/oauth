<?php

namespace OAuth2Demo\Client\Controllers;

use Silex\Application;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;

class CoopOAuthController extends BaseController
{
    public static function addRoutes($routing)
    {
        $routing->get('/coop/oauth/start', array(new self(), 'redirectToAuthorization'))->bind('coop_authorize_start');
        $routing->get('/coop/oauth/handle', array(new self(), 'receiveAuthorizationCode'))->bind('coop_authorize_redirect');
    }

    public function redirectToAuthorization(Request $request)
    {
        // generates an absolute URL like http://localhost/coop/oauth/handle
        // this is the page that the OAuth server will redirect back to
        // see ReceiveAuthorizationCode.php
        $redirectUrl = $this->generateUrl('coop_authorize_redirect', array(), true);

        $url = $this->getParameter('coop_url').'/authorize?'.http_build_query(array(
            'response_type' => 'code',
            'client_id' => $this->getParameter('client_id'),
            'redirect_uri' => $redirectUrl,
            'scope' => 'eggs-count profile',
            'state' => $request->getSession()->getId()
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
        $code = $request->get('code');
        // no "code" query parameter? The user denied the authorization request
        if (!$code) {
            return $this->render('failed_authorization.twig', array('response' => $request->query->all()));
        }

        // verify the "state" parameter matches this user's session (this is like CSRF - very important!!)
        if ($request->get('state') !== $request->getSession()->getId()) {
            return $this->render('failed_authorization.twig', array('response' => array('error_description' => 'Your session has expired.  Please try again.')));
        }

        // make the token request via http to /token
        // here are all the POST parameters we need to send to /token
        $parameters = array(
            'grant_type'    => 'authorization_code',
            'code'          => $code,
            'client_id'     => $this->getParameter('client_id'),
            'client_secret' => $this->getParameter('client_secret'),
            // re-create the same redirect URL. COOP needs this for security reasons!
            'redirect_uri'  => $this->generateUrl('coop_authorize_redirect', array(), true),
        );

        /** @var \Guzzle\Http\Client $httpClient simple object used to make http requests */
        $httpClient = $app['http_client'];
        $response = $httpClient->post(
            $this->getParameter('coop_url').'/token',
            null,
            $parameters
        )->send();

        // the response is JSON - decode it to an array!
        $json = json_decode((string) $response->getBody(), true);

        // if there is no access_token, we have a problem!!!
        if (!isset($json['access_token'])) {
            return $this->render('failed_token_request.twig', array('response' => $json ? $json : $response));
        }

        // yay! the all-important access token and its expiration date
        $token = $json['access_token'];
        $refreshToken = $json['refresh_token'];
        $expiresInSeconds = $json['expires_in'];
        $expiresAt = new \DateTime('+'.$expiresInSeconds.' seconds');

        // make an API request to /api/me to get user information
        $url = $this->getParameter('coop_url').'/api/me';
        $response = $this->getCurlClient()->get(
            $url,
            // these are the request headers. COOP expects an Authorization header
            array(
                'Authorization' => sprintf('Bearer %s', $token)
            )
        )->send();
        $json = json_decode((string) $response->getBody(), true);
        $coopUserId = $json['id'];

        // finally, get the current User object, set the data on it, and save it back to the database
        if ($this->isUserLoggedIn()) {
            $user = $this->getLoggedInUser();
        } else {
            // todo - what if a user with this email already exists?
            // todo - what if the coopUserId already exists?

            $user = $this->createUser(
                $json['email'],
                // a blank password - this user hasn't created a password yet!
                '',
                $json['firstName'],
                $json['lastName']
            );

            $this->loginUser($user);
        }

        $user->coopUserId = $coopUserId;
        $user->coopAccessToken = $token;
        $user->coopAccessExpiresAt = $expiresAt;
        $user->coopRefreshToken = $refreshToken;
        $this->saveUser($user);

        // redirect to the homepage!
        return $this->redirect($this->generateUrl('count_eggs'));
    }
}
