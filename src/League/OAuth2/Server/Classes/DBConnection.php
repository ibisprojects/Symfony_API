<?php
namespace League\OAuth2\Server\Classes;

use PDO;
use PDOException;

class DBConnection {

    private static $dsn = 'mysql:dbname=oauth_db;host=127.0.0.1';
    private static $user = 'root';
    private static $password = 'cheatgrass';

    public static function connect() {
        try {
            $dbConn = new PDO(DBConnection::$dsn, DBConnection::$user, DBConnection::$password);
        } catch (PDOException $e) {
            return null;
        }
        return $dbConn;
    }

}
