<?php

namespace Classes\DBTable;

//**************************************************************************************
// FileName: REL_PersonToProject.php
//
// Copyright (c) 2006, 
//
// Permission is hereby granted, free of charge, to any person obtaining a
// copy of this software and associated documentation files (the "Software"),
// to deal in the Software without restriction, including without limitation
// the rights to use, copy, modify, merge, publish, distribute, sublicense,
// and/or sell copies of the Software, and to permit persons to whom the
// Software is furnished to do so, subject to the following conditions:
//
// The above copyright notice and this permission notice shall be included
// in all copies or substantial portions of the Software.
//
// THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS
// OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
// FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL
// THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
// LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING
// FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER
// DEALINGS IN THE SOFTWARE.
//**************************************************************************************
//require_once("C:/Inetpub/wwwroot/Classes/DBTable/TBL_DBTables.php");
//**************************************************************************************
// Definitions
//**************************************************************************************
//**************************************************************************************
// Class Definition
//**************************************************************************************
use Classes\TBLDBTables;


define("PROJECT_UNKNOWN", "0");
define("PROJECT_UNDEFINED", "1"); // don't use!
define("PROJECT_CONTRIBUTOR", "2");
define("PROJECT_REVIEWER", "3");
define("PROJECT_AUTHORITY", "4");
define("PROJECT_MANAGER", "5");
define("PROJECT_ADMIN", "6");

class RELPersonToProject {

    //******************************************************************************
    // Private functions 
    //******************************************************************************

    public static function AddSearchWhereClause($Database, &$SelectString, $PersonID = null, $ProjectID = null, $WebsiteID = null) {
        if ($PersonID !== null)
            TBL_DBTables::AddWhereClause($SelectString, "PersonID=$PersonID");
        if ($ProjectID !== null)
            TBL_DBTables::AddWhereClause($SelectString, "ProjectID=$ProjectID");

        if ($WebsiteID != null) { //
            $Set = TBL_Websites::GetSetFromID($Database, $WebsiteID);

            if ($Set->Field("LimitProjects") == 1) {
                TBL_DBTables::AddWhereClause($SelectString, "REL_PersonToProject.ProjectID IN (" .
                        "SELECT ProjectID " .
                        "FROM REL_WebsiteToProject " .
                        "WHERE (WebsiteID=$WebsiteID))");
            }
        }
        //		DebugWriteln("SelectString=$SelectString");
    }

    //******************************************************************************
    // Basic database functions
    //******************************************************************************

    public static function GetSet($Database, $PersonID = null, $ProjectID = null, $Role = null, $RequestedRole = null) {
        $SelectString = "SELECT * " .
                "FROM REL_PersonToProject ";

        if ($PersonID !== null)
            TBL_DBTables::AddWhereClause($SelectString, "PersonID=$PersonID");
        if ($ProjectID !== null)
            TBL_DBTables::AddWhereClause($SelectString, "ProjectID=$ProjectID");
        if ($Role !== null)
            TBL_DBTables::AddWhereClause($SelectString, "Role=$Role");
        if ($RequestedRole !== null)
            TBL_DBTables::AddWhereClause($SelectString, "RequestedRole=$RequestedRole");

        //DebugWriteln("SelectString=$SelectString");

        $Set = $Database->Execute($SelectString);

        return($Set);
    }

    public static function GetSetFromID($Database, $ID) {
        $ID = SafeInt($ID);

        $SelectString = "SELECT * " .
                "FROM REL_PersonToProject " .
                "WHERE ID='" . $ID . "'";

        $Set = $Database->Execute($SelectString);

        return($Set);
    }

    public static function GetTotalRows($Database, $PersonID = null, $ProjectID = null, $WebsiteID = null) {
        // get the query for $CurrentRow+$NumRows rows in reversed order

        $SelectString = "SELECT COUNT(*) " .
                "FROM REL_PersonToProject " .
                "INNER JOIN TBL_Projects ON REL_PersonToProject.ProjectID=TBL_Projects.ID"; // Use view to be able to include and show Project Name - GJN

        REL_PersonToProject::AddSearchWhereClause($Database, $SelectString, $PersonID, $ProjectID, $WebsiteID);

//		DebugWriteln("$SelectString");

        $Set = $Database->Execute($SelectString);

        return($Set->Field(1));
    }

