<?php

namespace OAuth2Demo\Client\Security;

use OAuth2Demo\Client\Storage\Connection;
use Symfony\Component\Security\Core\User\UserProviderInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\Exception\UsernameNotFoundException;

class UserProvider implements UserProviderInterface
{
    private $connection;

    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
    }

    public function loadUserByUsername($username)
    {
        $user = $this->connection->getUser($username);

        if (!$user) {
            throw new UsernameNotFoundException(sprintf('Email "%s" does not exist.', $username));
        }

        return $user;
    }

    /**
     * Takes data (probably from the database) and create a User object
     *
     * @param  array $userDetails
     * @return User
     */
    public function createUser(array $userDetails)
    {
        $user = new User();

        $user->email= isset($userDetails['email']) ? $userDetails['email'] : null;
        $user->password = isset($userDetails['password']) ? $userDetails['password'] : null;
        $user->firstName = isset($userDetails['firstName']) ? $userDetails['firstName'] : null;
        $user->lastName = isset($userDetails['lastName']) ? $userDetails['lastName'] : null;
        $user->coopUserId = isset($userDetails['coopUserId']) ? $userDetails['coopUserId'] : null;
        $user->coopAccessToken = isset($userDetails['coopAccessToken']) ? $userDetails['coopAccessToken'] : null;
        $user->coopRefreshToken = isset($userDetails['coopRefreshToken']) ? $userDetails['coopRefreshToken'] : null;
        $user->facebookUserId = isset($userDetails['facebookUserId']) ? $userDetails['facebookUserId'] : null;

        // get the coopAccessExpiresAt, but transform the "Y-m-d H:i:s" string into a DateTime object
        $coopAccessExpiresAt = isset($userDetails['coopAccessExpiresAt']) ? $userDetails['coopAccessExpiresAt'] : null;
        if ($coopAccessExpiresAt) {
            $coopAccessExpiresAt = \DateTime::createFromFormat(User::TIMESTAMP_FORMAT, $coopAccessExpiresAt);
        }
        $user->coopAccessExpiresAt = $coopAccessExpiresAt;

        return $user;
    }

    public function refreshUser(UserInterface $user)
    {
        if (!$user instanceof User) {
            throw new UnsupportedUserException(sprintf('Instances of "%s" are not supported.', get_class($user)));
        }

        return $this->loadUserByUsername($user->getUsername());
    }

    public function supportsClass($class)
    {
        return $class === 'OAuth2Demo\Server\Security\User';
    }
}
