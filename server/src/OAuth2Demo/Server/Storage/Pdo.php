<?php

namespace OAuth2Demo\Server\Storage;

use OAuth2\Storage\Pdo as OAuth2Pdo;

class Pdo extends OAuth2Pdo
{
    public function getAllClientDetails()
    {
        $stmt = $this->db->prepare(sprintf('SELECT * from %s', $this->config['client_table']));
        $stmt->execute();

        return $stmt->fetchAll();
    }

    public function setClientDetails($client_id, $client_secret = null, $redirect_uri = null, $grant_types = null, $scope = null)
    {
        // if it exists, update it.
        if ($this->getClientDetails($client_id)) {
            $stmt = $this->db->prepare($sql = sprintf('UPDATE %s SET client_secret=:client_secret, redirect_uri=:redirect_uri, grant_types=:grant_types, scope=:scope where client_id=:client_id', $this->config['client_table']));
        } else {
            $stmt = $this->db->prepare(sprintf('INSERT INTO %s (client_id, client_secret, redirect_uri, grant_types, scope) VALUES (:client_id, :client_secret, :redirect_uri, :grant_types, :scope)', $this->config['client_table']));
        }
        return $stmt->execute(compact('client_id', 'client_secret', 'redirect_uri', 'grant_types', 'scope'));
    }
}