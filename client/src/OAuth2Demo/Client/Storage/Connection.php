<?php

namespace OAuth2Demo\Client\Storage;

use OAuth2Demo\Client\Security\User;
use Symfony\Component\Security\Core\Encoder\EncoderFactoryInterface;

class Connection
{
    private $db;

    private $encoderFactory;

    const TABLE_USER = 'users';

    public function __construct(\Pdo $pdo, EncoderFactoryInterface $encoderFactory)
    {
        $this->db = $pdo;
        $this->encoderFactory = $encoderFactory;
    }

    public function getUser($username)
    {
        $stmt = $this->db->prepare($sql = sprintf('SELECT * from %s where username=:username', self::TABLE_USER));
        $stmt->execute(array('username' => $username));

        if (!$userInfo = $stmt->fetch()) {
            return false;
        }

        return $userInfo;
    }

    public function setUser($username, $password, $firstName = null, $lastName = null)
    {
        // do not store in plaintext
        $password = $this->encodePassword(new User(), $password);

        // if it exists, update it.
        if ($this->getUser($username)) {
            $stmt = $this->db->prepare($sql = sprintf('UPDATE %s SET password=:password, first_name=:firstName, last_name=:lastName where username=:username', self::TABLE_USER));
        } else {
            $stmt = $this->db->prepare(sprintf('INSERT INTO %s (username, password, first_name, last_name) VALUES (:username, :password, :firstName, :lastName)', self::TABLE_USER));
        }
        return $stmt->execute(compact('username', 'password', 'firstName', 'lastName'));
    }

    private function encodePassword(User $user, $password)
    {
        $encoder = $this->encoderFactory->getEncoder($user);

        // compute the encoded password for foo
        return $encoder->encodePassword($password, $user->getSalt());
    }
}