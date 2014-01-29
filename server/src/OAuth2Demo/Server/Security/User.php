<?php

namespace OAuth2Demo\Server\Security;

use Symfony\Component\Security\Core\User\UserInterface;

class User implements UserInterface
{
    public $id;

    public $email;

    public $encodedPassword;

    public $address;

    public $firstName;

    public $lastName;

    public function getUsername()
    {
        return $this->email;
    }

    public function eraseCredentials()
    {
        $this->encodedPassword = null;
    }

    public function getPassword()
    {
        return $this->encodedPassword;
    }

    public function getRoles()
    {
        return array('ROLE_USER');
    }

    public function getSalt()
    {
        return null;
    }
}
