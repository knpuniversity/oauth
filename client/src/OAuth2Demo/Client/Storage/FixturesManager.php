<?php

namespace OAuth2Demo\Client\Storage;

use Silex\Application;

class FixturesManager
{
    private $app;

    public function __construct(Application $app)
    {
        $this->app = $app;
    }

    public function resetDatabase()
    {
        include_once(__DIR__.'/../../../../data/rebuild_db.php');
    }

    public function clearTables()
    {
        /** @var Connection $conn */
        $conn = $this->app['connection'];
        $conn->truncateTable('users');
        $conn->truncateTable('egg_count');
    }

    public function populateSqliteDb()
    {
        // user emails
        $leanna = 'everydayimcluckin@coop.com';
        $ryan = 'eggman@coop.com';
        $paige = 'loveandchickenpoop@coop.com';
        $brent = '99chickens@coop.com';

        $conn = $this->app['connection'];

        // create stock users
        $conn->createUser($leanna, rand(), 'Leanna', 'Pelham');
        $conn->createUser($ryan, rand(), 'Ryan', 'Weaver');
        $conn->createUser($paige, rand(), 'Paige', 'Collett');
        $conn->createUser($brent, rand(), 'Farmer', 'Scott');

        // create fake egg counts
        $conn->setEggCount($conn->getUser($brent), rand(1, 5), date('Y-m-d', strtotime('-10 days')));
        $conn->setEggCount($conn->getUser($ryan), rand(1, 2), date('Y-m-d', strtotime('-2 days')));
        $conn->setEggCount($conn->getUser($ryan), rand(1, 10), date('Y-m-d', strtotime('-12 days')));
        $conn->setEggCount($conn->getUser($leanna), rand(1, 20), date('Y-m-d', strtotime('-1 days')));
        $conn->setEggCount($conn->getUser($paige), rand(1, 10), date('Y-m-d', strtotime('-1 days')));
        $conn->setEggCount($conn->getUser($paige), rand(1, 20), date('Y-m-d', strtotime('-11 days')));
    }
}
