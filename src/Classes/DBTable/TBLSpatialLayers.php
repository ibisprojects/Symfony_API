<?php

namespace Classes\DBTable;

//**************************************************************************************
// FileName: TBL_SpatialLayers.php
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
// Definitions
//**************************************************************************************
//**************************************************************************************
// Class Definition
//**************************************************************************************

use Classes\Utilities\SQL;

class TBLSpatialLayers {

    //**********************************************************************************
    // TBL_DBTables functions
    //**********************************************************************************
    public static function GetFieldValue($Database, $FieldName, $ID, $Default = 0) {
        $Result = TBL_DBTables::GetFieldValue($Database, "TBL_SpatialLayers", $FieldName, $ID, $Default);

        return($Result);
    }

    public static function SetFieldValue($Database, $FieldName, $ID, $Value) {
        TBL_DBTables::SetFieldValue($Database, "TBL_SpatialLayers", $FieldName, $ID, $Value);
    }

    //******************************************************************************
    // Basic database functions
    //******************************************************************************

    public static function GetSetFromID($dbConn, $ID) {
        $ID = SQL::SafeInt($ID);

        $SelectString = "SELECT * ".
                "FROM \"TBL_SpatialLayers\" ".
                "WHERE \"ID\"='".$ID."'";

        $stmt = $dbConn->prepare($SelectString);
        $stmt->execute();

        $Set = $stmt->fetch();

        if (!$Set) {
            return false;
        }

        return($Set);
    }

    public static function GetSet($Database, $SpatialLayerGroupID = null, $CoordinateSystemID = null) {
        $SelectString = "SELECT * " .
                "FROM TBL_SpatialLayers ";

//		$WhereAdded=false;
        if ($SpatialLayerGroupID !== null)
            TBL_DBTables::AddWhereClause($SelectString, "SpatialLayerGroupID=$SpatialLayerGroupID");

        if ($CoordinateSystemID !== null)
            TBL_DBTables::AddWhereClause($SelectString, "CoordinateSystemID=$CoordinateSystemID");

//			DebugWriteln("SelectString=".$SelectString);

        $Set = $Database->Execute($SelectString);


        return($Set);
    }

    public static function GetNameFromID($dbConn, $ID) {
        $Name = "";

        $Set = TBLSpatialLayers::GetSetFromID($dbConn, $ID);

        if ($Set)
            $Name = $Set["Name"];

        return($Name);
    }

    public static function Insert($dbConn, $SpatialLayerGroupID) {
        $ExecString="INSERT INTO \"TBL_SpatialLayers\" (\"SpatialLayerGroupID\") VALUES ($SpatialLayerGroupID)";

        $stmt = $dbConn->prepare($ExecString);
        $stmt->execute();

        return $dbConn->lastInsertId('TBL_SpatialLayers_ID_seq');
    }

    public static function Update($dbConn, $ID, $Name, $GeometryType, $CoordinateSystemID, $RefX = null, $RefY = null, $RefWidth = null, $RefHeight = null, $FolderPath = null, $StartDate = null, $EndDate = null) {
        $UpdateString = "UPDATE \"TBL_SpatialLayers\" ".
                "SET \"Name\"='$Name', ".
                "\"GeometryType\"=$GeometryType, ".
                "\"CoordinateSystemID\"=$CoordinateSystemID ";

        if ($RefX !== null)
            $UpdateString.=",\"RefX\"='$RefX' ";
        if ($RefY !== null)
            $UpdateString.=",\"RefY\"='$RefY' ";
        if ($RefWidth !== null)
            $UpdateString.=",\"RefWidth\"='$RefWidth' ";
        if ($RefHeight !== null)
            $UpdateString.=",\"RefHeight\"='$RefHeight' ";

        if ($StartDate !== null)
            $UpdateString.=",\"StartDate\"='".$StartDate->GetSQLString()."' ";
        if ($EndDate !== null)
            $UpdateString.=",\"EndDate\"='".$EndDate->GetSQLString()."' ";
        if ($FolderPath !== null)
            $UpdateString.=",\"FolderPath\"='$FolderPath' ";

        $UpdateString.="WHERE \"ID\"=$ID";

        $stmt = $dbConn->prepare($UpdateString);
        $stmt->execute();

        return($ID);
    }

