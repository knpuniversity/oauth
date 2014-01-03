<?php

namespace OAuth2Demo\Client\Security;

use Symfony\Component\Security\Core\User\UserInterface;

class User implements UserInterface
{
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

    /**
     * @return string
     */
    public function getLastName()
    {
        return $this->lastName;
    }

    /**
     * @return string
     */
    public function getFirstName()
    {
        return $this->firstName;
    }

    /**
     * @return string
     */
    public function getEmail()
    {
        return $this->email;
    }

    /**
     * @return string
     */
    public function getAddress()
    {
        return $this->address;
    }
}
