<?php

namespace Classes\DBTable;

use Classes\Utilities\EmailUtil;

define("PERMISSION_USER", "1");

class TBLPeople {

    public static function GetPersonsName($dbConn, $PersonID) {
        $Name = "";

        $PersonSet = TBLPeople::GetSetFromID($dbConn, (int) $PersonID);

        if (count($PersonSet) > 0) {
            $Name = $PersonSet["FirstName"] . " " . $PersonSet["LastName"];
        }
        return($Name);
    }

    public static function GetPersonSetFromProjectID($dbConn, $ProjectID, $Role = null) {
        //print_r($ProjectID." ".$Role);
        //die();
        $SelectString = "SELECT \"TBL_People\".\"ID\" as \"ID\",\"FirstName\",\"LastName\" " .
                "FROM \"REL_PersonToProject\",\"TBL_People\" " .
                "WHERE \"ProjectID\"=$ProjectID " .
                "AND \"TBL_People\".\"ID\"=\"PersonID\" ";

        if ($Role !== null)
            $SelectString.="AND \"Role\"=$Role ";
        $stmt = $dbConn->prepare($SelectString);
        $stmt->execute();
        $PeopleSet = array();

        while ($PeopleEntry = $stmt->fetch()) {
            $PeopleSet[]= array("ID"=>$PeopleEntry["ID"],"FirstName"=>$PeopleEntry["FirstName"],"LastName"=>$PeopleEntry["LastName"]);
        }
        return($PeopleSet);
    }

    public static function verifyUser($dbConn, $UserID) {
        $UpdateString = "UPDATE \"TBL_People\" " .
                "SET \"ValidatedEmail\" = 1 " .
                "WHERE \"ID\" = :UserID";
        $stmt = $dbConn->prepare($UpdateString);
        $stmt->bindValue("UserID", $UserID);
        $stmt->execute();
    }

    public static function validateUsernamePassword($dbConn, $userName, $password) {
        $sql = "SELECT \"Login\" FROM \"TBL_People\"
                WHERE  \"Login\" = :username AND \"Password\" = :password";
        $stmt = $dbConn->prepare($sql);
        $stmt->bindValue("username", $userName);
        $stmt->bindValue("password", sha1($password));

        $stmt->execute();
        $user = $stmt->fetch();

        if (!$user) {
            return false;
        }
        return $user;
    }

    public static function GetSetFromID($dbConn, $Id) {
        $SelectString = "SELECT * " .
                "FROM \"TBL_People\" " .
                "WHERE \"ID\"='" . $Id . "'";
        $stmt = $dbConn->prepare($SelectString);
        $stmt->execute();
        $user = $stmt->fetch();
        if (!$user) {
            return false;
        }
        return $user;
    }

    public static function GetSetFromEmail($dbConn, $Email) {
        $SelectString = "SELECT * " .
                "FROM \"TBL_People\" " .
                "WHERE \"Email\"='" . $Email . "'";
        $stmt = $dbConn->prepare($SelectString);
        $stmt->execute();
        $user = $stmt->fetch();
        if (!$user) {
            return false;
        }
        return $user;
    }

    public static function GetSetFromLogin($dbConn, $Login) {
        $SelectString = "SELECT * " .
                "FROM \"TBL_People\" " .
                "WHERE \"Login\" = :Login ";

        $stmt = $dbConn->prepare($SelectString);
        $stmt->bindValue("Login", $Login);
        $stmt->execute();
        $user = $stmt->fetch();
        if (!$user) {
            return false;
        }
        return $user;
    }

    public static function Register($dbConn, $FirstName, $LastName, $Email, $Login, $Password) {
        $execString = "EXEC insert_TBL_People :LastName";
        $stmt = $dbConn->prepare($execString);
        $stmt->bindValue("LastName", $LastName);
        $stmt->execute();
        $personID = $dbConn->lastInsertId();

        $length = 10;
        $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $randomString = '';
        for ($i = 0; $i < $length; $i++) {
            $randomString .= $characters[rand(0, strlen($characters) - 1)];
        }
        $UpdateString = "UPDATE TBL_People SET FirstName=:FirstName, AgreedToDataUse='1', " .
                "AgreedToDataSharing='0', ShareWithSciStarter='1', Email=:Email, Login=:Login, Password=:HashPassword, WebsiteID='7', VerificationCode = :VerificationCode, DateRegistered = GETDATE() " .
                "WHERE ID=:PersonID";
        $stmtUpdate = $dbConn->prepare($UpdateString);
        $stmtUpdate->bindValue("FirstName", $FirstName);
        $stmtUpdate->bindValue("Email", $Email);
        $stmtUpdate->bindValue("Login", $Login);
        $stmtUpdate->bindValue("HashPassword", sha1($Password));
        $stmtUpdate->bindValue("PersonID", $personID);
        $stmtUpdate->bindValue("VerificationCode", $randomString);
        $stmtUpdate->execute();

        TBLPermissions::Insert($dbConn, $personID, PERMISSION_USER);

		$ServerName=$_SERVER['SERVER_NAME'];
		if (strpos($ServerName, 'test') !== false) {
			$ServerName="ibis-test1.nrel.colostate.edu";
		} else {
			$ServerName = "www.citsci.org";
		}

		$Link  = "http://$ServerName/cwis438/UserManagement/EmailValidation.php?WebSiteID=7&Code=$randomString&UserID=$personID";

		$Message = "<!DOCTYPE HTML PUBLIC '-//W3C//DTD HTML 3.2 Final//EN'> ".
		"<html> ".
		"<head> ".
		"<title>CitSci.org</title> " .
		"<link rel='stylesheet' href='http://www.citsci.org/cwis438/stylesheets/citsci.org.css' type='text/css'> " .
		"</head> " .
                   " <body bgcolor='#F5F4E8'> " .
                        "<table width='600' cellpadding='10' cellspacing='10' bgcolor='#F3F7FA' align='center'> " .
                            "<tr> " .
                                "<td> " .
                                    "<center><img src='http://www.citsci.org/WebContent/WS/citsci/images/MailChimpLogo.gif' width='200'></center> " .
                                        "<br/><br>" .
				"Dear $FirstName,<br><br>" .
                                "Thank you for registering with the CitSci.org app!<br><br> " .
                                "<table width='560' cellpadding='10' cellspacing='10' bgcolor='#ffffff' align='center'><tr><td> " .
                                "You're almost done!<br><br>".
				"<a href='$Link' style='font-size:24px;'>Please click here to verify your email and complete your registration.</a><br/><br/></td></tr></table><br>" .
				"----------------------------------------------<br><br>" .
				"You login information is:<br/><br/> " .
				"Username:&nbsp;&nbsp;<b>$Login</b><br/>" .
				"Password:&nbsp;&nbsp;<b>$Password</b><br/> " .
				"Email:&nbsp;&nbsp;$Email<br/><br/> ".
				"Please do not hesitate to contact us at webmaster@citsci.org with any questions you may have while exploring CitSci.org.<br/><br/> " .
				"Thank you,<br><br>" .
				"The CitSci.org Support Team \n" .
                        "<br><br></td></tr></table></body></html>";

		EmailUtil::SendHTMLMsg($Email, "Welcome to CitSci.org - Please complete your registration by verifying your email.", $Message, 7);

        return $personID;
    }

}

?>
