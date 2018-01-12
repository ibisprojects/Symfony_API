<?php

namespace Classes\DBTable;

//**************************************************************************************
// FileName: TBL_SpatialLayerTypeType.php
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
use Classes\Utilities\FileUtil;
use Classes\TBLDBTables;

class TBLSpatialLayerTypes {

    //******************************************************************************
    // Basic database functions
    //******************************************************************************

    public static function GetSetFromID($dbConn, $ID) {
        $ID = SQL::SafeInt($ID);

        $SelectString = "SELECT * ".
                "FROM \"TBL_SpatialLayerTypes\" ".
                "WHERE \"ID\"='".$ID."'";

        $stmt = $dbConn->prepare($SelectString);
        $stmt->execute();

        $SpatialLayerType = $stmt->fetch();

        if (!$SpatialLayerType) {
            return false;
        }

        return($SpatialLayerType);
    }

    public static function GetNameFromID($dbConn, $ID) {
        $Name = "";

        $Set = TBLSpatialLayerTypes::GetSetFromID($dbConn, $ID);

        if ($Set)
            $Name = $Set["Name"];

        return($Name);
    }

    public static function GetSet($Database, $AnalysisFlag = NOT_SPECIFIED, $PersonID = NOT_SPECIFIED, $OrderByField = NOT_SPECIFIED, $DescendingFlag = NOT_SPECIFIED, $MappingFlag = NOT_SPECIFIED) {
        $SelectString = "SELECT * " .
                "FROM TBL_SpatialLayerTypes ";

        if ($AnalysisFlag !== NOT_SPECIFIED)
            TBL_DBTables::AddWhereClause($SelectString, "AnalysisFlag=$AnalysisFlag");
        if ($PersonID !== NOT_SPECIFIED) {
            if ($PersonID !== null)
                TBL_DBTables::AddWhereClause($SelectString, "PersonID=$PersonID");
            else
                TBL_DBTables::AddWhereClause($SelectString, "PersonID IS NULL");
        }
        if ($MappingFlag !== NOT_SPECIFIED)
            TBL_DBTables::AddWhereClause($SelectString, "MappingFlag=$MappingFlag");

        if ($OrderByField != NOT_SPECIFIED)
            TBL_DBTables::AddOrderByClause($SelectString, $OrderByField, $DescendingFlag); // query the rows in the opposite order of what the user wants



//		DebugWriteln("SelectString=$SelectString");

        $Set = $Database->Execute($SelectString);

        return($Set);
    }

    public static function GetRows($Database, $CurrentRow, $NumRows, $TotalRows, $OrderByField, $DescendingFlag, $Fields = null) {
        if ($CurrentRow >= $TotalRows) {
            $LastPage = (int) ((($TotalRows + $NumRows - 1) / $NumRows) - 1); // from PageSettings

            $CurrentRow = $LastPage * $NumRows; // go to the last page
        }
        // get the query for $CurrentRow+$NumRows rows in reversed order

        $SelectString1 = "SELECT TOP " . ($TotalRows - $CurrentRow) . " ID " .
                "FROM TBL_SpatialLayerTypes ";

        TBL_DBTables::AddOrderByClause($SelectString1, $OrderByField, !$DescendingFlag); // query the rows in the opposite order of what the user wants
        // create the query that gets the top $NumRows and reverses the order to make it the way the user wants

        $SelectString = TBL_DBTables::GetSelectClause(0, $NumRows, $Fields);

        $SelectString.="FROM TBL_SpatialLayerTypes " .
                "WHERE ID IN ($SelectString1) " .
                "ORDER BY $OrderByField ";

        if ($DescendingFlag)
            $SelectString.="DESC "; // can't use order by function, finds previous order by



//		DebugWriteln("SelectString=$SelectString");

        $Set = $Database->Execute($SelectString);

        return($Set);
    }

    public static function GetTotalRows($Database) {
        // get the query for $CurrentRow+$NumRows rows in reversed order

        $SelectString = "SELECT COUNT(*) " .
                "FROM TBL_SpatialLayerTypes ";

//		DebugWriteln("SelectString=$SelectString");

        $Set = $Database->Execute($SelectString);

        return($Set->Field(1));
    }

