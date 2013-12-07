<?php

if (count($argv) < 3) {
    die("Usage: collect_eggs.php [client_id] [client_secret]\n");
}

include __DIR__.'/vendor/autoload.php';
use Guzzle\Http\Client;

// define our base parameters
$endpoint = 'localhost:9000';

// create our http client (Guzzle)
$http = new Client();

/* 1. Get the Access Token */

// get the parameters from the command input
$parameters = array(
    'client_id'     => $argv[1],
    'client_secret' => $argv[2],
    'grant_type'    => 'client_credentials',
);

$token_url = sprintf('http://%s/token', $endpoint);

// make a request to the token url
$response = $http->post($token_url, null, $parameters)->send();
$token = json_decode((string) $response->getBody(), true);

printf("Received access token: $token[access_token]\n");

/* 2. Call the APIs with the Access Token */

// create OAuth2 Authorization header using the Access Token
$headers = array('Authorization' => sprintf('Bearer %s', $token['access_token']));

// get the resource url from parameters.json
$resource_url = sprintf('http://%s/api/eggs-collect', $endpoint);

// make the request
$response = $http->post($resource_url, $headers)->send();
$api_response = json_decode((string) $response->getBody(), true);

printf("API Response: \n$api_response[message]\n");