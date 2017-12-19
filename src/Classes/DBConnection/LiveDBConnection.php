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
			
			if ($ComputerName=="ibis-test1") {
				$dbConn = new PDO("sqlsrv:server=IBIS-TEST1 ; database=invasive", LiveDBConnection::$user, LiveDBConnection::$password);
			} else if ($ComputerName=="ibis-live1") {
				$dbConn = new PDO("sqlsrv:server=IBIS-LIVE1 ; database=invasive", LiveDBConnection::$user, LiveDBConnection::$password);
			} else {
				$dbConn = new PDO("sqlsrv:server=IBIS-LIVE1 ; database=invasive", LiveDBConnection::$user, LiveDBConnection::$password);
			}
			
			//$dbConn = new PDO(LiveDBConnection::$dsn, LiveDBConnection::$user, LiveDBConnection::$password);
			
        } catch (PDOException $e) {
            return null;
        }
        return $dbConn;
    }
    
    private function getRecordSet($rs){
        return new DB_RecordSet($rs);
    }

}