    public static function Insert($dbConn, $Name) {
        $ExecString = "INSERT INTO \"TBL_SpatialLayerTypes\" (\"Name\", \"AreaSubtypeID\") VALUES ('$Name', 0)";

        $stmt = $dbConn->prepare($ExecString);
        $stmt->execute();

        return $dbConn->lastInsertId('TBL_SpatialLayerTypes_ID_seq');
    }

    public static function Update($dbConn, $ID, $Name, $RefX, $RefY, $RefWidth, $RefHeight, $AreaSubtypeID = null, $PersonID = NOT_SPECIFIED, $StartDate = null, $EndDate = null, $AnalysisFlag = null, $MappingFlag = null, $Description = null) {
        $UpdateString = "UPDATE \"TBL_SpatialLayerTypes\" ".
                "SET \"Name\"='$Name', ".
                "\"RefX\"=$RefX, ".
                "\"RefY\"=$RefY, ".
                "\"RefWidth\"=$RefWidth, ".
                "\"RefHeight\"=$RefHeight ";

        if ($PersonID !== NOT_SPECIFIED)
            TBLDBTables::AddStringUpdate($UpdateString, '"PersonID"', $PersonID);
        if ($AreaSubtypeID !== null)
            $UpdateString.=",\"AreaSubtypeID\"=$AreaSubtypeID ";
        if ($StartDate !== null)
            $UpdateString.=",\"StartDate\"='".$StartDate->GetSQLString()."' ";
        if ($EndDate !== null)
            $UpdateString.=",\"EndDate\"='".$EndDate->GetSQLString()."' ";
        if ($AnalysisFlag !== null)
            $UpdateString.=",\"AnalysisFlag\"='$AnalysisFlag' ";
        if ($MappingFlag !== null)
            $UpdateString.=",\"MappingFlag\"='$MappingFlag' ";
        if ($Description !== null)
            $UpdateString.=",\"Description\"='$Description' ";

        $UpdateString.="WHERE \"ID\"=$ID";

        $stmt = $dbConn->prepare($UpdateString);
        $stmt->execute();

        return($ID);
    }

    public static function Delete($dbConn, $SpatialLayerTypeID) {
        $SpatialLayerTypeID = SQL::SafeInt($SpatialLayerTypeID);

        // get the sets

        $SpatialLayerTypeSet = TBLSpatialLayerTypes::GetSetFromID($dbConn, $SpatialLayerTypeID);

        if (!$SpatialLayerTypeSet)
            return;

        // delete the type folder

        $UserID = $SpatialLayerTypeSet["PersonID"];

        if ($UserID > 0) {
            $DestinPath = "/var/www/citsci/inetpub/UserUploads/$UserID/GeoRasters/$SpatialLayerTypeID";

            FileUtil::DeleteFolder($DestinPath);
        }

        // needs DatabaseRepair, User, Admin
        // Checked by:User
        TBLDBTables::Delete($dbConn, "TBL_SpatialLayerTypes", $SpatialLayerTypeID);
    }

    //******************************************************************************
    // Standard entry functions
    //******************************************************************************
    public static function GetStandardID($dbConn, $AreaSubtypeID) {
        $SelectString = "SELECT \"TBL_SpatialLayerTypes\".\"ID\" ".
                "FROM \"TBL_SpatialLayerTypes\" ".
                "WHERE \"AreaSubtypeID\"=$AreaSubtypeID ".
                "AND \"PersonID\" IS NULL";

        $stmt = $dbConn->prepare($SelectString);
        $stmt->execute();
        $SpatialLayerTypeSet = $stmt->fetch();

        if ($SpatialLayerTypeSet) { // get it from the set
            $SpatialLayerTypeID = $SpatialLayerTypeSet["ID"];
        } else { // find a layer and insert a new grid
            $Name = LKUAreaSubtypes::GetNameFromID($dbConn, $AreaSubtypeID);
            $SpatialLayerTypeID = TBLSpatialLayerTypes::Insert($dbConn, "Untitled");
            TBLSpatialLayerTypes::Update($dbConn, $SpatialLayerTypeID, $Name, -180, 90, 360, -180, $AreaSubtypeID);
        }

        return($SpatialLayerTypeID);
    }

