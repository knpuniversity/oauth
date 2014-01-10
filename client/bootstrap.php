<?php

require __DIR__.'/vendor/autoload.php';

use Silex\Application;

/** show all errors! */
ini_set('display_errors', 1);
error_reporting(E_ALL);

/** create the silex application object */
$app = new Application();
$app['debug'] = true;

/** set up routes / controllers */
$app->mount('/', new OAuth2Demo\Client\Client());

return $app;
