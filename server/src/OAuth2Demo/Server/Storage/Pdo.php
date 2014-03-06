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

    /**
     * Creates/updates a client/application
     *
     * @param $client_id
     * @param null $client_secret
     * @param null $redirect_uri
     * @param null $grant_types
     * @param null $scope
     * @param null $user_id
     * @return bool
     */
    public function setClientDetails($client_id, $client_secret = null, $redirect_uri = null, $grant_types = null, $scope = null, $user_id = null)
    {
        // if it exists, update it.
        if ($this->getClientDetails($client_id)) {
            $stmt = $this->db->prepare($sql = sprintf('UPDATE %s SET client_secret=:client_secret, redirect_uri=:redirect_uri, grant_types=:grant_types, scope=:scope where client_id=:client_id AND user_id=:user_id', $this->config['client_table']));
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

    /* The COOP storage methods */
    public function logApiCall($user_id, $action)
    {
        $timestamp = time();
        $sql = 'INSERT INTO api_log (user_id, action, timestamp) VALUES (:user_id, :action, :timestamp)';

        $stmt = $this->db->prepare($sql);

        return $stmt->execute(compact('user_id', 'action', 'timestamp'));
    }

    public function wasApiCalledRecently($user_id, $action, $seconds)
    {
        $timestamp = time() - $seconds;
        $sql = 'SELECT count(*) as count FROM api_log WHERE user_id=:user_id AND action=:action AND timestamp>=:timestamp';

        $stmt = $this->db->prepare($sql);
        $stmt->execute(compact('user_id', 'action', 'timestamp'));
        $result = $stmt->fetch();

        return $result && $result['count'] > 0;
    }

    public function addEggCount($user_id, $egg_count, $day = null)
    {
        $day = $day ?: strtotime(date('Y-m-d'));
        if (is_null($this->getEggCount($user_id, $day))) {
            $sql = 'INSERT INTO egg_count (user_id, day, count) VALUES (:user_id, :day, :egg_count)';
        } else {
            $sql = 'UPDATE egg_count SET count = count + :egg_count WHERE user_id=:user_id and day=:day';
        }

        $stmt = $this->db->prepare($sql);

        return $stmt->execute(compact('user_id', 'day', 'egg_count'));
    }

    public function getEggCount($user_id, $day = null)
    {
        $day = $day ?: strtotime(date('Y-m-d'));
        $sql = 'SELECT count from egg_count where user_id=:user_id and day=:day';
        $stmt = $this->db->prepare($sql);

        $stmt->execute(compact('user_id', 'day'));
        $result = $stmt->fetch();

        return $result ? $result['count'] : null;
    }

    public function findUsernameById($id)
    {
        $stmt = $this->db->prepare($sql = sprintf('SELECT * from %s where id=:id', $this->config['user_table']));
        $stmt->execute(array('id' => $id));

        if (!$userInfo = $stmt->fetch()) {
            return false;
        }

        return $userInfo['username'];
    }

    public function truncateTable($tbl)
    {
        $sql = 'DELETE FROM '.$tbl;

        $stmt = $this->db->prepare($sql);

        $stmt->execute();
    }
}