    //******************************************************************************
    public static function UpdateBounds($Database, $SpatialLayerTypeID) {
        $SelectString = "SELECT MIN(RefX),MAX(RefY),MAX(RefX+RefWidth),MIN(RefY+RefHeight) " .
                "FROM TBL_SpatialLayerGroups " .
                "WHERE SpatialLayerTypeID=$SpatialLayerTypeID ";

        $Set = $Database->Execute($SelectString);

        if (($Set->FetchRow() == false) || ($Set->Field(1) === null)) { // don't have any real data yet
            $UpdateString = "UPDATE TBL_SpatialLayerTypes " .
                    "SET RefX=NULL," .
                    "RefY=NULL," .
                    "RefWidth=NULL," .
                    "RefHeight=NULL " .
                    "WHERE ID=$SpatialLayerTypeID";
        } else { // found groups with data
            $UpdateString = "UPDATE TBL_SpatialLayerTypes " .
                    "SET RefX=" . $Set->Field(1) . "," .
                    "RefY=" . $Set->Field(2) . "," .
                    "RefWidth=" . ($Set->Field(3) - $Set->Field(1)) . "," .
                    "RefHeight=" . ($Set->Field(4) - $Set->Field(2)) . " " .
                    "WHERE ID=$SpatialLayerTypeID";
        }
//    	DebugWriteln("UpdateString=$UpdateString");

        $Database->Execute($UpdateString);
    }

