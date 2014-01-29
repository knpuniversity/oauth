<?php

namespace OAuth2Demo\Server\Controllers;

use Silex\Application;
use Symfony\Component\HttpFoundation\Response;

class Home
{
    // Connects the routes in Silex
    public static function addRoutes($routing)
    {
        $routing->get('/', array(new self(), 'home'))->bind('home');
        $routing->get('/api', array(new self(), 'apiHome'))->bind('api_home');
        $routing->get('/db/reset', array(new self(), 'resetDb'));
    }

    /**
     * Create a client application
     */
    public function home(Application $app)
    {
        if ($app['security']->isGranted('IS_AUTHENTICATED_FULLY')) {
            $user = $app['security']->getToken()->getUser();

            // we're logged in! Show the nice dashboard
            return $app['twig']->render('dashboard.twig', ['user' => $user]);
        } else {
            // we're anonymous, jsut show them some marketing jargon
            return $app['twig']->render('home.twig');
        }
    }

    public function apiHome(Application $app)
    {
        $clients = array();
        if ($app['security']->isGranted('IS_AUTHENTICATED_FULLY')) {
            $user    = $app['security']->getToken()->getUser();
            $clients = $app['storage']->getAllClientDetails($user->getUsername());
        }

        return $app['twig']->render('apiHome.twig', array(
            'clients' => $clients,
            'exampleSuccessResponse' => array(
                'action'  => 'The action (e.g. "barn-unlock")',
                'success' => true,
                'message' => 'Some summary message',
                'data'    => 'A raw, related piece of data if applicable',
            ),
            'exampleErrorResponse' => array(
                'error'  => 'A key (e.g. access_denied) for the error',
                'error_description' => 'A longer description of the error',
            ),
        ));
    }

    public function resetDb()
    {
        $path = __DIR__.'/../../../../data/rebuild_db.php';
        exec('php '.$path, $output, $code);

        return new Response($code == 0 ? 'Success' : 'Failure');
    }
}
