<?php

// determine where the sqlite DB will go
$dbfile = __DIR__.'/coop.sqlite';

// remove sqlite file if it exists
if (file_exists($dbfile)) {
    unlink($dbfile);
}

if (!is_writable(__DIR__)) {
    // try to set permissions.
    if (!@chmod(__DIR__, 0777)) {
        throw new Exception("Unable to write to $dbfile");
    }
}

// rebuild the DB
$db = new PDO(sprintf('sqlite:%s', $dbfile));
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// OAuth tables
$db->exec('CREATE TABLE oauth_clients (client_id TEXT, client_secret TEXT, redirect_uri TEXT, grant_types TEXT, scope TEXT, user_id TEXT)');
$db->exec('CREATE TABLE oauth_access_tokens (access_token TEXT, client_id TEXT, user_id TEXT, expires TIMESTAMP, scope TEXT)');
$db->exec('CREATE TABLE oauth_authorization_codes (authorization_code TEXT, client_id TEXT, user_id TEXT, redirect_uri TEXT, expires TIMESTAMP, scope TEXT)');
$db->exec('CREATE TABLE oauth_refresh_tokens (refresh_token TEXT, client_id TEXT, user_id TEXT, expires TIMESTAMP, scope TEXT)');
$db->exec('CREATE TABLE oauth_users (id INTEGER PRIMARY KEY AUTOINCREMENT, username TEXT, password TEXT, first_name TEXT, last_name TEXT, address TEXT)');
$db->exec('CREATE TABLE oauth_scopes (type VARCHAR(255) NOT NULL DEFAULT "supported", scope VARCHAR(2000), client_id VARCHAR (80));');

// The COOP tables
$db->exec('CREATE TABLE egg_count (user_id TEXT, day TIMESTAMP, count INTEGER)');
$db->exec('CREATE TABLE api_log (user_id TEXT, action TEXT, timestamp TIMESTAMP)');

chmod($dbfile, 0777);
