<?php

namespace OAuth2Demo\Client\Controllers;

use Silex\Application;

class Authentication
{
    // Connects the routes in Silex
    public static function addRoutes($routing)
    {
        $routing->get('/login', array(new self(), 'login'))->bind('login');
    }

    public function login(Application $app)
    {
        die('Eventually redirect to login here!');
    }
}
