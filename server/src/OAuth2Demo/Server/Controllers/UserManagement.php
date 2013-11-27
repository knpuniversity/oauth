<?php

namespace OAuth2Demo\Server\Controllers;

use Silex\Application;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\SecurityContextInterface;

class UserManagement
{
    // Connects the routes in Silex
    static public function addRoutes($routing)
    {
        $routing->get('/register', [new self(), 'register'])->bind('user_register');
        $routing->post('/register', [new self(), 'registerHandle'])->bind('user_register_handle');
        $routing->get('/login', [new self(), 'login'])->bind('user_login');
        $routing->post('/login_check', [new self(), 'loginCheck'])->bind('user_login_check');
        $routing->post('/logout', [new self(), 'logout'])->bind('user_logout');
    }

    /**
     * Registration page
     */
    public function register(Application $app)
    {
        return $app['twig']->render('user/register.twig');
    }

    /**
     * Processes the registration
     */
    public function registerHandle(Application $app, Request $request)
    {
        $errors = array();

        if (!$email = $request->request->get('email')) {
            $errors[] = '"email" is required';
        }
        if (!$password = $request->request->get('password')) {
            $errors[] = '"password" is required';
        }
        if (!$address = $request->request->get('address')) {
            $errors[] = '"address" is required';
        }

        $firstName = $request->request->get('firstName');
        $lastName = $request->request->get('lastName');

        /** @var \OAuth2Demo\Server\Storage\Pdo $storage */
        $storage = $app['storage'];

        // make sure we don't already have this user!
        if ($existingUser = $storage->getUser($email)) {
            $errors[] = 'A user with this email address is already registered!';
        }

        // errors? Show them!
        if (count($errors) > 0) {
            return $app['twig']->render('user\register.twig', ['errors' => $errors]);
        }

        $storage->setUser($email, $password, $firstName, $lastName, $address);

        return new RedirectResponse($app['url_generator']->generate('home'));
    }

    /**
     * Displays the login form
     *
     * @param Application $app
     */
    public function login(Application $app, Request $request)
    {
        return $app['twig']->render('user/login.twig', array(
            'error'         => $app['security.last_error']($request),
            'last_username' => $app['session']->get('_security.last_username'),
        ));
    }

    public function loginCheck(Application $app)
    {
        throw new \Exception('Should not get here - this should be handled magically by the security system!');
    }

    public function logout(Application $app)
    {
        throw new \Exception('Should not get here - this should be handled magically by the security system!');
    }
}
