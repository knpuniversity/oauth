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

        // ensure our Sqlite database exists
        if (!file_exists($sqliteFile = __DIR__.'/../../../data/oauth.sqlite')) {
            $this->generateSqliteDb();
        }

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
        Controllers\ReceiveAuthorizationCode::addRoutes($routing);
        Controllers\CountEggs::addRoutes($routing);
        Controllers\UserManagement::addRoutes($routing);

        return $routing;
    }

    private function setup(Application $app)
    {
        if (!file_exists($sqliteFile = __DIR__.'/../../../data/oauth.sqlite')) {
            $this->generateSqliteDb();
        }

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

        $app['parameters'] = $this->loadParameters();
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
            array('^/coop', 'IS_AUTHENTICATED_FULLY'),
        );
    }
}
