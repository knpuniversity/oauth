<?php

namespace OAuth2Demo\Client\Storage;

class Connection
{
    private $pdo;

    const TABLE_USER = 'users';

    public function __construct(\Pdo $pdo)
    {
        $this->db = $pdo;
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
}