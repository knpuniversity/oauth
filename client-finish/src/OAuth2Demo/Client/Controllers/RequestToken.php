<?php

namespace OAuth2Demo\Client\Controllers;

use Silex\Application;

class RequestToken
{
    static public function addRoutes($routing)
    {
        $routing->get('/request_token/authorization_code', [new self(), 'requestToken'])->bind('request_token_with_authcode');
    }

    public function requestToken(Application $app)
    {
        $twig   = $app['twig'];          // used to render twig templates
        $config = $app['parameters'];    // the configuration for the current oauth implementation
        $urlgen = $app['url_generator']; // generates URLs based on our routing
        $http   = $app['http_client'];   // simple class used to make http requests

        $code = $app['request']->get('code');

        // exchange authorization code for access token
        $query = [
            'grant_type'    => 'authorization_code',
            'code'          => $code,
            'client_id'     => $config['client_id'],
            'client_secret' => $config['client_secret'],
            'redirect_uri'  => $urlgen->generate('authorize_redirect', array(), true),
        ];

        // make the token request via http and decode the json response
        $response = $http->post($config['token_url'], null, $query, $config['http_options'])->send();
        $json = json_decode((string) $response->getBody(), true);

        // if it is succesful, display the token in our app
        if (isset($json['access_token'])) {
            return $twig->render('show_access_token.twig', array('token' => $json['access_token']));
        }

        return $twig->render('failed_token_request.twig', array('response' => $json ? $json : $response));
    }
}