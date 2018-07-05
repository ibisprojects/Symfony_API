<?php

namespace Classes\DBTable;

//**************************************************************************************
// FileName: TBL_SpatialLayerGroups.php
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
//**************************************************************************************
// Class Definition
//**************************************************************************************

use Classes\Utilities\SQL;
use API\Classes\Constants;

class TBLSpatialLayerGroups {

    //******************************************************************************
    // Basic database functions
    //******************************************************************************

    public static function GetSetFromID($dbConn, $ID) {
        $ID = SQL::SafeInt($ID);

        $SelectString = "SELECT * ".
                "FROM \"TBL_SpatialLayerGroups\" ".
                "WHERE \"ID\"='".$ID."'";

        $stmt = $dbConn->prepare($SelectString);
        $stmt->execute();

        $set = $stmt->fetch();

        if (!$set) {
            return false;
        }

        return($Set);
    }

    public static function GetSet($Database, $SpatialLayerTypeID = null, $OrderByField = null, $DescendingFlag = null) {
//    	DebugWriteln("OrderByField=$OrderByField");

        $SelectString = "SELECT * " .
                "FROM TBL_SpatialLayerGroups ";

        if ($SpatialLayerTypeID !== null)
            TBL_DBTables::AddWhereClause($SelectString, "SpatialLayerTypeID=$SpatialLayerTypeID");

        if ($OrderByField != null)
            TBL_DBTables::AddOrderByClause($SelectString, $OrderByField, $DescendingFlag); // query the rows in the opposite order of what the user wants

//		DebugWriteln("SelectString=$SelectString");

        $Set = $Database->Execute($SelectString);

        return($Set);
    }

    public static function GetNameFromID($dbConn, $ID) {
        $Name = "";

        $Set = TBLSpatialLayerGroups::GetSetFromID($dbConn, $ID);

        if ($Set)
            $Name = $Set["Name"];

        return($Name);
    }

    public static function GetRows($Database, $CurrentRow, $NumRows, $TotalRows, $OrderByField, $DescendingFlag, $Fields = null, $SpatialLayerTypeID = null) {
        if ($CurrentRow >= $TotalRows) {
            $LastPage = (int) ((($TotalRows + $NumRows - 1) / $NumRows) - 1); // from PageSettings

            $CurrentRow = $LastPage * $NumRows; // go to the last page
        }
        // get the query for $CurrentRow+$NumRows rows in reversed order

        $SelectString1 = "SELECT TOP " . ($TotalRows - $CurrentRow) . " ID " .
                "FROM TBL_SpatialLayerGroups ";

        if ($SpatialLayerID !== null)
            TBL_DBTables::AddWhereClause($SelectString1, "SpatialLayerTypeID=$SpatialLayerTypeID");

        TBL_DBTables::AddOrderByClause($SelectString1, $OrderByField, !$DescendingFlag); // query the rows in the opposite order of what the user wants
        // create the query that gets the top $NumRows and reverses the order to make it the way the user wants

        $SelectString = TBL_DBTables::GetSelectClause(0, $NumRows, $Fields);

        $SelectString.="FROM TBL_SpatialLayerGroups " .
                "WHERE ID IN ($SelectString1) " .
                "ORDER BY $OrderByField ";

        if ($DescendingFlag)
            $SelectString.="DESC "; // can't use order by function, finds previous order by



//		DebugWriteln("SelectString=$SelectString");

        $Set = $Database->Execute($SelectString);

        return($Set);
    }

    public static function GetTotalRows($Database, $SpatialLayerTypeID = null) {
        // get the query for $CurrentRow+$NumRows rows in reversed order

        $SelectString = "SELECT COUNT(*) " .
                "FROM TBL_SpatialLayerGroups ";

        if ($SpatialLayerTypeID !== null)
            TBL_DBTables::AddWhereClause($SelectString, "SpatialLayerTypeID=$SpatialLayerTypeID");

        $Set = $Database->Execute($SelectString);

        return($Set->Field(1));
    }

    public static function Insert($dbConn, $SpatialLayerTypeID, $Name = "Untitled") {
        $ExecString = "INSERT INTO \"TBL_SpatialLayerGroups\" (\"SpatialLayerTypeID\", \"Name\") VALUES ($SpatialLayerTypeID,'$Name')";

        $stmt = $dbConn->prepare($ExecString);
        $stmt->execute();

        return $dbConn->lastInsertId('"TBL_SpatialLayerGroups_ID_seq"');
    }

    public static function Update($dbConn, $ID, $Name, $RefX, $RefY, $RefWidth, $RefHeight, $StartDate = null, $EndDate = null, $FolderPath = null) {
        $UpdateString = "UPDATE \"TBL_SpatialLayerGroups\" ".
                "SET \"Name\"='$Name' ";

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
        TBL_DBTables::Delete($Database, "TBL_SpatialLayerGroups", $ID);
    }

