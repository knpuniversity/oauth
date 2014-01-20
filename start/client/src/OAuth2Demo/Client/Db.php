<?php

namespace OAuth2Demo\Client;

class Db
{
    private $pdo;

    public function __construct(\PDO $pdo)
    {
        $this->pdo = $pdo;
    }

}
