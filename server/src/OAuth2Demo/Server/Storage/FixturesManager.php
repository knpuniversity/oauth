<?php

namespace OAuth2Demo\Server\Storage;

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
        /** @var Pdo $pdo */
        $pdo = $this->app['storage'];
        $pdo->truncateTable('oauth_clients');
        $pdo->truncateTable('oauth_access_tokens');
        $pdo->truncateTable('oauth_authorization_codes');
        $pdo->truncateTable('oauth_refresh_tokens');
        $pdo->truncateTable('oauth_users');
        $pdo->truncateTable('oauth_scopes');
        $pdo->truncateTable('egg_count');
        $pdo->truncateTable('api_log');
    }

    public function populateSqliteDb()
    {
        /** @var Pdo $pdo */
        $pdo = $this->app['storage'];

        $pdo->setClientDetails(
            'TopCluck',
            '2e2dfd645da38940b1ff694733cc6be6',
            null,
            null,
            'eggs-collect profile',
            null
        );

        $pdo->setUser(
            'test@knpuniversity.com',
            'test',
            'Edgar',
            'Cat',
            null
        );
    }
}
