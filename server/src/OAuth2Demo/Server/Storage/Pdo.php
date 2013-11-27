<?php

namespace OAuth2Demo\Server\Storage;

use OAuth2\Storage\Pdo as OAuth2Pdo;

class Pdo extends OAuth2Pdo
{
    public function getAllClientDetails($user_id)
    {
        $stmt = $this->db->prepare(sprintf('SELECT * from %s where user_id = :user_id', $this->config['client_table']));
        $stmt->execute(compact('user_id'));

        return $stmt->fetchAll();
    }

    public function setClientDetails($client_id, $client_secret = null, $redirect_uri = null, $grant_types = null, $scope = null, $user_id = null)
    {
        // if it exists, update it.
        if ($this->getClientDetails($client_id)) {
            $stmt = $this->db->prepare($sql = sprintf('UPDATE %s SET client_secret=:client_secret, redirect_uri=:redirect_uri, grant_types=:grant_types, scope=:scope where client_id=:client_id, user_id=:user_id', $this->config['client_table']));
        } else {
            $stmt = $this->db->prepare(sprintf('INSERT INTO %s (client_id, client_secret, redirect_uri, grant_types, scope, user_id) VALUES (:client_id, :client_secret, :redirect_uri, :grant_types, :scope, :user_id)', $this->config['client_table']));
        }
        return $stmt->execute(compact('client_id', 'client_secret', 'redirect_uri', 'grant_types', 'scope', 'user_id'));
    }

    public function setUser($username, $password, $firstName = null, $lastName = null, $address = null)
    {
        // do not store in plaintext
        $password = sha1($password);

        // if it exists, update it.
        if ($this->getUser($username)) {
            $stmt = $this->db->prepare($sql = sprintf('UPDATE %s SET password=:password, first_name=:firstName, last_name=:lastName, address=:address where username=:username', $this->config['user_table']));
        } else {
            $stmt = $this->db->prepare(sprintf('INSERT INTO %s (username, password, first_name, last_name, address) VALUES (:username, :password, :firstName, :lastName, :address)', $this->config['user_table']));
        }
        return $stmt->execute(compact('username', 'password', 'firstName', 'lastName', 'address'));
    }
}