<?php

namespace OAuth2Demo\Client\Controllers;

class ActionableController
{
    // Connects a route in Silex
    public static function route($urlPath, $routeName, $routing)
    {
        $routing->get($urlPath, array(new self(), '__invoke'))->bind($routeName);
    }
}
