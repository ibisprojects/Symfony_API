<?php
namespace Classes\DBConnection;

use PDO;
use PDOException;



class DBConnection {

    private static $user = 'sa';
    private static $password = 'cheatgrass';

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
            } else if ($ComputerName == "do-api1" || $ComputerName == "do-dev" || $ComputerName == "aline") {
                $driver = "pgsql";
                // $host = 'host=localhost';
                $host = 'host=138.197.14.189'; // new dev
                self::$password = "zsPi3GWIlo";

                if($ComputerName == "do-api1") {
                    $host = 'host=citscidb1';
                    self::$password = "kRz6eN48";
                }

                $db = 'citsci';
                self::$user = "postgres";
            }

            $dsn = "{$driver}:{$host};dbname={$db};user=".self::$user.";password=".self::$password;
            $dbConn = new PDO($dsn);
        } catch (PDOException $e) {
            return null;
        }

        return $dbConn;
    }
}
