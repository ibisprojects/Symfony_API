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
			
			if ($ComputerName=="ibis-test1") {
				$dbConn = new PDO("sqlsrv:server=IBIS-TEST1 ; database=invasive", DBConnection::$user, DBConnection::$password);
			} else if ($ComputerName=="ibis-live1") {
				$dbConn = new PDO("sqlsrv:server=IBIS-LIVE1 ; database=invasive", DBConnection::$user, DBConnection::$password);
			} else {
				$dbConn = new PDO("sqlsrv:server=IBIS-LIVE1 ; database=invasive", DBConnection::$user, DBConnection::$password);
			} 
				            
        } catch (PDOException $e) {
            return null;
        }
        return $dbConn;
    }
    
    private function getRecordSet($rs){
        return new DB_RecordSet($rs);
    }

}
