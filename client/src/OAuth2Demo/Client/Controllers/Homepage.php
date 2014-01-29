<?php

namespace OAuth2Demo\Client\Controllers;

use Silex\Application;

class Homepage extends BaseController
{
    // Connects the routes in Silex
    public static function addRoutes($routing)
    {
        $routing->get('/', array(new self(), 'homepage'))->bind('home');
    }

    public function homepage(Application $app)
    {
        $egg_counts = $app['connection']->getLeaderboardEggCounts();

        // homepage when logged in
        if ($this->isUserLoggedIn()) {
            return $this->render('dashboard.twig', array(
                'user'          => $this->getLoggedInUser(),
                'eggCount'      => $this->getTodaysEggCountForUser($this->getLoggedInUser()),
                'egg_counts'    => $egg_counts,
            ));
        }

        return $this->render('index.twig', array(
            'coopUrl' => $app['parameters']['coop_url'],
            'egg_counts' => $egg_counts,
        ));
    }
}