    public static function GetRows($Database, &$CurrentRow, $NumRows, $TotalRows, $OrderByField, $DescendingFlag, $Fields = null, $PersonID = null, $ProjectID = null, $WebsiteID = null) {
        if ($CurrentRow >= $TotalRows) {
            $LastPage = (int) ((($TotalRows + $NumRows - 1) / $NumRows) - 1); // from PageSettings

            $CurrentRow = $LastPage * $NumRows; // go to the last page
        }

        //DebugWriteln("CurrentRow=$CurrentRow");

        $SelectString1 = "SELECT TOP " . ($TotalRows - $CurrentRow) . " REL_PersonToProject.ID " .
                "FROM REL_PersonToProject " .
                "INNER JOIN TBL_Projects ON REL_PersonToProject.ProjectID=TBL_Projects.ID"; // Use view to be able to include and show Project Name - GJN

        REL_PersonToProject::AddSearchWhereClause($Database, $SelectString1, $PersonID, $ProjectID, $WebsiteID);

        TBL_DBTables::AddOrderByClause($SelectString1, $OrderByField, !$DescendingFlag); // query the rows in the opposite order of what the user wants; $DescendingFlag needs to be false for this to work - GJN
        // create the query that gets the top $NumRows and reverses the order to make it the way the user wants

        $SelectString = TBL_DBTables::GetSelectClause(0, $NumRows, $Fields);

        $SelectString.=
                "FROM REL_PersonToProject " .
                "INNER JOIN TBL_Projects ON REL_PersonToProject.ProjectID=TBL_Projects.ID " . // Use view to be able to include and show Project Name - GJN
                "WHERE REL_PersonToProject.ID IN ($SelectString1) ";

        TBL_DBTables::AddOrderByClause($SelectString, $OrderByField, $DescendingFlag); // query the rows in the opposite order of what the user wants; $DescendingFlag needs to be false for this to work - GJN
//		DebugWriteln("SelectString=$SelectString");

        $Set = $Database->Execute($SelectString);

        return($Set);
    }

    public static function Insert($dbConn, $PersonID, $ProjectID, $Role = null, $RequestedRole = null) {
        $ExecString = "EXEC insert_REL_PersonToProject :PersonID";

        //$ID = $Database->DoInsert($ExecString);

        $stmt = $dbConn->prepare($ExecString);
        $stmt->bindValue("PersonID", $PersonID);
        $stmt->execute();
        $ID = $dbConn->lastInsertId();

        RELPersonToProject::Update($dbConn, $ID, $PersonID, $ProjectID, $Role, $RequestedRole);

        return($ID);
    }  

    public static function Update($dbConn, $ID, $PersonID, $ProjectID, $Role = null, $RequestedRole = null) {
        $UpdateString = "UPDATE REL_PersonToProject " .
                "SET PersonID= :PersonID, " .
                "ProjectID= :ProjectID ";

        if ($Role != null) {
            TBLDBTables::AddUpdateClause($UpdateString, "Role", $Role);
        }
        if ($RequestedRole != null) {
            TBLDBTables::AddUpdateClause($UpdateString, "RequestedRole", $RequestedRole);
        }
        $UpdateString = $UpdateString . " WHERE ID= :ID ";

        $stmt = $dbConn->prepare($UpdateString);
        $stmt->bindValue("PersonID", $PersonID);
        $stmt->bindValue("ProjectID", $ProjectID);
        $stmt->bindValue("ID", $ID);
        $stmt->execute(); 

        return($ID);
    }

    public static function Delete($Database, $ID) {
        TBL_DBTables::Delete($Database, "REL_PersonToProject", $ID);
    }

    //******************************************************************************
    // Additional functions
    //******************************************************************************

    public static function GetSetFromPersonID($Database, $PersonID) { // jjg - replace with call to GetSet()
        $SelectString = "SELECT * " .
                "FROM REL_PersonToProject " .
                "WHERE (PersonID='$PersonID')";

        $Set = $Database->Execute($SelectString);

        return($Set);
    }

    public static function GetPendingRequestsSet($Database, $ProjectID) { // gjn
        $SelectString = "SELECT * " .
                "FROM REL_PersonToProject " .
                "WHERE (ProjectID=$ProjectID) AND (RequestedRole IS NOT NULL)";

        $Set = $Database->Execute($SelectString);

        return($Set);
    }

    public function GetDistinctProjectMembersSet($Database, $ProjectID) {
        $SelectString = "SELECT DISTINCT REL_PersonToProject.PersonID, TBL_People.FirstName, TBL_People.LastName " .
                "FROM REL_PersonToProject INNER JOIN " .
                "TBL_People ON REL_PersonToProject.PersonID = TBL_People.ID " .
                "WHERE (REL_PersonToProject.Role IS NOT NULL) AND (REL_PersonToProject.ProjectID = $ProjectID) " .
                "ORDER BY TBL_People.FirstName ASC";

        //DebugWriteln("SelectString=$SelectString");

        $Set = $Database->Execute($SelectString);

        return($Set);
    }

