<?php

include __DIR__.'/vendor/autoload.php';
use Guzzle\Http\Client;

// create our http client (Guzzle)
$client = new Client('http://coop.apps.knpuniversity.com');

/* 1. Get the Access Token */
$request = $client->post('/token', null, array(
    'client_id'     => '',
    'client_secret' => '',
    'grant_type'    => '',
));

