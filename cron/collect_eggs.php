<?php

include __DIR__.'/vendor/autoload.php';
use Guzzle\Http\Client;

// create our http client (Guzzle)
$http = new Client('http://coop.apps.knpuniversity.com', array(
    'request.options' => array(
        'exceptions' => false,
    )
));

$accessToken = 'GET THIS FROM YOUR APPLICATION FOR NOW';

$request = $http->post('/api/2/eggs-collect');
$request->addHeader('Authorization', 'Bearer '.$accessToken);
$response = $request->send();
echo $response->getBody();

echo "\n\n";
