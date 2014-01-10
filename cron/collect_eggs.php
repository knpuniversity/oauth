<?php

include __DIR__.'/vendor/autoload.php';
use Guzzle\Http\Client;

// create our http client (Guzzle)
$http = new Client('http://coop.apps.knpuniversity.com', array(
    'request.options' => array(
        'exceptions' => false,
    )
));

/* 1. Get the Access Token */
$request = $http->post('/token', null, array(
    'client_id'     => 'Brent\'s Lazy CRON Job',
    'client_secret' => 'a2e7f02def711095f83f2fb04ecbc0d3',
    'grant_type'    => 'client_credentials',
));

// make a request to the token url
$response = $request->send();
$responseBody = $response->getBody(true);
var_dump($responseBody);die;

$accessToken = 'GET THIS FROM YOUR APPLICATION FOR NOW';

$request = $http->post('/api/2/eggs-collect');
$request->addHeader('Authorization', 'Bearer '.$accessToken);
$response = $request->send();
echo $response->getBody();

echo "\n\n";
