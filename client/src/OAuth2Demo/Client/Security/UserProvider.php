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
        $userDetails = $this->connection->getUser($username);

        if (!$userDetails) {
            throw new UsernameNotFoundException(sprintf('Username "%s" does not exist.', $username));
        }

        $user = new User();
        $user->email = $userDetails['username'];
        $user->encodedPassword = $userDetails['password'];
        $user->firstName = $userDetails['first_name'];
        $user->lastName = $userDetails['last_name'];

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
