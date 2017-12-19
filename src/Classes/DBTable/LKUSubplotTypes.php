<?php

namespace Classes\DBTable;

//**************************************************************************************
// FileName: LKU_SubplotTypes.php
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
//**************************************************************************************
// Class Definition
//**************************************************************************************

class LKUSubplotTypes {

    //**********************************************************************************
    // Basic database functions
    //**********************************************************************************

    public static function GetSet($Database, $AreaSubtypeID = null) {
        $SelectString = "SELECT * " .
                "FROM LKU_SubplotTypes ";

        if ($AreaSubtypeID !== null)
            TBL_DBTables::AddWhereClause($SelectString, "AreaSubtypeID=$AreaSubtypeID");

        $Set = $Database->Execute($SelectString);

        return($Set);
    }

    public static function GetSetFromID($dbConn, $SubplotTypeID) {
        $SelectString = "SELECT * " .
                "FROM LKU_SubplotTypes " .
                "WHERE ID='$SubplotTypeID'";

       $stmt = $dbConn->prepare($SelectString);
        $stmt->execute();
        $Set= $stmt->fetch();
        if (!$Set) {
            return false;
        }
        return($Set);
    }

    public static function Delete($Database, $SubplotTypesID) {
        TBL_DBTables::Delete($Database, "LKU_SubplotTypes", $SubplotTypesID);
    }

    //**********************************************************************************
    // Additional functions
    //**********************************************************************************

    public static function GetTypeNameFromID($Database, $SubplotTypeID) {
        $SelectString = "SELECT * " .
                "FROM LKU_SubplotTypes " .
                "WHERE ID='$SubplotTypeID'";

        $Set = $Database->Execute($SelectString);

        $Name = $Set->Field("Name");

        return($Name);
    }

    public static function GetIDFromTypeName($Database, $SubplotTypeName, $AreaSubtypeID = null) {
        $SelectString = "SELECT * " .
                "FROM LKU_SubplotTypes " .
                "WHERE Name='$SubplotTypeName'";

        if ($AreaSubtypeID != null) {
            $SelectString = $SelectString . " AND AreaSubtypeID=$AreaSubtypeID";
        }

        //DebugWriteln("SelectString=$SelectString");

        $Set = $Database->Execute($SelectString);

        $ID = $Set->Field("ID");

        //DebugWriteln("ID=$ID");

        return($ID);
    }

    //**********************************************************************************
    // Additional functions
    //**********************************************************************************
}

?>
