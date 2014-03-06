<?php

namespace OAuth2Demo\Server;

use Silex\Application;
use Silex\ControllerProviderInterface;
use OAuth2\HttpFoundationBridge\Response as BridgeResponse;
use OAuth2\Server as OAuth2Server;
use OAuth2\GrantType\AuthorizationCode;
use OAuth2\GrantType\ClientCredentials;
use OAuth2\GrantType\RefreshToken;
use OAuth2\Storage\Memory;
use OAuth2\Scope;
use OAuth2Demo\Server\Storage\Pdo;

class Server implements ControllerProviderInterface
{
    /**
     * function to create the OAuth2 Server Object
     */
    public function setup(Application $app)
    {
        // make sure the sqlite file is initialized
        $sqliteFile = __DIR__.'/../../../data/coop.sqlite';
        $dbFileExists = file_exists($sqliteFile);
        if (!$dbFileExists) {
            $this->generateSqliteDb();
        }

        // create PDO-based sqlite storage
        $storage = new Pdo(array('dsn' => 'sqlite:'.$sqliteFile));

        // if we created the db, lets put in some data
        if (!$dbFileExists) {
            $this->populateSqliteDb($storage);
        }

        // create array of supported grant types
        // todo - update the documentation in _authentication.twig when we add more
        $grantTypes = array(
            'authorization_code' => new AuthorizationCode($storage),
            'client_credentials' => new ClientCredentials($storage),
            'refresh_token'      => new RefreshToken($storage, array('always_issue_new_refresh_token' => true)),
        );

        // instantiate the oauth server
        $server = new OAuth2Server($storage, array('enforce_state' => false, 'allow_implicit' => true, 'access_lifetime' => 86400), $grantTypes);

        $app['api_actions']   =  [
            'barn-unlock'     => 'Unlock the Barn',
            'toiletseat-down' => 'Put the Toilet Seat Down',
            'chickens-feed'   => 'Feed Your Chickens',
            'eggs-collect'    => 'Collect Eggs from Your Chickens',
            'eggs-count'      => 'Get the Number of Eggs Collected Today',
        ];

        $app['scopes'] = array_merge($app['api_actions'], [
            'profile'         => 'Access Your Profile Data',
        ]);

        // add scopes
        $memory = new Memory(array(
          'supported_scopes' => array_keys($app['scopes']),
        ));

        $server->setScopeUtil(new Scope($memory));

        // add the server to the silex "container" so we can use it in our controllers (see src/OAuth2Demo/Server/Controllers/.*)
        $app['oauth_server'] = $server;
        $app['storage'] = $storage;

        /**
         * add HttpFoundataionBridge Response to the container, which returns a silex-compatible response object
         * @see (https://github.com/bshaffer/oauth2-server-httpfoundation-bridge)
         */
        $app['oauth_response'] = new BridgeResponse();
    }

    /**
     * Connect the controller classes to the routes
     */
    public function connect(Application $app)
    {
        // create the oauth2 server object
        $this->setup($app);

        // creates a new controller based on the default route
        $routing = $app['controllers_factory'];

        /* Set corresponding endpoints on the controller classes */
        Controllers\Home::addRoutes($routing);
        Controllers\AppManagement::addRoutes($routing);
        Controllers\UserManagement::addRoutes($routing);

        // For the OAUTH server
        Controllers\Authorize::addRoutes($routing);
        Controllers\Token::addRoutes($routing);
        Controllers\Resource::addRoutes($routing);

        return $routing;
    }

    private function generateSqliteDb()
    {
        include_once(__DIR__.'/../../../data/rebuild_db.php');
    }

    private function populateSqliteDb(Pdo $pdo)
    {
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
