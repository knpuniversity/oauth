<?php

namespace OAuth2Demo\Client\Controllers;

use OAuth2Demo\Client\Security\User;
use OAuth2Demo\Client\Storage\Connection;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * Base controller class to hide Silex-related implementation details
 */
class BaseController
{
    private $container;

    /**
     * See the event listener in kernel.controller for how this is set
     *
     * @param \Pimple $container
     */
    public function setContainer(\Pimple $container)
    {
        $this->container = $container;
    }

    /**
     * Render a twig template
     *
     * @param string $template The template filename
     * @param array $variables
     * @return string
     */
    public function render($template, array $variables = array())
    {
        return $this->container['twig']->render($template, $variables);
    }

    /**
     * Is the current user logged in?
     *
     * @return boolean
     */
    public function isUserLoggedIn()
    {
        return $this->container['security']->isGranted('IS_AUTHENTICATED_FULLY');
    }

    /**
     * @return User|null
     */
    public function getLoggedInUser()
    {
        if (!$this->isUserLoggedIn()) {
            return;
        }

        return $this->container['security']->getToken()->getUser();
    }

    /**
     * Saves the user to the databsae!
     *
     * @param User $user
     */
    public function saveUser(User $user)
    {
        /** @var Connection $db */
        $db = $this->container['connection'];

        $db->saveUser($user);
    }

    /**
     * Finds a User in the database for this email
     *
     * @param $email
     * @return bool|User
     */
    public function findUserByEmail($email)
    {
        /** @var \OAuth2Demo\Client\Storage\Connection $storage */
        $storage = $this->container['connection'];

        return $storage->getUser($email);
    }

    /**
     * @param string $routeName The name of the route
     * @param array $parameters Route variables
     * @param bool $absolute
     * @return string A URL!
     */
    public function generateUrl($routeName, array $parameters = array(), $absolute = false)
    {
        return $this->container['url_generator']->generate(
            $routeName,
            $parameters,
            $absolute
        );
    }

    /**
     * @param string $url
     * @param int $status
     * @return RedirectResponse
     */
    public function redirect($url, $status = 302)
    {
        return new RedirectResponse($url, $status);
    }

    /**
     * Returns a value from the parameters.json file
     *
     * @param string $name
     * @return string
     * @throws \InvalidArgumentException
     */
    public function getParameter($name)
    {
        $parameters = $this->container['parameters'];

        if (!isset($parameters[$name])) {
            throw new \InvalidArgumentException(sprintf('I don\'t see the key "%s" in parameters.json!', $name));
        }

        return $parameters[$name];
    }
}