<?php

include __DIR__.'/vendor/autoload.php';
use Guzzle\Http\Client;

// create our http client (Guzzle)
$client = new Client('http://coop.apps.knpuniversity.com', array(
    'request.options' => array(
        'exceptions' => false,
    )
));

/* 2. Call the APIs with the Access Token */
$request = $client->post('/api/2/eggs-collect');
$response = $request->send();
echo $response->getBody();

echo "\n\n";
