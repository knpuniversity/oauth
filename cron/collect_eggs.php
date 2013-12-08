<?php

if (count($argv) < 3) {
    die("Usage: collect_eggs.php [client_id] [client_secret]\n");
}

include __DIR__.'/vendor/autoload.php';
use Guzzle\Http\Client;

// define our base parameters
$endpoint = '';
$token_url = '';
$resource_url = '';

// create our http client (Guzzle)
$http = new Client();

/* 1. Get the Access Token */

// get the parameters from the command input
$parameters = array(
    'client_id'     => '',
    'client_secret' => '',
    'grant_type'    => '',
);

// make a request to the token url

printf("Received access token: $token\n");

/* 2. Call the APIs with the Access Token */

// create OAuth2 Authorization header using the Access Token

// make the request

printf("API Response: $message\n");