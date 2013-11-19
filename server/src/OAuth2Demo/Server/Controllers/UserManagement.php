<?php

namespace OAuth2Demo\Server\Controllers;

use Silex\Application;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;

class UserManagement
{
    // Connects the routes in Silex
    static public function addRoutes($routing)
    {
        $routing->get('/register', [new self(), 'register'])->bind('user_register');
        $routing->post('/register', [new self(), 'registerHandle'])->bind('user_register_handle');
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

        $storage->setUser($email, $password, $firstName, $lastName);

        return new RedirectResponse($app['url_generator']->generate('home'));
    }

}
