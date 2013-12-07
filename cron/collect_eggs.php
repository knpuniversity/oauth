<?php

if (count($argv) < 3) {
    die("Usage: collect_eggs.php [client_id] [client_secret]\n");
}

include __DIR__.'/vendor/autoload.php';
use Guzzle\Http\Client;

// define our base parameters
$endpoint = 'coop.apps.knpuniversity.com';
$token_url = sprintf('http://%s/token', $endpoint);
$resource_url = sprintf('http://%s/api/eggs-collect', $endpoint);

// create our http client (Guzzle)
$http = new Client();

/* 1. Get the Access Token */

// get the parameters from the command input
$parameters = array(
    'client_id'     => $argv[1],
    'client_secret' => $argv[2],
    'grant_type'    => 'client_credentials',
);

// make a request to the token url
$response   = $http->post($token_url, null, $parameters)->send();
$token_json = json_decode((string) $response->getBody(), true);
$token      = $token_json['access_token'];

printf("Received access token: $token\n");

/* 2. Call the APIs with the Access Token */

// create OAuth2 Authorization header using the Access Token
$headers = array('Authorization' => sprintf('Bearer %s', $token['access_token']));

// make the request
$response = $http->post($resource_url, $headers)->send();
$api_response = json_decode((string) $response->getBody(), true);
$message = $api_response['message'];

printf("API Response: $message\n");
