<?php

namespace OAuth2Demo\Client\Storage;

use OAuth2Demo\Client\Security\User;
use OAuth2Demo\Client\Security\UserProvider;
use Symfony\Component\Security\Core\Encoder\EncoderFactoryInterface;

class Connection
{
    private $db;

    private $encoderFactory;

    private $container;

    const TABLE_USER = 'users';

    public function __construct(\Pdo $pdo, EncoderFactoryInterface $encoderFactory, \Pimple $container)
    {
        $this->db = $pdo;
        $this->encoderFactory = $encoderFactory;
        $this->container = $container;
    }

    public function getUser($email)
    {
        $stmt = $this->db->prepare($sql = sprintf('SELECT * from %s where email=:email', self::TABLE_USER));
        $stmt->execute(array('email' => $email));

        if (!$userInfo = $stmt->fetch()) {
            return false;
        }

        return $this->getUserProvider()->createUser($userInfo);
    }

    public function saveUser(User $user)
    {
        if ($this->getUser($user->email)) {
            $stmt = $this->db->prepare(sprintf('UPDATE %s SET password=:password, firstName=:firstName, lastName=:lastName, coopAccessToken=:coopAccessToken, coopAccessExpiresAt=:coopAccessExpiresAt where email=:email', self::TABLE_USER));
        } else {
            $stmt = $this->db->prepare(sprintf('INSERT INTO %s (email, password, firstName, lastName, coopAccessToken, coopAccessExpiresAt) VALUES (:email, :password, :firstName, :lastName, :coopAccessToken, :coopAccessExpiresAt)', self::TABLE_USER));
        }

        return $stmt->execute(array(
            'email' => $user->email,
            'password' => $user->email,
            'firstName' => $user->firstName,
            'lastName' => $user->lastName,
            'coopAccessToken' => $user->coopAccessToken,
            'coopAccessExpiresAt' => $user->coopAccessExpiresAt ? $user->coopAccessExpiresAt->format(User::TIMESTAMP_FORMAT) : ''
        ));
    }

    public function createUser($email, $password, $firstName = null, $lastName = null)
    {
        // do not store in plaintext
        $password = $this->encodePassword(new User(), $password);

        $user = $this->getUserProvider()->createUser(array(
            'email' => $email,
            'password' => $password,
            'firstName' => $firstName,
            'lastName' => $lastName
        ));

        $this->saveUser($user);
    }

    private function encodePassword(User $user, $password)
    {
        $encoder = $this->encoderFactory->getEncoder($user);

        // compute the encoded password for foo
        return $encoder->encodePassword($password, $user->getSalt());
    }

    /**
     * @return UserProvider
     */
    private function getUserProvider()
    {
        return $this->container['security.user_provider'];
    }
}