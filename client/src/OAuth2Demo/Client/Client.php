<?php

namespace OAuth2Demo\Client;

use OAuth2Demo\Client\Controllers\BaseController;
use Silex\Application;
use Silex\Provider\UrlGeneratorServiceProvider;
use Silex\Provider\TwigServiceProvider;
use Silex\ControllerProviderInterface;
use Silex\Provider\SessionServiceProvider;
use Guzzle\Http\Client as GuzzleClient;
use Silex\Provider\SecurityServiceProvider;
use OAuth2Demo\Client\Security\UserProvider;
use OAuth2Demo\Client\Storage\Connection;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\HttpKernel\Event\FilterControllerEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\HttpKernel\Tests\Controller;

class Client implements ControllerProviderInterface
{
    public function connect(Application $app)
    {
        // set up silex application
        $app->register(new UrlGeneratorServiceProvider());
        $app->register(new TwigServiceProvider(), array(
            'twig.path' => __DIR__.'/../../../views',
        ));

        // sets twig extension for client debug rendering
        $app->extend('twig', function(\Twig_Environment $twig) {
            $twig->addExtension(new Twig\JsonStringifyExtension());

            return $twig;
        });

        $this->configureSecurity($app);

        // set up the service container
        $this->setup($app);

        // create http client
        $app['http_client'] = new GuzzleClient('', array(
            // makes 400/500 responses not throw an exception
            'request.options' => array(
                'exceptions' => false,
            )
        ));

        // create the session
        $app->register(new SessionServiceProvider());

        if (!$app['session']->isStarted()) {
            $app['session']->start();
        }

        // creates a new controller based on the default route
        $routing = $app['controllers_factory'];

        /** @var EventDispatcher $dispatcher */
        $dispatcher = $app['dispatcher'];
        // a quick event listener to inject the container into our BaseController
        $dispatcher->addListener(
            KernelEvents::CONTROLLER,
            function(FilterControllerEvent $event) use ($app) {
                $controller = $event->getController();
                if (!is_array($controller)) {
                    return;
                }

                $controllerObject = $controller[0];
                if ($controllerObject instanceof BaseController) {
                    $controllerObject->setContainer($app);
                }
            }
        );

        // Set corresponding endpoints on the controller classes
        Controllers\Homepage::addRoutes($routing);
        Controllers\CoopOAuthController::addRoutes($routing);
        Controllers\CountEggs::addRoutes($routing);
        Controllers\UserManagement::addRoutes($routing);
        Controllers\FacebookOAuthController::addRoutes($routing);

        return $routing;
    }

    private function setup(Application $app)
    {
        $sqliteFile = __DIR__.'/../../../data/topcluck.sqlite';

        $app['pdo'] = $app->share(function () use ($sqliteFile) {
            $pdo = new \PDO('sqlite:'.$sqliteFile);
            $pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);

            return $pdo;
        });

        $app['connection'] = $app->share(function() use ($sqliteFile, $app) {
            return new Connection(
                $app['pdo'],
                $app['security.encoder_factory'],
                // to avoid a circular reference situation
                $app
            );
        });

        // ensure our Sqlite database exists
        $app['parameters'] = $this->loadParameters();

        if (!file_exists($sqliteFile)) {
            $this->generateSqliteDb();
            $this->populateSqliteDb($app);
        }
    }

    public function loadParameters()
    {
        /** load the parameters configuration */
        $parameterFile = __DIR__.'/../../../data/parameters.json';
        if (!$parameters = json_decode(file_get_contents($parameterFile), true)) {
            throw new \Exception('unable to parse parameters file: '.$parameterFile);
        }

        return $parameters;
    }

    private function generateSqliteDb()
    {
        include_once(__DIR__.'/../../../data/rebuild_db.php');
    }

    private function populateSqliteDb($app)
    {
        $conn = new Connection(
            $app['pdo'],
            $app['security.encoder_factory'],
            // to avoid a circular reference situation
            $app
        );

        // user emails
        $leanna = 'everydayimcluckin@coop.com';
        $ryan = 'eggman@coop.com';
        $paige = 'loveandchickenpoop@coop.com';
        $brent = '99chickens@coop.com';

        // create stock users
        $conn->createUser($leanna, rand(), 'Leanna', 'Pelham');
        $conn->createUser($ryan, rand(), 'Ryan', 'Weaver');
        $conn->createUser($paige, rand(), 'Paige', 'Collett');
        $conn->createUser($brent, rand(), 'Brent', 'Shaffer');

        // create fake egg counts
        $conn->setEggCount($conn->getUser($brent), rand(1, 5), date('Y-m-d', strtotime('-10 days')));
        $conn->setEggCount($conn->getUser($ryan), rand(1, 2), date('Y-m-d', strtotime('-2 days')));
        $conn->setEggCount($conn->getUser($ryan), rand(1, 10), date('Y-m-d', strtotime('-12 days')));
        $conn->setEggCount($conn->getUser($leanna), rand(1, 20), date('Y-m-d', strtotime('-1 days')));
        $conn->setEggCount($conn->getUser($paige), rand(1, 10), date('Y-m-d', strtotime('-1 days')));
        $conn->setEggCount($conn->getUser($paige), rand(1, 20), date('Y-m-d', strtotime('-11 days')));
    }

    private function configureSecurity(Application $app)
    {
        $app['security.user_provider'] = $app->share(function () use ($app) {
            return new UserProvider($app['connection']);
        });

        $app->register(new SecurityServiceProvider(), array(
            'security.firewalls' => array(
                'main' => array(
                    'pattern' => '^/',
                    'form' => true,
                    'users' => $app->share(function () use ($app) {
                        return $app['security.user_provider'];
                    }),
                    'anonymous' => true,
                    'logout' => true,
                ),
            )
        ));

        // require login for application management
        $app['security.access_rules'] = array(
            array('^/coop/oauth', 'IS_AUTHENTICATED_ANONYMOUSLY'),
            array('^/coop', 'IS_AUTHENTICATED_FULLY'),
        );
    }
}
