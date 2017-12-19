<?php

namespace Classes\DBTable;

//**************************************************************************************
// FileName: TBL_Permissions.php
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

use Classes\DBTable\TBLDBTables;

//**************************************************************************************
// Class Definition
//**************************************************************************************
class TBLPermissions {

    //******************************************************************************
    // Basic database functions
    //******************************************************************************

    public static function GetSet($Database, $PersonID = null, $Permission = null, $OrderByFlag = false) {
        $SelectString = "SELECT * " .
                "FROM TBL_Permissions ";

        if ($PersonID !== null)
            TBL_DBTables::AddWhereClause($SelectString, "PersonID=$PersonID");
        if ($Permission !== null)
            TBL_DBTables::AddWhereClause($SelectString, "Permission=$Permission");

        if ($OrderByFlag)
            $SelectString.="ORDER BY Permission ASC";

        $TextSet = $Database->Execute($SelectString);

        return($TextSet);
    }

    public static function GetSetFromID($Database, $PermissionID) {
        $PermissionID = SafeInt($PermissionID);

        $SelectString = "SELECT * " .
                "FROM TBL_Permissions " .
                "WHERE ID=$PermissionID";

//		DebugWriteln("SelectString=".$SelectString);

        $TextSet = $Database->Execute($SelectString);

        return($TextSet);
    }

    public static function Insert($dbConn, $PersonID, $Permission) {

        $ExecString = "EXEC insert_TBL_Permissions :PersonID";

        $stmt = $dbConn->prepare($ExecString);
        $stmt->bindValue("PersonID", $PersonID);
        $stmt->execute();
        $PermissionID = $dbConn->lastInsertId();

        $UpdateString = "UPDATE TBL_Permissions " .
                "SET Permission= :Permission  " .
                "WHERE ID = :PermissionID";

        $stmtUpdate = $dbConn->prepare($UpdateString);
        $stmtUpdate->bindValue("Permission", $Permission);
        $stmtUpdate->bindValue("PermissionID", $PermissionID);
        $stmtUpdate->execute();


        return($PermissionID);
    }

    public static function Delete($Database, $PermissionID) {
        TBL_DBTables::Delete($Database, "TBL_Permissions", $PermissionID);
    }

    //*******************************************************************
    // Additional functions
    //*******************************************************************
    public static function HasPermission($Database, $Permission, $PersonID = null) {
        if ($PersonID == null)
            $PersonID = GetUserID();

        $Result = false;

        $SelectString = "SELECT * " .
                "FROM TBL_Permissions " .
                "WHERE PersonID=$PersonID " .
                "AND Permission=$Permission";

        //DebugWriteln("SelectString=$SelectString");

        $Set = $Database->Execute($SelectString);

        if ($Set->FetchRow())
            $Result = true;

        return($Result);
    }

}

?>
