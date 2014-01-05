<?php

namespace OAuth2Demo\Client\Controllers;

use Silex\Application;
use Symfony\Component\HttpFoundation\Request;

class ReceiveAuthorizationCode
{
    public static function addRoutes($routing)
    {
        $routing->get('/coop/receive_authcode', array(new self(), 'receiveAuthorizationCode'))->bind('authorize_redirect');
    }

    public function receiveAuthorizationCode(Application $app, Request $request)
    {
        /** @var \Twig_Environment $twig The Twig templating object */
        $twig = $app['twig'];

        $code = $request->get('code');
        // no "code" query parameter? The user denied the authorization request
        if (!$code) {
            return $twig->render('failed_authorization.twig', array('response' => $request->query->all()));
        }

        /*
         * TODO - put back later
        // verify the "state" parameter matches this user's session (this is like CSRF - very important!!)
        if ($request->get('state') !== $session->getId()) {
            return $twig->render('failed_authorization.twig', array('response' => array('error_description' => 'Your session has expired.  Please try again.')));
        }
        */

        // make the token request via http to /token
        // here are all the POST parameters we need to send to /token
        $parameters = array(
            'grant_type'    => 'authorization_code',
            'code'          => $code,
            'client_id'     => $app['parameters']['client_id'],
            'client_secret' => $app['parameters']['client_secret'],
            // re-create the same redirect URL. COOP needs this for security reasons!
            'redirect_uri'  => $app['url_generator']->generate('authorize_redirect', array(), true),
        );

        /** @var \Guzzle\Http\Client $httpClient simple object used to make http requests */
        $httpClient = $app['http_client'];
        $response = $httpClient->post(
            $app['parameters']['coop_host'].'/token',
            null,
            $parameters
        )->send();

        // the response is JSON - decode it to an array!
        $json = json_decode((string) $response->getBody(), true);

        // if there is no access_token, we have a problem!!!
        if (!isset($json['access_token'])) {
            return $twig->render('failed_token_request.twig', array('response' => $json ? $json : $response));
        }

        return $twig->render('show_access_token.twig', array('token' => $json['access_token']));
    }
}