    //******************************************************************************
    // Standard SpatiaLayer functions
    //******************************************************************************
    public static function UpdateBounds($Database, $SpatialLayerGroupID) {
        $MinX = null;
        $MaxX = null;
        $MinY = null;
        $MaxY = null;

        // get the layers that are in geographic

        $SelectString = "SELECT MIN(RefX),MAX(RefY),MAX(RefX+RefWidth),MIN(RefY+RefHeight) " .
                "FROM TBL_SpatialLayers " .
                "WHERE SpatialLayerGroupID=$SpatialLayerGroupID " .
                "AND CoordinateSystemID=1";

        $Set = $Database->Execute($SelectString);

        if ($Set->FetchRow()) {
            $MinX = $Set->Field(1);
            $MaxX = $Set->Field(3);
            $MinY = $Set->Field(4);
            $MaxY = $Set->Field(2);
        }

        // get the layers that are in UTM

        $SelectString = "SELECT DISTINCT CoordinateSystemID " .
                "FROM TBL_SpatialLayers " .
                "WHERE SpatialLayerGroupID=$SpatialLayerGroupID " .
                "AND CoordinateSystemID<>1";

        $CoordinateSystemSet = $Database->Execute($SelectString);

        while ($CoordinateSystemSet->FetchRow()) {
            $CoordinateSystemID = $CoordinateSystemSet->Field(1);
            //			DebugWriteln("CoordinateSystemID=$CoordinateSystemID");

            $SelectString = "SELECT MIN(RefX),MAX(RefY),MAX(RefX+RefWidth),MIN(RefY+RefHeight) " .
                    "FROM TBL_SpatialLayers " .
                    "WHERE SpatialLayerGroupID=$SpatialLayerGroupID " .
                    "AND CoordinateSystemID=$CoordinateSystemID";

            $Set = $Database->Execute($SelectString);

            $X1 = $Set->Field(1);
            $Y1 = $Set->Field(2);
            $X2 = $Set->Field(3);
            $Y2 = $Set->Field(4);
            /* 	    	$Item=array(
              $Set->Field(1),$Set->Field(4),  // MinX, MinY
              $Set->Field(1),$Set->Field(2),  // MinX, MaxY
              $Set->Field(3),$Set->Field(4),  // MaxX, MinY
              $Set->Field(3),$Set->Field(2),  // MaxX, MaxY
              );
             */
            $SourceEPSG = LKU_CoordinateSystems::GetEPSGFromID($Database, $CoordinateSystemID);
//			DebugWriteln("SourceEPSG=$SourceEPSG");

            Projector::ProjectGeometryFromEPSGToGeographic($SourceEPSG, $X1, $Y1);

            Projector::ProjectGeometryFromEPSGToGeographic($SourceEPSG, $X2, $Y2);

//	    	DumpArray($Item);

            if (($MinX === null) || ($X1 < $MinX))
                $MinX = $X1;
            if (($MaxX === null) || ($X1 > $MaxX))
                $MaxX = $X1;

            if (($MinY === null) || ($Y1 < $MinY))
                $MinY = $Y1;
            if (($MaxY === null) || ($Y1 > $MaxY))
                $MaxY = $Y1;

            if (($MinX === null) || ($X2 < $MinX))
                $MinX = $X2;
            if (($MaxX === null) || ($X2 > $MaxX))
                $MaxX = $X2;

            if (($MinY === null) || ($Y2 < $MinY))
                $MinY = $Y2;
            if (($MaxY === null) || ($Y2 > $MaxY))
                $MaxY = $Y2;

//	    		DebugWriteln("MinX=$MinX, MaxX=$MaxX, MinY=$MinY, MaxY=$MaxY");
        }

        // update the database with the new values

        if ($MinX === null) { // if any values were not set, set everything to NULL
            $UpdateString = "UPDATE TBL_SpatialLayerGroups " .
                    "SET RefX=NULL," .
                    "RefY=NULL," .
                    "RefWidth=NULL," .
                    "RefHeight=NULL " .
                    "WHERE ID=$SpatialLayerGroupID";
        } else { // set the new bounds
            $UpdateString = "UPDATE TBL_SpatialLayerGroups " .
                    "SET RefX=" . $MinX . "," .
                    "RefY=" . $MaxY . "," .
                    "RefWidth=" . ($MaxX - $MinX) . "," .
                    "RefHeight=" . ($MinY - $MaxY) . " " .
                    "WHERE ID=$SpatialLayerGroupID";
        }

//    	DebugWriteln("UpdateString=$UpdateString");
        $Database->Execute($UpdateString);
    }

