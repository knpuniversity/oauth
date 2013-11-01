<?php

namespace OAuth2Demo\Client\Controllers;

use OAuth2Demo\Client\Http\Curl;
use Silex\Application;

class RequestResource
{
    static public function addRoutes($routing)
    {
        $routing->get('/request_resource', array(new self(), 'requestResource'))->bind('request_resource');
    }

    public function requestResource(Application $app)
    {
        $twig   = $app['twig'];          // used to render twig templates
        $config = $app['parameters'];    // the configuration for the current oauth implementation
        $urlgen = $app['url_generator']; // generates URLs based on our routing
        $http   = $app['http_client'];   // simple class used to make http requests

        // pull the token from the request
        $token = $app['request']->get('token');

        // make the resource request with the token in the AUTHORIZATION header
        $headers =  array('Authorization' => sprintf('Bearer %s', $token));

        // determine the resource endpoint to call based on our config (do this somewhere else?)
        $apiRoute = $config['resource_route'];
        $endpoint = 0 === strpos($apiRoute, 'http') ? $apiRoute : $urlgen->generate($apiRoute, $config['resource_params'], true);

        // make the resource request via http and decode the json response
        $response = $http->get($endpoint, $headers, $config['http_options'])->send();
        $json = json_decode((string) $response->getBody(), true);

        $resource_uri = sprintf('%s%saccess_token=%s', $endpoint, false === strpos($endpoint, '?') ? '?' : '&', $token);

        return $twig->render('show_resource.twig', array('response' => $json ? $json : $response, 'resource_uri' => $resource_uri));
    }
}