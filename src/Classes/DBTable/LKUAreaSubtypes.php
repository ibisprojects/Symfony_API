<?php

namespace Classes\DBTable;

//**************************************************************************************
// FileName: LKU_AreaSubtypes.php
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
// Definitions
//**************************************************************************************

define("GEOMETRY_TYPE_UNKNOWN", 0);
define("GEOMETRY_TYPE_POINT", 1);
define("GEOMETRY_TYPE_POLYLINE", 2);
define("GEOMETRY_TYPE_POLYGON", 3);
define("GEOMETRY_TYPE_RASTER", 4);

define("AREA_SUBTYPE_NATION", 2);
define("AREA_SUBTYPE_STATE", 3);
define("AREA_SUBTYPE_COUNTY", 4);
define("AREA_SUBTYPE_POINT", 11);
define("AREA_SUBTYPE_POLYLINE", 12);
define("AREA_SUBTYPE_POLYGON", 13);

define("AREA_SUBTYPE_TRAILHEAD", 62);
define("AREA_SUBTYPE_TRAILSEGMENT", 63);

define("AREA_SUBTYPE_MICA_PLOT", 67);

//**************************************************************************************
// Class Definition
//**************************************************************************************

class LKUAreaSubtypes {

    //**********************************************************************************
    // TBL_DBTables functions
    //**********************************************************************************

    public static function GetFieldValue($Database, $FieldName, $ID, $Default = 0) {
        $Result = TBL_DBTables::GetFieldValue($Database, "LKU_AreaSubtypes", $FieldName, $ID, $Default);

        return($Result);
    }

    public static function SetFieldValue($Database, $FieldName, $ID, $Value) {
        TBL_DBTables::SetFieldValue($Database, "LKU_AreaSubtypes", $FieldName, $ID, $Value);
    }

    //******************************************************************************
    // Basic database functions
    //******************************************************************************

    public static function GetSet($Database, $AreaTypeID = null, $Survey = null, $GeometryType = null) {
        $SelectString = "SELECT * " .
                "FROM LKU_AreaSubtypes ";

        if ($AreaTypeID !== null)
            TBL_DBTables::AddWhereClause($SelectString, "AreaTypeID=$AreaTypeID");
        if ($Survey !== null)
            TBL_DBTables::AddWhereClause($SelectString, "Survey=$Survey");
        if ($GeometryType !== null)
            TBL_DBTables::AddWhereClause($SelectString, "GeometryType=$GeometryType");

        $SelectString.=" ORDER BY Name";
        $Set = $Database->Execute($SelectString);

        return($Set);
    }

    public static function GetSetFromID($dbConn, $ID) {
        $SelectString = "SELECT * " .
                "FROM LKU_AreaSubtypes " .
                "WHERE ID='" . $ID . "' " .
                "ORDER BY Name";

        $stmt = $dbConn->prepare($SelectString);
        $stmt->execute();
        $Set = $stmt->fetch();
        return($Set);
    }

    public static function Delete($Database, $AreaSubtypeID) {
        TBL_DBTables::Delete($Database, "LKU_AreaSubtypes", $AreaSubtypeID);
    }

    //******************************************************************************
    // Additional functions
    //******************************************************************************
    public static function GetNameFromID($dbConn, $ID) {
        $Set = LKUAreaSubtypes::GetSetFromID($dbConn, $ID);

        return($Set["Name"]);
    }

    public static function GetBrowseSet($Database) {
        $SelectString = "SELECT LKU_AreaSubtypes.ID, LKU_AreaSubtypes.Name " .
                "FROM LKU_AreaSubtypes INNER JOIN " .
                "LKU_AreaTypes ON LKU_AreaSubtypes.AreaTypeID = LKU_AreaTypes.ID " .
                "WHERE LKU_AreaTypes.AreaBrowse=1 " .
                "ORDER BY LKU_AreaTypes.ID, LKU_AreaSubtypes.Name";

        $Set = $Database->Execute($SelectString);

        return($Set);
    }

    public static function GetAreaTypeSet($Database, $AreaTypeID) {
        $SelectString = "SELECT * " .
                "FROM LKU_AreaSubtypes " .
                "WHERE AreaTypeID='" . $AreaTypeID . "'";

        $Set = $Database->Execute($SelectString);

        return($Set);
    }

    public static function GetSetFromTypeAndOrder($Database, $AreaTypeID, $OrderNumber) {
        $SelectString = "SELECT * " .
                "FROM LKU_AreaSubtypes " .
                "WHERE AreaTypeID=$AreaTypeID " .
                "AND OrderNumber=$OrderNumber";

        $Set = $Database->Execute($SelectString);

        return($Set);
    }

    public static function GetAreaTypeIDFromID($Database, $ID) {
        $SelectString = "SELECT AreaTypeID " .
                "FROM LKU_AreaSubtypes " .
                "WHERE ID='" . $ID . "'";

        $Set = $Database->Execute($SelectString);

        return($Set->Field("AreaTypeID"));
    }

    public static function GetIDFromName($Database, $AreaSubTypeName, $AreaSubtypeID = null) {
        $SelectString = "SELECT Name, ID FROM LKU_AreaSubtypes WHERE Name ='$AreaSubTypeName'";

        //DebugWriteln("AreaSubtypeID====$AreaSubtypeID");

        if ($AreaSubtypeID != null) {
            $SelectString = $SelectString . " AND AreaSubtypeID=$AreaSubtypeID";
        }

        //DebugWriteln("$SelectString");

        $Set = $Database->Execute($SelectString);

        $ID = $Set->Field("ID");

        return($ID);
    }

}

?>