    public static function GetStandardID($dbConn, $AreaSubtypeID) {
        //
        //	Returns an ID to a spatial layer grid with PersonID=NULL (standard).
        //	Creates the grid and any required parents if none is found.//

        $SelectString = "SELECT \"TBL_SpatialLayerGroups\".\"ID\" ".
                "FROM \"TBL_SpatialLayerGroups\" ".
                "INNER JOIN \"TBL_SpatialLayerTypes\" ".
                "ON \"TBL_SpatialLayerTypes\".\"ID\"=\"TBL_SpatialLayerGroups\".\"SpatialLayerTypeID\" ".
                "WHERE \"AreaSubtypeID\"=$AreaSubtypeID ".
                "AND \"PersonID\" IS NULL";

        $stmt = $dbConn->prepare($SelectString);
        $stmt->execute();
        $SpatialLayerGroupSet = $stmt->fetch();

        if ($SpatialLayerGroupSet) { // get it from the set
            $SpatialLayerGroupID = $SpatialLayerGroupSet["ID"];
        } else { // find a type and insert a new group
            $SpatialLayerTypeID = TBLSpatialLayerTypes::GetStandardID($dbConn, $AreaSubtypeID);
            $Name = TBLSpatialLayerTypes::GetNameFromID($dbConn, $SpatialLayerTypeID);
            $SpatialLayerGroupID = TBLSpatialLayerGroups::Insert($dbConn, $SpatialLayerTypeID);
            TBLSpatialLayerGroups::Update($dbConn, $SpatialLayerGroupID, $Name, -180, 90, 360, -180);
        }

        return($SpatialLayerGroupID);
    }

    //******************************************************************************
    // Additional functions
    //******************************************************************************
    /*    public static function GetSetFromSpatialLayerTypeID($Database,$SpatialLayerTypeID)
      {
      $SpatialLayerTypeID=SafeInt($SpatialLayerTypeID);

      $SelectString="SELECT * ".
      "FROM TBL_SpatialLayerGroups ".
      "WHERE SpatialLayerTypeID='$SpatialLayerTypeID'";

      $Set=$Database->Execute($SelectString);

      return($Set);

      }
     */ private static function AddMapFile($Database, $UserID, $InsertLogID, $SpatialLayerGroupID, $Name, $DestinPath, $CoordinateSystemID) {
        //
        // Note: this function does not work and used to access the DLL to project rasters and
        // nneds to be changed to use the Java BlueSpray to project rasters (and to use a file
        // format other than ECW!).
        //
        // This public static function will add a new raster file for the map into an existing SpatialLayerGroup as required
        //	to display the raster on a map.  The file
        //	will be projected into the specified CoordinateSystemID.  This also includes:
        //	- Adding a new SpatialLayer if there is not one that matches the CoordinateSystemID
        //
	// You will need to call TBL_SpatialLayerGroups::UpdateBounds() and
        //	TBL_SpatialLayerTypes::UpdateBounds() after calling this function
        // (obsolete)//

        $FilePathECW = null;
        $FileName = $Name = GetFileNameFromFilePath($DestinPath);

        $Set = TBL_SpatialLayerGroups::GetSetFromID($Database, $SpatialLayerGroupID);

        $SpatialLayerTypeID = $Set->Field("SpatialLayerTypeID");

        //*************************************
        // insert the new layer so we have one in the right projection

        $SpatialLayerID = TBL_SpatialLayers::Insert($Database, $SpatialLayerGroupID);

        $LayerPath = "D:/Inetpub/UserUploads/$UserID/GeoRasters/" .
                "$SpatialLayerTypeID/$SpatialLayerGroupID/$SpatialLayerID/";

        $UpdateString = "UPDATE TBL_SpatialLayers " .
                "SET FolderPath='$LayerPath'," .
                "GeometryType=" . Constants::GEOMETRY_TYPE_RASTER . ", " .
                "CoordinateSystemID=" . $CoordinateSystemID . " " .
                "WHERE ID=$SpatialLayerID";

        //	DebugWriteln("UpdateString=$UpdateString");
        $Database->Execute($UpdateString);

        //*************************************
        // insert the new grid so we have an ID for the file path

        $SpatialLayerGridID = TBL_SpatialLayerGrids::Insert($Database, $SpatialLayerID);

        // create the folder and file using the new SpatialLayerGridID

        $FolderPathECW = "D:/Inetpub/UserUploads/" . GetUserID() . "/GeoRasters/" .
                "$SpatialLayerTypeID/$SpatialLayerGroupID/$SpatialLayerID/$SpatialLayerGridID";

        // update the grid record

        $UpdateString = "UPDATE TBL_SpatialLayerGrids " .
                "SET Name='$Name'," .
                "FolderPath='$FolderPathECW'," .
                "AnalysisFlag='0'," .
                "MappingFlag='1' " .
                "WHERE ID=$SpatialLayerGridID";

        //	DebugWriteln("UpdateString=$UpdateString");

        $Database->Execute($UpdateString);

        // create the geographic image

        $EPSGNumber = LKU_CoordinateSystems::GetEPSGFromID($Database, $CoordinateSystemID);

        MakeSurePathExists($FolderPathECW);

        $FileNameECW = GetFileNameWithoutExtension($FileName);

        $Raster = new STRaster();
        $Raster->Load($DestinPath);

        $Raster->EqualizeAndConvertTo256($Raster->GetPixelType());

        $Raster->Convert(STPIXEL_GRAY, STBAND_DEPTH_8);

        Writeln("NOT SUPPORTED: Needs to be rolled to Java BlueSpray");
//		$RasterProjector=new STRasterProjector();
//		$RasterProjector->Project($Database,$Raster,$EPSGNumber);

        $FilePathECW = $FolderPathECW . "/" . $FileNameECW . ".ecw";
        //		DebugWriteln("FilePathECW=$FilePathECW");

        $Raster->Save($FilePathECW);

        //*************************************
        // add the file record

        $RefX = $Raster->GetRefX();
        $RefY = $Raster->GetRefY();
        $RefWidth = $Raster->GetRefWidth();
        $RefHeight = $Raster->GetRefHeight();

        $SpatialLayerFileID = TBL_SpatialLayerFiles::Insert($Database, $SpatialLayerGridID, $FilePathECW, $RefX, $RefY, $RefWidth, $RefHeight);

        $UpdateString = "UPDATE TBL_SpatialLayerFiles " .
                "SET InsertLogID=$InsertLogID " .
                "WHERE ID=$SpatialLayerFileID";

        $Database->Execute($UpdateString);

        // update the other spatial layer objects to include any bounary changes

        TBL_SpatialLayerGrids::UpdateBoundsFromFiles($Database, $SpatialLayerGridID);
        TBL_SpatialLayerGrids::UpdateGridToFiles($Database, $SpatialLayerGridID);

        TBL_SpatialLayers::UpdateBounds($Database, $SpatialLayerID);

        return($FilePathECW);
    }

