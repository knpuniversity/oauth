<?php

include __DIR__.'/../vendor/autoload.php';
use Guzzle\Http\Client;
use Silex\Application;

/** show all errors! */
ini_set('display_errors', 1);
error_reporting(E_ALL);

/** create the silex application object */
$app = new Application();
$app['debug'] = true;

/** set up routes / controllers */
$app->mount('/', new OAuth2Demo\Client\Client());

// create our http client (Guzzle)
$client = new Client($app['parameters']['coop_url']);

// refresh all tokens expiring today or earlier
$expiringTokens = $app['connection']->getExpiringTokens(date('Y-m-d'));

foreach ($expiringTokens as $info) {
    /* 1. Get the Access Token */
    $request = $client->post('/token', null, array(
        'client_id'     => $app['parameters']['client_id'],
        'client_secret' => $app['parameters']['client_secret'],
        'grant_type'    => 'refresh_token',
        'refresh_token' => $info['coopRefreshToken'],
    ));

    $response = $request->send();
    $json = json_decode($response->getBody(true), true);
    $accessToken = $json['access_token'];
    $expires = date('Y-m-d H:i:s', time() + $json['expires_in']);
    $refreshToken = $json['refresh_token'];

    echo sprintf("Refreshing token for user %s: now expires %s\n\n", $info['email'], $expires);
    $app['connection']->saveNewTokens($info['email'], $accessToken, $expires, $refreshToken);
}

echo sprintf("Refreshed %s tokens\n\n", count($expiringTokens));
