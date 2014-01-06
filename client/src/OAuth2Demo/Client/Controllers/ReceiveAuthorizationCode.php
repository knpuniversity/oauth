<?php

namespace OAuth2Demo\Client\Controllers;

use Silex\Application;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;

class ReceiveAuthorizationCode extends BaseController
{
    public static function addRoutes($routing)
    {
        $routing->get('/coop/receive_authcode', array(new self(), 'receiveAuthorizationCode'))->bind('authorize_redirect');
    }

    public function receiveAuthorizationCode(Application $app, Request $request)
    {
        $code = $request->get('code');
        // no "code" query parameter? The user denied the authorization request
        if (!$code) {
            return $this->render('failed_authorization.twig', array('response' => $request->query->all()));
        }

        /*
         * TODO - put back later
        // verify the "state" parameter matches this user's session (this is like CSRF - very important!!)
        if ($request->get('state') !== $session->getId()) {
            return $this->render('failed_authorization.twig', array('response' => array('error_description' => 'Your session has expired.  Please try again.')));
        }
        */

        // make the token request via http to /token
        // here are all the POST parameters we need to send to /token
        $parameters = array(
            'grant_type'    => 'authorization_code',
            'code'          => $code,
            'client_id'     => $this->getParameter('client_id'),
            'client_secret' => $this->getParameter('client_secret'),
            // re-create the same redirect URL. COOP needs this for security reasons!
            'redirect_uri'  => $this->generateUrl('authorize_redirect', array(), true),
        );

        /** @var \Guzzle\Http\Client $httpClient simple object used to make http requests */
        $httpClient = $app['http_client'];
        $response = $httpClient->post(
            $this->getParameter('coop_host').'/token',
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
        $expiresInSeconds = $json['expires_in'];
        $expiresAt = new \DateTime('+'.$expiresInSeconds.' seconds');

        // make an API request to /api/me to get user information
        $url = $this->getParameter('coop_host').'/api/me';
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
        $user = $this->getLoggedInUser();
        $user->coopUserId = $coopUserId;
        $user->coopAccessToken = $token;
        $user->coopAccessExpiresAt = $expiresAt;
        $this->saveUser($user);

        // redirect to the homepage!
        return $this->redirect($this->generateUrl('home'));
    }
}