    public static function AddMapFiles($Database, $UserID, $InsertLogID, $SpatialLayerGroupID, $Name, $DestinPath) {
        //
        // called by GeoRaster_Update.php - not currently working because of GoogleMaps and BlueSpray Java roll
        //
        // Adds a raster file to an existing group.  Projects the raster into the various projectsion
        // (this is obsolete with GoogleMaps)
        // project the raster to Geographic

        $GeographicRasterFilePath = TBL_SpatialLayerGroups::AddMapFile($Database, $UserID, $InsertLogID, $SpatialLayerGroupID, $Name, $DestinPath, Constants::COORDINATE_SYSTEM_WGS84_GEOGRAPHIC);

        // load the new raster to project it into the UTM zones it overlaps with

        $Raster = new STRaster();
        $Raster->Load($GeographicRasterFilePath);

        // add the raster to the UTM zones it appears in

        if ($Raster->GetRefPixelWidth() < 0.01) { // make sure we have a raster with pixels less than 1 kilometer
            $RefX = $Raster->GetRefX();
            $RefY = $Raster->GetRefY();
            $RefWidth = $Raster->GetRefWidth();
            $RefHeight = $Raster->GetRefHeight();

            /* 			$StartZone=GetUTMZoneFromLonLat($RefX,$RefY); // just use the last x,y
              $EndZone=GetUTMZoneFromLonLat($RefX+$RefWidth,$RefY); // just use the last x,y
              //			TBL_SpatialLayerData::GetZonesForArea($RefX,$RefY,$RefWidth,$RefHeight,&$StartZone,&$EndZone);

              //			DebugWriteln("StartZone=$StartZone, EndZone=$EndZone");

              // add rasters for each zone

              for ($i=$StartZone;$i<=$EndZone;$i++)
              {
              $CoordinateSystemID=LKU_CoordinateSystems::GetIDFromUTMZone($Database,$i);
              //				DebugWriteln("New CoordinateSystemID=$CoordinateSystemID");

              TBL_SpatialLayerGroups::AddMapFile($Database,$UserID,$InsertLogID,$SpatialLayerGroupID,$Name,
              $DestinPath,$CoordinateSystemID);
              }
             */
        }

        TBL_SpatialLayerGroups::UpdateBounds($Database, $SpatialLayerGroupID);

        $Set = TBL_SpatialLayerGroups::GetSetFromID($Database, $SpatialLayerGroupID);

        TBL_SpatialLayerTypes::UpdateBounds($Database, $Set->Field("SpatialLayerTypeID"));
    }

}

?>
