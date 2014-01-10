<?php

namespace OAuth2Demo\Server\Security;

use OAuth2Demo\Server\Storage\Pdo;
use Symfony\Component\Security\Core\User\UserProviderInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\Exception\UsernameNotFoundException;

class UserProvider implements UserProviderInterface
{
    private $storage;

    public function __construct(Pdo $storage)
    {
        $this->storage = $storage;
    }

    public function loadUserByUsername($username)
    {
        if (!$user = $this->findUser($username)) {
            throw new UsernameNotFoundException(sprintf('Username "%s" does not exist.', $username));
        }

        return $user;
    }

    public function findUser($username)
    {
        $userDetails = $this->storage->getUser($username);

        if (!$userDetails) {
            return false;
        }

        $user = new User();
        $user->id = $userDetails['id'];
        $user->email = $userDetails['username'];
        $user->encodedPassword = $userDetails['password'];
        $user->firstName = $userDetails['first_name'];
        $user->lastName = $userDetails['last_name'];
        $user->address = $userDetails['address'];
        // todo - address
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
