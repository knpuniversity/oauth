<?php

namespace OAuth2Demo\Client\Controllers;

use Silex\Application;

class Authentication extends ActionableController
{
    public function __invoke(Application $app)
    {
        die('Eventually redirect to login here!');
    }
}
