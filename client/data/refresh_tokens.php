<?php

$app = require __DIR__.'/../bootstrap.php';
use Guzzle\Http\Client;

// create our http client (Guzzle)
$http = new Client('http://coop.apps.knpuniversity.com', array(
    'request.options' => array(
        'exceptions' => false,
    )
));

// refresh all tokens expiring today or earlier
/** @var \OAuth2Demo\Client\Storage\Connection $conn */
$conn = $app['connection'];

$expiringTokens = $conn->getExpiringTokens();

foreach ($expiringTokens as $userInfo) {

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
    var_dump($responseBody);die;
    $responseArr = json_decode($responseBody, true);

}
