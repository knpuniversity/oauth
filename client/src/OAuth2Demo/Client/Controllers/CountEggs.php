<?php

namespace OAuth2Demo\Client\Controllers;

use Silex\Application;

class CountEggs extends BaseController
{
    public static function addRoutes($routing)
    {
        $routing->get('/coop/count-eggs', array(new self(), 'countEggs'))->bind('count_eggs');
    }

    public function requestResource(Application $app)
    {
        die('todo');
        $twig   = $app['twig'];          // used to render twig templates
        $config = $app['parameters'];    // the configuration for the current oauth implementation
        $http   = $app['http_client'];   // simple class used to make http requests

        // 1) make an API request to count this user's eggs

        // pull the token from the request
        $token = $app['request']->get('token');

        // make the resource request with the token in the AUTHORIZATION header
        $headers =  array('Authorization' => sprintf('Bearer %s', $token));

        // make the resource request via http and decode the json response
        $endpoint = $config['resource_url'].'/barn-unlock';
        $response = $http->post($endpoint, $headers)->send();
        $json = json_decode((string) $response->getBody(), true);

        $resource_uri = sprintf('%s%saccess_token=%s', $endpoint, false === strpos($endpoint, '?') ? '?' : '&', $token);

        return $twig->render('show_resource.twig', array('response' => $json ? $json : $response, 'resource_uri' => $resource_uri));
    }
}
