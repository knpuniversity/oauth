<?php

// determine where the sqlite DB will go
$dbfile = __DIR__.'/topcluck.sqlite';

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
$db->exec('CREATE TABLE users (
    email TEXT PRIMARY KEY ASC,
    firstName TEXT,
    lastName TEXT,
    password TEXT,
    coopUserId TEXT,
    coopAccessToken TEXT,
    coopAccessExpiresAt DateTime,
    coopRefreshToken TEXT,
    facebookUserId TEXT
)');

$db->exec('CREATE TABLE egg_count (email TEXT, day TIMESTAMP, count INTEGER)');

chmod($dbfile, 0777);