    //******************************************************************************
    // Additional functions
    //******************************************************************************
    /* 	public static function AddFromFilePath($Database,$FilePath,$SpatialLayerTypeName,$Description="",$CoordinateSystemID=null,$InsertLogID)
      //
      //	Adds a new spatiallayer type including the folders based on a new file.
      // Adds the Required:
      //		- SpatialLayerType: based on $SpatialLayerTypeName
      //		- SpatialLayerGroup
      //		- SpatialLayer: based on the $CoordinateSystemID specified
      //		- SpatialLayerGrids: based on the speficied file
      //
      // This public static function will also add map layers for UTM files
      // (obsolete)
      //
      {
      $UserID=GetUserID();

      $NewFileName="Raster_0";

      $FolderPath=GetFolderPathFromFilePath($FilePath);
      //		DebugWriteln("FolderPath=$FolderPath");

      $BaseFileName=GetFileNameWithoutExtension($FilePath);
      //		DebugWriteln("BaseFileName=$BaseFileName");

      // load the original raster

      $Raster=new STRaster();
      $Raster->Load($FilePath);

      //		$Raster->WriteProperties();

      $RefX=$Raster->GetRefX();
      $RefY=$Raster->GetRefY();
      $RefWidth=$Raster->GetRefWidth();
      $RefHeight=$Raster->GetRefHeight();

      if ($CoordinateSystemID==null) // if not specified then use the one in the raster file
      {
      $CRS=$Raster->DropCRS();
      $CoordinateSystemID=$CRS->GetCoordinateSystemID($Database);
      //			DebugWriteln("CoordinateSystemID12 =$CoordinateSystemID");
      }

      // add the type, group, layer, and geographic grid

      $SpatialLayerTypeID=TBL_SpatialLayerTypes::Insert($Database,$SpatialLayerTypeName);
      //		DebugWriteln("SpatialLayerTypeID=$SpatialLayerTypeID");

      $SpatialLayerGroupID=TBL_SpatialLayerGroups::Insert($Database,$SpatialLayerTypeID);
      //		DebugWriteln("SpatialLayerGroupID=$SpatialLayerGroupID");

      $SpatialLayerID=TBL_SpatialLayers::Insert($Database,$SpatialLayerGroupID);
      //		DebugWriteln("SpatialLayerID=$SpatialLayerID");

      TBL_SpatialLayers::Update($Database,$SpatialLayerID,$SpatialLayerTypeName,GEOMETRY_TYPE_RASTER,$CoordinateSystemID);

      $SpatialLayerGridID=TBL_SpatialLayerGrids::Insert($Database,$SpatialLayerID);
      //		DebugWriteln("SpatialLayerGridID=$SpatialLayerGridID");

      // setup the destination path to point to the raster file

      $DestinPath="D:/Inetpub/UserUploads/".GetUserID()."/GeoRasters/".
      "$SpatialLayerTypeID/$SpatialLayerGroupID/$SpatialLayerID/$SpatialLayerGridID/";
      //		DebugWriteln("DestinPath=$DestinPath");

      MakeSurePathExists($DestinPath);

      // copy the temp files to their final locations

      //	DebugWriteln("SourcePath=".$FolderPath.$BaseFileName.".tif");
      //	DebugWriteln("DestinPath=".$DestinPath.$NewFileName.".tif");
      copy($FolderPath.$BaseFileName.".tif",$DestinPath.$NewFileName.".tif");
      copy($FolderPath.$BaseFileName.".tfw",$DestinPath.$NewFileName.".tfw");
      //	unlink($FolderPath.$BaseFileName.".tif");

      //	copy($FolderPath.$BaseFileName.".jpg",$DestinPath."/_display/".$FileName.".jpg");
      //	unlink($FolderPath.$BaseFileName.".jpg");

      // update the grid

      $UpdateString="UPDATE TBL_SpatialLayerGrids ".
      "SET Name='$SpatialLayerTypeName',".
      "AnalysisFlag=1,".
      "MappingFlag=0,".
      "RefX=$RefX,".
      "RefY=$RefY,".
      "NumColumns=1,".
      "NumRows=1, ".
      "RefColumnWidth=$RefWidth,".
      "RefRowHeight=$RefHeight ".
      "WHERE ID=$SpatialLayerGridID";

      //		DebugWriteln("UpdateString=$UpdateString");

      $Database->Execute($UpdateString);

      TBL_SpatialLayerGrids::UpdateFiles($Database,$SpatialLayerGridID);
      TBL_SpatialLayerGrids::UpdateGridToFiles($Database,$SpatialLayerGridID);

      // update the layer

      //		$CoordinateSystemID=LKU_CoordinateSystems::GetIDFromProjection($Database,$Projection,
      //			$UTMZone,$SouthernHemisphere,$Datum);

      $UpdateString="UPDATE TBL_SpatialLayers ".
      "SET Name='$SpatialLayerTypeName',".
      "CoordinateSystemID=$CoordinateSystemID ".
      "WHERE ID=$SpatialLayerID";

      //		DebugWriteln("UpdateString=$UpdateString");

      $Database->Execute($UpdateString);

      TBL_SpatialLayers::UpdateBounds($Database,$SpatialLayerID); // jjg - update bounds should happen after adding other layers and grids

      // update the group

      $UpdateString="UPDATE TBL_SpatialLayerGroups ".
      "SET Name='$SpatialLayerTypeName',".
      "RefX=$RefX,".
      "RefY=$RefY,".
      "RefWidth=$RefWidth,".
      "RefHeight=$RefHeight ".
      "WHERE ID=$SpatialLayerGroupID";

      //		DebugWriteln("UpdateString=$UpdateString");

      $Database->Execute($UpdateString);

      TBL_SpatialLayerGroups::UpdateBounds($Database,$SpatialLayerGroupID);

      // update the type

      $UpdateString="UPDATE TBL_SpatialLayerTypes ".
      "SET Name='$SpatialLayerTypeName', ".
      "Description='$Description', ".
      "PersonID=$UserID ".
      "WHERE ID=$SpatialLayerTypeID";

      //		DebugWriteln("UpdateString=$UpdateString");

      $Database->Execute($UpdateString);

      TBL_SpatialLayerTypes::UpdateBounds($Database,$SpatialLayerTypeID);

      //**************************************************************************
      // create the corresponding map grid

      //		TBL_SpatialLayerGroups::AddMapFiles($Database,$UserID,$InsertLogID,$SpatialLayerGroupID,$NewFileName,$FilePath);

      return($SpatialLayerTypeID);
      }
     */
}

?>
