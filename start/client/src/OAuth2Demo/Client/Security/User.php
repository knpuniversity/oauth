<?php

namespace OAuth2Demo\Client\Security;

use Symfony\Component\Security\Core\User\UserInterface;

class User implements UserInterface
{
    const TIMESTAMP_FORMAT = 'Y-m-d H:i:s';

    public $email;

    public $password;

    public $firstName;

    public $lastName;

    public $coopUserId;

    public $coopAccessToken;

    /** @var \DateTime */
    public $coopAccessExpiresAt;

    public $coopRefreshToken;

    public $facebookUserId;

    /**
     * Start: Security-related stuff
     */

    public function getUsername()
    {
        return $this->email;
    }
    public function eraseCredentials()
    {
        $this->password = null;
    }
    public function getPassword()
    {
        return $this->password;
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
     * Has the COOP access token expired?
     *
     * @return bool
     */
    public function hasCoopAccessTokenExpired()
    {
        if (!$this->coopAccessExpiresAt) {
            return true;
        }

        $now = new \DateTime('now');
        return ($now > $this->coopAccessExpiresAt);
    }
}
