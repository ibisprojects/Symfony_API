<?php

namespace Classes\DBTable;

//**************************************************************************************
// FileName: LKU_CoordinateSystems.php
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

use API\Classes\Constants;

define("COORDINATE_SYSTEM_WGS84_UTM_1_North", 136); // add offset to get zones 1 through 60
define("COORDINATE_SYSTEM_WGS84_UTM_1_South", 196); // add offset to get zones 1 through 60

//

//**************************************************************************************
// Class Definition
//**************************************************************************************

class LKUCoordinateSystems {

    //**********************************************************************************
    // Basic database functions
    //**********************************************************************************

    public static function GetSet($dbConn) {
        $SelectString = "SELECT * " .
                "FROM LKU_CoordinateSystems ";

//		DebugWriteln("SelectString=$SelectString");

        $Set = $dbConn->Execute($SelectString);

        return($Set);
    }

    public static function Delete($dbConn, $CoordinateSystemID) {
        TBL_DBTables::Delete($dbConn, "LKU_CoordinateSystems", $CoordinateSystemID);
    }

    public static function GetSetFromID($dbConn, $ID) {
        $SelectString = "SELECT * ".
                "FROM \"LKU_CoordinateSystems\" ".
                "WHERE \"ID\"='$ID'";

        $stmt = $dbConn->prepare($SelectString);
        $stmt->execute();

        $set = $stmt->fetch();

        if (!$set)
            return false;

        return($set);
    }

    public static function GetNameForID($dbConn, $ID) {
        $Name = "Untitled";

        $Set = LKUCoordinateSystems::GetSetFromID($dbConn, $ID);

        if (isset($Set["Name"]))
            $Name = $Set["Name"];

        return($Name);
    }

    //**********************************************************************************
    // Additional database functions
    //**********************************************************************************
    public static function GetEPSGFromID($dbConn, $ID) {
        $EPSGNumber = 0;

        $Set = LKU_CoordinateSystems::GetSetFromID($dbConn, $ID);

        if ($Set->FetchRow())
            $EPSGNumber = $Set->Field("EPSG");

        return($EPSGNumber);
    }

    public static function GetIDFromEPSG($dbConn, $EPSGNumber) {
        $ID = 0;

        $SelectString = "SELECT ID " .
                "FROM LKU_CoordinateSystems " .
                "WHERE EPSG=$EPSGNumber";

        $Set = $dbConn->Execute($SelectString);

        if ($Set->FetchRow())
            $ID = $Set->Field("ID");

        return($ID);
    }

    //**********************************************************************************
    // Basic database functions
    //**********************************************************************************

    public static function GetIDFromProjection($dbConn, $Projection, $UTMZone = null, $South = false, $Datum = STDATUM_WGS_84) {
        $ID = 0;

//		DebugWriteln("Projection=$Projection");
//		DebugWriteln("UTMZone=$UTMZone");
//		DebugWriteln("South=$South");
//		DebugWriteln("Datum=$Datum");

        switch ($Projection) {
            case STPROJECTION_GEOGRAPHIC:
                $ID = Constants::COORDINATE_SYSTEM_WGS84_GEOGRAPHIC;
                break;
            case STPROJECTION_UTM:
                $ID = LKU_CoordinateSystems::GetIDFromUTMZone($dbConn, $UTMZone, $South);
                break;
        }
        return($ID);
    }

    public static function GetIDFromUTMZone($dbConn, $UTMZone, $South = false, $Datum = STDATUM_WGS_84) {
        $ID = 0;

        if ($South == false) {
            $ID = $UTMZone + COORDINATE_SYSTEM_WGS84_UTM_1_North - 1;
        } else {
            $ID = $UTMZone + COORDINATE_SYSTEM_WGS84_UTM_1_South - 1;
        }
        return($ID);
    }

    public static function GetProjectionFromID($dbConn, $ID) {
        $Projection = 0;

        if ($ID == Constants::COORDINATE_SYSTEM_WGS84_GEOGRAPHIC) {
            $Projection = STPROJECTION_GEOGRAPHIC;
        } else {
            $Projection = STPROJECTION_UTM;
        }
        return($Projection);
    }