    public function GetPersonProjectRolesSet($Database, $PersonID, $ProjectID) {
        $SelectString = "SELECT DISTINCT PersonID, ProjectID, Role, RequestedRole, Denied, ID " .
                "FROM REL_PersonToProject " .
                "WHERE (Role IS NOT NULL) AND (ProjectID = $ProjectID) AND (PersonID = $PersonID)";

        $Set = $Database->Execute($SelectString);

        return($Set);
    }

    public static function IsProjectMember($Database, $PersonID, $ProjectID) {
        $SelectString = "SELECT * " .
                "FROM REL_PersonToProject " .
                "WHERE (PersonID='$PersonID' AND ProjectID='$ProjectID') AND (Role IS NOT NULL)";

        $Set = $Database->Execute($SelectString);

        $IsMember = false;

        if ($Set->FetchRow()) {
            $IsMember = true;
        }

        return($IsMember);
    }

    public static function HasRole($dbConn, $ProjectID, $Role, $PersonID) {


        $SelectString = "SELECT * " .
                "FROM REL_PersonToProject " .
                "WHERE PersonID=:PersonID AND ProjectID=:ProjectID AND Role>=:Role";
        $stmt = $dbConn->prepare($SelectString);
        $stmt->bindValue("PersonID", $PersonID);
        $stmt->bindValue("ProjectID", $ProjectID);
         $stmt->bindValue("Role", $Role);
        $stmt->execute();
        $rel = $stmt->fetch();
        if (!$rel) {
            return  false;
        }
        else{
            return  true;
        }
    }

    public static function IsManager($Database) {
        //
        // Returns true if the user is a manager on at least one project//

        $Result = false;

        $PersonID = GetUserID();
        //DebugWriteln("PersonID=$PersonID");

        $SelectString = "SELECT * " .
                "FROM REL_PersonToProject " .
                "WHERE PersonID='$PersonID' AND Role=5"; // 5 is manager
        //DebugWriteln("SelectString=$SelectString");

        $Set = $Database->Execute($SelectString);

        if ($Set->FetchRow())
            $Result = true;

        return($Result);
    }

    public static function HasRequestedRole($Database, $ProjectID, $Role, $PersonID = null) {
        //
        // Returns true if the user has already requested the specified role on the project//

        $Result = false;

        if ($PersonID == null)
            $PersonID = GetUserID();

        $SelectString = "SELECT * " .
                "FROM REL_PersonToProject " .
                "WHERE PersonID='$PersonID' AND ProjectID=$ProjectID AND RequestedRole=$Role";

//		DebugWriteln("SelectString=$SelectString");

        $Set = $Database->Execute($SelectString);

        if ($Set->FetchRow())
            $Result = true;

        return($Result);
    }

    public static function GetNumProjectsPersonIsMember($Database, $PersonID, $WebsiteID = null) {
        $SelectString = "SELECT COUNT(DISTINCT TBL_Projects.ID) AS NumProjects
            FROM TBL_Projects INNER JOIN
              REL_WebsiteToProject ON TBL_Projects.ID = REL_WebsiteToProject.ProjectID INNER JOIN
              REL_PersonToProject ON TBL_Projects.ID = REL_PersonToProject.ProjectID
            WHERE (REL_PersonToProject.Role >= 2) AND (REL_PersonToProject.PersonID = $PersonID)";

        if ($WebsiteID != null) {
            $SelectString.=" AND (REL_WebsiteToProject.WebsiteID = $WebsiteID)";
        }

        $Set = $Database->Execute($SelectString);

        $NumProjects = $Set->Field("NumProjects");

        return($NumProjects);
    }

    function GetPersonsProjectSet($Database, $PersonID) {
        $SelectString = "SELECT DISTINCT REL_PersonToProject.PersonID, TBL_Projects.ID, TBL_Projects.ProjName " .
                "FROM REL_PersonToProject " .
                "INNER JOIN TBL_Projects ON REL_PersonToProject.ProjectID=TBL_Projects.ID " .
                "WHERE REL_PersonToProject.PersonID = $PersonID " .
                "ORDER BY ProjName";

        //DebugWriteln("SelectString=$SelectString");

        $Set = $Database->Execute($SelectString);

        return($Set);
    }

}

?>