    public static function Delete($Database, $ID) {
        TBL_DBTables::Delete($Database, "TBL_SpatialLayers", $ID);
    }

    //******************************************************************************
    // Additional functions
    //******************************************************************************
    /*    public static function GetSetFromSpatialLayerGroupID($Database,$SpatialLayerGroupID)
      {
      $SpatialLayerGroupID=SafeInt($SpatialLayerGroupID);

      $SelectString="SELECT * ".
      "FROM TBL_SpatialLayers ".
      "WHERE SpatialLayerGroupID='$SpatialLayerGroupID'";

      $Set=$Database->Execute($SelectString);

      return($Set);

      }
     */    //******************************************************************************
    // Standard SpatialLayer functions
    //******************************************************************************
    public static function UpdateBounds($Database, $SpatialLayerID) {
        $SelectString = "SELECT MIN(RefX),MAX(RefY),MAX(RefX+RefColumnWidth*NumColumns)," .
                "MIN(RefY+RefRowHeight*NumRows) " .
                "FROM TBL_SpatialLayerGrids " .
                "WHERE SpatialLayerID=$SpatialLayerID";

        $Set = $Database->Execute($SelectString);

        $UpdateString = "UPDATE TBL_SpatialLayers " .
                "SET RefX=" . $Set->Field(1) . "," .
                "RefY=" . $Set->Field(2) . "," .
                "RefWidth=" . ($Set->Field(3) - $Set->Field(1)) . "," .
                "RefHeight=" . ($Set->Field(4) - $Set->Field(2)) . " " .
                "WHERE ID=$SpatialLayerID";

//	   	DebugWriteln("UpdateString=$UpdateString");
        $Database->Execute($UpdateString);
    }

    public static function GetStandardID($dbConn, $AreaSubtypeID, $CoordinateSystemID) {
    //
    //	Returns an ID to a spatial layer grid with PersonID=NULL (standard).
    //	Creates the grid and any required parents if none is found.//

        // try to find an existing grid

        $SelectString = "SELECT \"TBL_SpatialLayers\".\"ID\" ".
                "FROM \"TBL_SpatialLayers\" ".
                "INNER JOIN \"TBL_SpatialLayerGroups\" ".
                "ON \"TBL_SpatialLayerGroups\".\"ID\"=\"TBL_SpatialLayers\".\"SpatialLayerGroupID\" ".
                "INNER JOIN \"TBL_SpatialLayerTypes\" ".
                "ON \"TBL_SpatialLayerTypes\".\"ID\"=\"TBL_SpatialLayerGroups\".\"SpatialLayerTypeID\" ".
                "WHERE \"AreaSubtypeID\"=$AreaSubtypeID ".
                "AND \"CoordinateSystemID\"=$CoordinateSystemID ".
                "AND \"PersonID\" IS NULL";

        $stmt = $dbConn->prepare($SelectString);
        $stmt->execute();
        $SpatialLayerSet = $stmt->fetch();

        if ($SpatialLayerSet) { // get it from the set
            $SpatialLayerID = $SpatialLayerSet["ID"];
        } else { // find a layer and insert a new grid
            $SpatialLayerGroupID = TBLSpatialLayerGroups::GetStandardID($dbConn, $AreaSubtypeID);
            $Name = TBLSpatialLayerGroups::GetNameFromID($dbConn, $SpatialLayerGroupID);
            $Name .= " ".LKUCoordinateSystems::GetNameForID($dbConn, $CoordinateSystemID);
            $SpatialLayerID = TBLSpatialLayers::Insert($dbConn, $SpatialLayerGroupID);
            TBLSpatialLayers::Update($dbConn, $SpatialLayerID, $Name, 0, $CoordinateSystemID);
        }

        return($SpatialLayerID);
    }
}

?>
