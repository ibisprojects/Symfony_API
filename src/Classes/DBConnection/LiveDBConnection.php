<?php
namespace Classes\DBConnection;

use PDO;
use PDOException;

class LiveDBConnection {

    private static $user = 'sa';
    private static $password = 'cheatgrass';
	//private static $dsn = 'sqlsrv:server=IBIS-TEST1 ; database=invasive';

    public function connect() {
        try {
            $ComputerName="";

			if (isset($_SERVER['COMPUTERNAME'])) {
				$ComputerName=$_SERVER['COMPUTERNAME'];
			}

			if (isset($_ENV['COMPUTERNAME'])) {
				$ComputerName=$_ENV['COMPUTERNAME'];
			}

			$ComputerName=strtolower($ComputerName);

            $driver = 'sqlsrv';
            $host = 'host=IBIS-LIVE1';
            $db = 'invasive';

            if ($ComputerName=="ibis-test1") {
                $host = 'host=IBIS-TEST1';
            } else if ($ComputerName == "do-dev" || $ComputerName == "aline") {
                $driver = "pgsql";
                // $host = 'host=localhost';
                $host = 'host=138.197.14.189'; // new dev
                $db = 'citsci';
                self::$user = "postgres";
                self::$password = "zsPi3GWIlo";
            }

            $dsn = "{$driver}:{$host};dbname={$db};user=".self::$user.";password=".self::$password;
            $dbConn = new PDO($dsn);
        } catch (PDOException $e) {
            return null;
        }

        return $dbConn;
    }
}
