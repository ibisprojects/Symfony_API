<?php

namespace Classes\DBTable;

//**************************************************************************************
// FileName: TBL_SpatialLayerData.php
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

use Classes\TBLDBTables;
use Classes\DBTable\LKUCoordinateSystems;


//**************************************************************************************
// Definitions
//**************************************************************************************
//**************************************************************************************
// Class Definition
//**************************************************************************************

class TBLSpatialLayerData {

    //**********************************************************************************
    // TBL_DBTables functions
    //**********************************************************************************

    public static function GetFieldValue($Database, $FieldName, $ID, $Default = 0) {
        $Result = TBL_DBTables::GetFieldValue($Database, "TBL_SpatialLayerData", $FieldName, $ID, $Default);

        return($Result);
    }

    public static function SetFieldValue($Database, $FieldName, $ID, $Value) {
        TBL_DBTables::SetFieldValue($Database, "TBL_SpatialLayerData", $FieldName, $ID, $Value);
    }

    //******************************************************************************
    // Basic database functions
    //******************************************************************************

    public static function GetSetFromID($Database, $SpatialLayerDataID) {
        $SpatialLayerDataID = SafeInt($SpatialLayerDataID);

        $SelectString = "SELECT * " .
                "FROM TBL_SpatialLayerData " .
                "WHERE ID='" . $SpatialLayerDataID . "'";

        $Set = $Database->Execute($SelectString);

        return($Set);
    }

    public static function Insert($dbConn, $AreaID, $RefX = 0, $RefY = 0, $RefWidth = 0, $RefHeight = 0, $CoordinateSystemID = 0, $SpatialLayerGridID = 0, $GeometryString = null) {
        //
        // Either a CoordinateSystemID or a SpatialLayerGridID is required//
        // Make sure we have a SpatialLayerGridID and a CoordinateSystemID

        if ($SpatialLayerGridID == 0) { // get a SpatialLayerGridID
            $AreaSet = TBLAreas::GetSetFromID($dbConn, $AreaID);
            if (!$AreaSet)
                return;
            else {
                $AreaSubtypeID = $AreaSet["AreaSubtypeID"];
                $SpatialLayerGridID = TBLSpatialLayerGrids::GetStandardID($dbConn, $AreaSubtypeID, $CoordinateSystemID);
            }
        }
        // reproject the data

        if ($CoordinateSystemID == 0) {
            $SpatialLayerGridSet = TBLSpatialLayerGrids::GetSetFromID($dbConn, $SpatialLayerGridID);
            $CoordinateSystemID = $SpatialLayerGridSet["CoordinateSystemID"];
        }

        if ($CoordinateSystemID != 1) { // make sure the coorindate is in geographic
            //Skipped since involved with Bluespray
            //$ErrorString = LKUCoordinateSystems::CoordinateToGeographic($dbConn, $RefX, $RefY, $CoordinateSystemID);
            if ($GeometryString != null) {
                //Skipped since involved with Bluespray
                //$ErrorString = LKUCoordinateSystems::GeometryToGeographic($dbConn, $GeometryString, $CoordinateSystemID);
            }
            $CoordinateSystemID = 1;
        }
        /*
          if ($AreaID > 0) {
          $SelectString = "SELECT * " .
          "FROM TBL_SpatialLayerData " .
          "WHERE AreaID=$AreaID";
          $stmt = $dbConn->prepare($SelectString);
          $stmt->execute();
          while ($Set = $stmt->fetch()) {
          TBLSpatialLayerData::Delete($dbConn, $Set["ID"]);
          }
          } */
        // insert the SpatialLayerData record
        $ExecString = "EXEC insert_TBL_SpatialLayerData " . $AreaID;
        $stmt = $dbConn->prepare($ExecString);
        $stmt->execute();
        $SpatialLayerDataID = $dbConn->lastInsertId();

        if ($GeometryString != null) {
            //BlueSpray::GetBoundsFromGeometry($GeometryString,$RefX,$RefY,$RefWidth,$RefHeight);			
        }
        $Temp = $AreaID;
        if ($AreaID == 0)
            $Temp = "NULL";

        $UpdateString = "UPDATE TBL_SpatialLayerData " .
                "SET AreaID=$AreaID, " .
                "RefX=$RefX, " .
                "RefY=$RefY, " .
                "RefWidth=$RefWidth, " .
                "RefHeight=$RefHeight, " .
                "CoordinateSystemID=$CoordinateSystemID, " .
                "SpatialLayerGridID=$SpatialLayerGridID " .
                "WHERE ID=$SpatialLayerDataID";
        $stmt = $dbConn->prepare($UpdateString);
        $stmt->execute();
        // insert the GeometryData

        if ($GeometryString == null) { // get a geometry string for a point
            //$GeometryString = BlueSpray::GetWKTPoint($RefX, $RefY);
        } else {
            $UpdateString = "UPDATE TBL_SpatialLayerData " .
                    "SET GeometryData=geometry::STGeomFromText('" . $GeometryString . "', 0) " .
                    "WHERE ID=$SpatialLayerDataID";
            $stmt = $dbConn->prepare($UpdateString);
            $stmt->execute();
        }
        // insert the data to grid relationship (also relates to area)
        RELSpatialLayerGridToData::Insert($dbConn, $SpatialLayerGridID, $SpatialLayerDataID, 0, 0, $AreaID);
        return($SpatialLayerDataID);
    }

    public static function Delete($dbConn, $SpatialLayerDataID) {
        TBLDBTables::Delete($dbConn, "TBL_SpatialLayerData", $SpatialLayerDataID);
    }

    //******************************************************************************
    // Additional functions
    //******************************************************************************
    public static function GetSetFromAreaID($Database, $AreaID, $CoordinateSystemID = 0) {
        $SelectString = "SELECT * " .
                "FROM TBL_SpatialLayerData " .
                "WHERE AreaID='" . $AreaID . "' ";

        if ($CoordinateSystemID > 0)
            $SelectString.="AND CoordinateSystemID=$CoordinateSystemID ";

        $SelectString.="ORDER BY CoordinateSystemID";

//		DebugWriteln("SelectString=$SelectString");

        $Set = $Database->Execute($SelectString);

        return($Set);
    }

    public static function DeleteFromAreaID($Database, $AreaID) {
        //
        //	Delete all the spatial data associated with an area
        $Set = TBL_SpatialLayerData::GetSetFromAreaID($Database, $AreaID);

        while ($Set->FetchRow()) {
            TBL_SpatialLayerData::Delete($Database, $Set->Field("ID"));
        }
    }

    //******************************************************************************
    // Special Insert functions
    //******************************************************************************
}

?>