    public static function GetUTMZoneFromID($dbConn, $ID) {
        $UTMZone = 0;

        if ($ID < COORDINATE_SYSTEM_WGS84_UTM_1_South) {
            $UTMZone = $ID - COORDINATE_SYSTEM_WGS84_UTM_1_North + 1; // north zones
        } else {
//			DebugWriteln("ID=$ID");
            $UTMZone = $ID - COORDINATE_SYSTEM_WGS84_UTM_1_South + 1; // south zones
        }
        return($UTMZone);
    }

    public static function GetSouthFromID($dbConn, $ID) { // returns true if the coordinate system is only valid for southern hemisphere
        $South = false;

        if ($ID > COORDINATE_SYSTEM_WGS84_UTM_1_South)
            $South = true;

        return($South);
    }

    public static function GetEPSGNumber($dbConn, $ID) {
        $Set = LKU_CoordinateSystems::GetSetFromID($dbConn, $ID);

        return($Set->IntegerField("EPSG"));
    }

    //**********************************************************************************
    // Projection Functions
    //**********************************************************************************
    //**************************************************************************************
    // Bounds
    //**************************************************************************************
    public static function ProjectBoundsFromGeographic($dbConn, &$RefX, &$RefY, &$RefWidth, &$RefHeight, $DestinCoordinateSystemID) {
        $LLX = $RefX + $RefWidth;
        $LLY = $RefY + $RefHeight;

        $ErrorString = LKU_CoordinateSystems::CoordinateFromGeographic($dbConn, $RefX, $RefY, $DestinCoordinateSystemID);
        if ($ErrorString == null)
            $ErrorString = LKU_CoordinateSystems::CoordinateFromGeographic($dbConn, $LLX, $LLY, $DestinCoordinateSystemID);

        $RefWidth = $LLX - $RefX;
        $RefHeight = $LLY - $RefY;
        return($ErrorString);
    }

    public static function ProjectBoundsFromToGeographic($dbConn, &$RefX, &$RefY, &$RefWidth, &$RefHeight, $SourceCoordinateSystemID) {
        $LLX = $RefX + $RefWidth;
        $LLY = $RefY + $RefHeight;

        $ErrorString = LKU_CoordinateSystems::CoordinateToGeographic($dbConn, $RefX, $RefY, $SourceCoordinateSystemID);
        if ($ErrorString == null)
            $ErrorString = LKU_CoordinateSystems::CoordinateToGeographic($dbConn, $LLX, $LLY, $SourceCoordinateSystemID);

        $RefWidth = $LLX - $RefX;
        $RefHeight = $LLY - $RefY;
        return($ErrorString);
    }

    //**************************************************************************************
    // Single point
    //**************************************************************************************
    public static function CoordinateFromGeographic($dbConn, &$X, &$Y, $DestinCoordinateSystemID, $SourceDatum = 0) {
        $ErrorString = null;

//		$Coordinates=array($X,$Y);
        $Set = LKU_CoordinateSystems::GetSetFromID($dbConn, $DestinCoordinateSystemID);

        if ($Set->FetchRow()) {
            $EPSG = $Set->Field("EPSG");

            Projector::ProjectPointFromGeographicToEPSG($X, $Y, $EPSG);
        } else {
            $ErrorString = "Unsupported CoordinateSystemID";
        }
        return($ErrorString);
    }

    public static function GeometryToGeographic($dbConn, &$GeometryString, $SourceCoordinateSystemID) {
        $ErrorString = null;

        $Set = LKU_CoordinateSystems::GetSetFromID($dbConn, $SourceCoordinateSystemID);

        if ($Set->FetchRow()) {
            $EPSG = $Set->Field("EPSG");

            Projector::ProjectGeometryFromEPSGToGeographic($EPSG, $GeometryString);
        } else {
            $ErrorString = "Unsupported CoordinateSystemID";
        }

        return($ErrorString);
    }

    //**************************************************************************************
    // Array of points
    //**************************************************************************************
}

?>
