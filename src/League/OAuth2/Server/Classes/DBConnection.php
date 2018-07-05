<?php
namespace League\OAuth2\Server\Classes;

use PDO;
use PDOException;

class DBConnection {

    private static $dsn = 'mysql:dbname=oauth_db;host=127.0.0.1';
    private static $user = 'root';
    private static $password = 'cheatgrass';

    public static function connect() {
        $ComputerName = '';

        if (isset($_SERVER['COMPUTERNAME'])) {
            $ComputerName=$_SERVER['COMPUTERNAME'];
        }

        if (isset($_ENV['COMPUTERNAME'])) {
            $ComputerName=$_ENV['COMPUTERNAME'];
        }

        $ComputerName=strtolower($ComputerName);

        if ($ComputerName == 'do-api1') {
            DBConnection::$dsn = 'mysql:dbname=oauth_db;host=citscidb1';
            DBConnection::$password = 'avqXxCs4jm';
        } else if ($ComputerName == 'aline') {
            DBConnection::$password = '@linr33@!';
        }

        try {
            $dbConn = new PDO(DBConnection::$dsn, DBConnection::$user, DBConnection::$password);
        } catch (PDOException $e) {
            return null;
        }
        return $dbConn;
    }

}
