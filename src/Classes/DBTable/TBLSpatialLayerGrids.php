<?php

namespace Classes\DBTable;

//**************************************************************************************
// FileName: TBL_SpatialLayerGrids.php
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
use Classes\Utilities\SQL;

//**************************************************************************************
// Definitions
//**************************************************************************************
//**************************************************************************************
// Class Definition
//**************************************************************************************

class TBLSpatialLayerGrids {

    //******************************************************************************
    // Basic database functions
    //******************************************************************************

    public static function GetSetFromID($dbConn, $ID) {
        $ID = SQL::SafeInt($ID);

        $SelectString = "SELECT * ".
                "FROM \"TBL_SpatialLayerGrids\" ".
                "WHERE \"ID\"='".$ID."'";

        $stmt = $dbConn->prepare($SelectString);
        $stmt->execute();

        $Set = $stmt->fetch();

        if (!$Set) {
            return false;
        }

        return($Set);
    }

    public static function GetSet($Database, $SpatialLayerID = null, $AnalysisFlag = null) {
        $SelectString = "SELECT * " .
                "FROM TBL_SpatialLayerGrids ";

        if ($SpatialLayerID !== null)
            TBL_DBTables::AddWhereClause($SelectString, "SpatialLayerID=$SpatialLayerID");
        if ($AnalysisFlag != null)
            TBL_DBTables::AddWhereClause($SelectString, "AnalysisFlag=$AnalysisFlag");

//		if ($SpatialLayerID!==null & $AnalysisFlag==null) TBL_DBTables::AddWhereClause($SelectString,"SpatialLayerID=$SpatialLayerID");
//		DebugWriteln("SelectString=$SelectString");
        $Set = $Database->Execute($SelectString);

        return($Set);
    }

    public static function GetRows($Database, $CurrentRow, $NumRows, $TotalRows, $OrderByField, $DescendingFlag, $Fields = null, $SpatialLayerID = null) {
        if ($CurrentRow >= $TotalRows) {
            $LastPage = (int) ((($TotalRows + $NumRows - 1) / $NumRows) - 1); // from PageSettings

            $CurrentRow = $LastPage * $NumRows; // go to the last page
        }
        // get the query for $CurrentRow+$NumRows rows in reversed order

        $SelectString1 = "SELECT TOP " . ($TotalRows - $CurrentRow) . " ID " .
                "FROM TBL_SpatialLayerGrids ";

        if ($SpatialLayerID !== null)
            TBL_DBTables::AddWhereClause($SelectString1, "SpatialLayerID=$SpatialLayerID");

        TBL_DBTables::AddOrderByClause($SelectString1, $OrderByField, !$DescendingFlag); // query the rows in the opposite order of what the user wants
        // create the query that gets the top $NumRows and reverses the order to make it the way the user wants

        $SelectString = TBL_DBTables::GetSelectClause(0, $NumRows, $Fields);

        $SelectString.="FROM TBL_SpatialLayerGrids " .
                "WHERE ID IN ($SelectString1) " .
                "ORDER BY $OrderByField ";

        if ($DescendingFlag)
            $SelectString.="DESC "; // can't use order by function, finds previous order by





//		DebugWriteln("SelectString=$SelectString");

        $Set = $Database->Execute($SelectString);

        return($Set);
    }

    public static function GetTotalRows($Database, $SpatialLayerID = null) {
        // get the query for $CurrentRow+$NumRows rows in reversed order

        $SelectString = "SELECT COUNT(*) " .
                "FROM TBL_SpatialLayerGrids ";

        if ($SpatialLayerID !== null)
            TBL_DBTables::AddWhereClause($SelectString, "SpatialLayerID=$SpatialLayerID");

//		DebugWriteln("SelectString=$SelectString");

        $Set = $Database->Execute($SelectString);

        return($Set->Field(1));
    }

    public static function Insert($dbConn, $SpatialLayerID) {
        // insert the new record

        $ExecString="INSERT INTO \"TBL_SpatialLayerGrids\" (\"SpatialLayerID\", \"NumColumns\", \"NumRows\") 
			VALUES ($SpatialLayerID, 1, 1)";

        $stmt = $dbConn->prepare($ExecString);
        $stmt->execute();

        return $dbConn->lastInsertId('TBL_SpatialLayerGrids_ID_seq');
    }

    public static function Update($dbConn, $ID, $Name, $RefX = null, $RefY = null, $RefColumnWidth = null, $RefRowHeight = null, $NumColumns = null, $NumRows = null, $MinZoom = null, $MaxZoom = null, $StartDate = null, $EndDate = null, $FolderPath = null) {
        $UpdateString = "UPDATE \"TBL_SpatialLayerGrids\" ".
                "SET \"Name\"='$Name' ";

        if ($RefX !== null)
            $UpdateString.=",\"RefX\"='$RefX' ";
        if ($RefY !== null)
            $UpdateString.=",\"RefY\"='$RefY' ";
        if ($RefColumnWidth !== null)
            $UpdateString.=",\"RefColumnWidth\"='$RefColumnWidth' ";
        if ($RefRowHeight !== null)
            $UpdateString.=",\"RefRowHeight\"='$RefRowHeight' ";
        if ($NumColumns !== null)
            $UpdateString.=",\"NumColumns\"='$NumColumns' ";
        if ($NumRows !== null)
            $UpdateString.=",\"NumRows\"='$NumRows' ";

        if ($MinZoom !== null)
            $UpdateString.=",\"MinZoom\"='$MinZoom' ";
        if ($MaxZoom !== null)
            $UpdateString.=",\"MaxZoom\"='$MaxZoom' ";
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
        TBL_DBTables::Delete($Database, "TBL_SpatialLayerGrids", $ID);
    }

    //******************************************************************************
    // Standard entry functions
    //******************************************************************************
    public static function GetStandardID($dbConn, $AreaSubtypeID, $CoordinateSystemID, $MinZoom = null, $MaxZoom = null) {
        //
        //	Returns an ID to a spatial layer grid with PersonID=NULL (standard).
        //	Creates the grid and any required parents if none is found.//
        // try to find an existing grid

        $SelectString = "SELECT \"TBL_SpatialLayerGrids\".\"ID\" ".
                "FROM \"TBL_SpatialLayerGrids\" ".
                "INNER JOIN \"TBL_SpatialLayers\" ".
                "ON \"TBL_SpatialLayers\".\"ID\"=\"TBL_SpatialLayerGrids\".\"SpatialLayerID\" ".
                "INNER JOIN \"TBL_SpatialLayerGroups\" ".
                "ON \"TBL_SpatialLayerGroups\".\"ID\"=\"TBL_SpatialLayers\".\"SpatialLayerGroupID\" ".
                "INNER JOIN \"TBL_SpatialLayerTypes\" ".
                "ON \"TBL_SpatialLayerTypes\".\"ID\"=\"TBL_SpatialLayerGroups\".\"SpatialLayerTypeID\" ".
                "WHERE \"AreaSubtypeID\"=$AreaSubtypeID ".
                "AND \"CoordinateSystemID\"=$CoordinateSystemID ".
                "AND \"PersonID\" IS NULL";

        if ($MinZoom != null)
            TBLDBTables::AddWhereClause($SelectString, "\"MinZoom\"=$MinZoom");
        if ($MaxZoom != null)
            TBLDBTables::AddWhereClause($SelectString, "\"MaxZoom\"=$MaxZoom");

        $stmt = $dbConn->prepare($SelectString);
        $stmt->execute();
        $SpatialLayerGridSet = $stmt->fetch();

        if ($SpatialLayerGridSet) { // get it from the set
            $SpatialLayerGridID = $SpatialLayerGridSet["ID"];
        } else { // find a layer and insert a new grid
            $SpatialLayerID = TBLSpatialLayers::GetStandardID($dbConn, $AreaSubtypeID, $CoordinateSystemID);

            $Name = TBLSpatialLayers::GetNameFromID($dbConn, $SpatialLayerID);

            $SpatialLayerGridID = TBLSpatialLayerGrids::Insert($dbConn, $SpatialLayerID);

            TBLSpatialLayerGrids::Update($dbConn, $SpatialLayerGridID, $Name, null, null, null, null, null, null, $MinZoom, $MaxZoom);
        }

        return($SpatialLayerGridID);
    }

    //******************************************************************************
    // Additional functions
    //******************************************************************************
    /*    public static function GetSetFromSpatialLayerID($Database,$SpatialLayerID)
      {
      $SpatialLayerID=SafeInt($SpatialLayerID);

      $SelectString="SELECT * ".
      "FROM TBL_SpatialLayerGrids ".
      "WHERE SpatialLayerID='$SpatialLayerID'";

      $Set=$Database->Execute($SelectString);

      return($Set);
      }
     */
    public static function GetSetFromSpatialLayerTypeID($Database, $SpatialLayerTypeID, $CoordinateSystemID = null, $Zoom = null, $MappingFlag = null, $AnalyaisFlag = null) {
        $SelectString = "SELECT TBL_SpatialLayerGrids.ID " .
                "FROM TBL_SpatialLayerTypes " .
                "INNER JOIN TBL_SpatialLayerGroups ON " .
                "TBL_SpatialLayerGroups.SpatialLayerTypeID=TBL_SpatialLayerTypes.ID " .
                "INNER JOIN TBL_SpatialLayers ON " .
                "TBL_SpatialLayers.SpatialLayerGroupID=TBL_SpatialLayerGroups.ID " .
                "INNER JOIN TBL_SpatialLayerGrids ON " .
                "TBL_SpatialLayerGrids.SpatialLayerID=TBL_SpatialLayers.ID " .
                "WHERE SpatialLayerTypeID=$SpatialLayerTypeID ";

        if ($CoordinateSystemID != null)
            $SelectString.="AND TBL_SpatialLayers.CoordinateSystemID=$CoordinateSystemID ";

        if ($Zoom != null)
            $SelectString.=
                    "AND (TBL_SpatialLayerGrids.MinZoom IS NULL " .
                    "OR TBL_SpatialLayerGrids.MinZoom=0 " .
                    "OR TBL_SpatialLayerGrids.MinZoom<=$Zoom) " .
                    "AND (TBL_SpatialLayerGrids.MaxZoom IS NULL " .
                    "OR TBL_SpatialLayerGrids.MaxZoom=0 " .
                    "OR TBL_SpatialLayerGrids.MaxZoom>$Zoom) ";

        if ($MappingFlag != null)
            $SelectString.="AND TBL_SpatialLayerGrids.MappingFlag=$MappingFlag ";

        if ($AnalyaisFlag != null)
            $SelectString.="AND TBL_SpatialLayerGrids.AnalysisFlag=$AnalyaisFlag ";

//		DebugWriteln("SelectString=$SelectString");

        $Set = $Database->Execute($SelectString);

        return($Set);
    }

    //******************************************************************************
    // Update functions
    //******************************************************************************
    public static function UpdateFiles($Database, $SpatialLayerGridID, $ThePage = null) {
        //
        //	Updates the TBL_SpatialLayerFiles entries based on the specified
        //	SpatialLayerGridID.  Uses the folder path in the grid to find the
        //	files and update the records.
        //
	// Inputs:
        //	ThePage - optional parameter to print out what is going on (only used in admin)//

        if ($ThePage !== null)
            $TheTable = new TableSettings(TABLE_DATA);

        $SpatialLayerGridSet = TBL_SpatialLayerGrids::GetSetFromID($Database, $SpatialLayerGridID);

        $FolderPath = $SpatialLayerGridSet->Field("FolderPath");

        //	DebugWriteln("FolderPath=$FolderPath");
        // remove any existing records

        if ($ThePage !== null)
            $ThePage->Heading(1, " Deleting file entries in the database");

        //	if ($ThePage!==null) $ThePage->BodyText("Deleting existing files");

        TBL_SpatialLayerFiles::DeleteFromSpatialLayerGridID($Database, $SpatialLayerGridID);

        if ($ThePage !== null)
            $ThePage->ParagraphBreak();

        // get the list of files

        $FileArray = GetDirectoryFileArray($FolderPath);
        //DebugWriteln("FileArray=$FileArray");
        //DumpArray($FileArray);
        // add all the files to the database

        if ($ThePage !== null) {
            $ThePage->Heading(1, " Adding files to database");

            $TheTable->TableStart();
            $TheTable->TableColumnHeadings(array("Count", "ID", "File Name",
                "RefX", "RefY", "RefWidth", "RefHeight"));
            $TotalStart = GetMicrotime();
        }


        $FileCount = count($FileArray);
        //DebugWriteln("FileCount=$FileCount");

        $Count = 0;
        for ($i = 0; $i < $FileCount; $i++) {
            $SourceFilePath = $FileArray[$i];
            //	DebugWriteln("SourceFilePath=$SourceFilePath");

            if (!is_dir($SourceFilePath)) {
                $Extension = GetFileExtensionFromFilePath($SourceFilePath);

                $Extension = strtolower($Extension);

                if (($Extension == "ecw") || ($Extension == "tif")) {
                    $SourceFilePath = AppendPath($FolderPath, $SourceFilePath);
                    //			DebugWriteln("SourceFilePath=$SourceFilePath");

                    BlueSpray::GetRasterFileBounds($InputPath, $RefX, $RefY, $RefWidth, $RefHeight);

                    // load info
                    //			$Raster->Load($SourceFilePath);
                    /* 					$Raster->LoadInfo($SourceFilePath);
                      //			DebugWriteln("Raster->Resource=$Raster->Resource");

                      $RefX=$Raster->GetRefX();
                      $RefY=$Raster->GetRefY();
                      $RefWidth=$Raster->GetRefWidth();
                      $RefHeight=$Raster->GetRefHeight();
                     */
//					DebugWriteln("RefX=$RefX");
//					DebugWriteln("RefY=$RefY");
//					DebugWriteln("RefWidth=$RefWidth");
//					DebugWriteln("RefHeight=$RefHeight");

                    $SpatialLayerFileID = TBL_SpatialLayerFiles::Insert($Database, $SpatialLayerGridID, $SourceFilePath, $RefX, $RefY, $RefWidth, $RefHeight);

                    if ($ThePage !== null) {
                        $TheTable->TableRow(array($Count, $SpatialLayerFileID, $SourceFilePath,
                            $RefX, $RefY, $RefWidth, $RefHeight));
                    }

                    $Count++;
                }
            }
        }

        if ($ThePage !== null) {
            $TheTable->TableEnd();

            $TotalDuration = GetMicrotime() - $TotalStart;
            $ThePage->BodyText("TotalDuration=$TotalDuration");
        }
    }

    public static function UpdateBoundsFromFiles($Database, $SpatialLayerGridID, $ThePage = null) {
        //
        //	Recomputes the dimensions of the columns and rows based on the files in the grid//

        $SpatialLayerFileSet = TBL_SpatialLayerFiles::GetSet($Database, $SpatialLayerGridID);

        $RefLeft = 0;
        $RefRight = 0;
        $RefTop = 0;
        $RefBottom = 0;
        $Intialized = false;

        if ($ThePage !== null)
            $ThePage->Heading(1, " Updating the upper-left corner, the row height and the column width based on the files in the grid.");

        while ($SpatialLayerFileSet->FetchRow()) {
            if ($Intialized == false) {
                $RefLeft = $SpatialLayerFileSet->Field("RefX");
                $RefRight = $RefLeft + $SpatialLayerFileSet->Field("RefWidth");
                $RefTop = $SpatialLayerFileSet->Field("RefY");
                $RefBottom = $RefTop + $SpatialLayerFileSet->Field("RefHeight");
                $Intialized = true;
            } else {
                $TempLeft = $SpatialLayerFileSet->Field("RefX");
                $TempRight = $TempLeft + $SpatialLayerFileSet->Field("RefWidth");
                $TempTop = $SpatialLayerFileSet->Field("RefY");
                $TempBottom = $TempTop + $SpatialLayerFileSet->Field("RefHeight");

                if ($RefLeft > $TempLeft)
                    $RefLeft = $TempLeft;
                if ($RefRight < $TempRight)
                    $RefRight = $TempRight;
                if ($RefTop < $TempTop)
                    $RefTop = $TempTop;
                if ($RefBottom > $TempBottom)
                    $RefBottom = $TempBottom;
            }
        }

        if ($Intialized) {
            $Set = TBL_SpatialLayerGrids::GetSetFromID($Database, $SpatialLayerGridID);

            $NumColumns = $Set->Field("NumColumns");
            $NumRows = $Set->Field("NumRows");

            if ($NumColumns <= 0)
                $NumColumns = 1;
            if ($NumRows <= 0)
                $NumRows = 1;

            $RefColumnWidth = ($RefRight - $RefLeft) / $NumColumns;
            $RefRowHeight = ($RefBottom - $RefTop) / $NumRows;

            if ($ThePage !== null) {
                $ThePage->BodyText("RefLeft=$RefLeft");
                $ThePage->BodyText("RefTop=$RefTop");
                $ThePage->BodyText("RefRight=$RefRight");
                $ThePage->BodyText("RefBottom=$RefBottom");
                $ThePage->BodyText("RefColumnWidth=$RefColumnWidth");
                $ThePage->BodyText("RefRowHeight=$RefRowHeight");
                $ThePage->BodyText("NumColumns=" . $Set->Field("NumColumns"));
                $ThePage->BodyText("NumRows=" . $Set->Field("NumRows"));
            }

            $UpdateString = "UPDATE TBL_SpatialLayerGrids " .
                    "SET RefX=$RefLeft, " .
                    "RefY=$RefTop, " .
                    "RefColumnWidth=$RefColumnWidth, " .
                    "RefRowHeight=$RefRowHeight, " .
                    "NumColumns=$NumColumns, " .
                    "NumRows=$NumRows " .
                    "WHERE ID=$SpatialLayerGridID";

            $Database->Execute($UpdateString);
        } else {
            if ($ThePage !== null)
                $ThePage->BodyText("No files found");
        }
    }

    public static function UpdateGridToFiles($Database, $SpatialLayerGridID, $ThePage = null) {
        //
        //	Updates the GridToFile relationships in the REL_SpatialLayerGridToFile table
        //	based on the specified SpatialLayerGridID.  The settings in the grid (NumRows, NumColumns, etc.)
        //	are used to determine the number of cells and thus the number of relationships
        //	between the grid and the file
        //
	// Inputs:
        //	ThePage - optional parameter to print out what is going on (only used in admin)//
        //	$Title="Updating Spatial Layer Grids for SpatialLayerGridID=$SpatialLayerGridID";
        //	$ThePage->Heading(0,$Title);

        if ($ThePage !== null)
            $TheTable = new TableSettings(TABLE_DATA);

        // remove any existing records

        if ($ThePage !== null)
            $ThePage->Heading(1, " Deleting grid entries in the database");

        REL_SpatialLayerGridToFile::DeleteFromSpatialLayerGridID($Database, $SpatialLayerGridID);

        // get the spatiallayer record

        if ($ThePage !== null)
            $ThePage->Heading(1, " Adding grid entries to the database");

        $LayerSet = TBL_SpatialLayerGrids::GetSetFromID($Database, $SpatialLayerGridID);

        $LayerRefX = $LayerSet->Field("RefX");
        $LayerRefY = $LayerSet->Field("RefY");
        $LayerRefColumnWidth = $LayerSet->Field("RefColumnWidth");
        $LayerRefRowHeight = $LayerSet->Field("RefRowHeight");
        $LayerNumColumns = $LayerSet->Field("NumColumns");
        $LayerNumRows = $LayerSet->Field("NumRows");

//		DebugWriteln("LayerRefColumnWidth=$LayerRefColumnWidth");

        $LayerRefLeft = $LayerRefX;
        $LayerRefTop = $LayerRefY;

        $LayerRefRight = $LayerRefX + $LayerRefColumnWidth * $LayerNumColumns;
        $LayerRefBottom = $LayerRefY + $LayerRefRowHeight * $LayerNumRows;

        /* 		if ($ThePage!==null)
          {
          //			$ThePage->BodyText("LayerRefLeft=$LayerRefLeft");
          //			$ThePage->BodyText("LayerRefTop=$LayerRefTop");
          //			$ThePage->BodyText("LayerRefRight=$LayerRefRight");
          //			$ThePage->BodyText("LayerRefBottom=$LayerRefBottom");
          //			$ThePage->BodyText("LayerRefColumnWidth=$LayerRefColumnWidth");
          //			$ThePage->BodyText("LayerRefRowHeight=$LayerRefRowHeight");
          $ThePage->BodyText("LayerNumColumns=$LayerNumColumns");
          $ThePage->BodyText("LayerNumRows=$LayerNumRows");
          }
         */
        // get the list of files

        $FileSet = TBL_SpatialLayerFiles::GetSet($Database, $SpatialLayerGridID);

        // add all the files to the database

        if ($ThePage !== null) {
            $TheTable->TableStart();
            $TheTable->TableColumnHeadings(array("Count", "GridToFileID", "FileID",
                "FileLeft", "FileRight", "FileTop", "FileBottom", "Row", "Column"));
            $TotalStart = GetMicrotime();
        }


        $Count = 0;
        while ($FileSet->FetchRow()) {
            $SpatialLayerFileID = $FileSet->Field("ID");

            $FileRefX = $FileSet->Field("RefX");
            $FileRefY = $FileSet->Field("RefY");
            $FileRefWidth = $FileSet->Field("RefWidth");
            $FileRefHeight = $FileSet->Field("RefHeight");

            $FileRefLeft = $FileRefX;
            $FileRefTop = $FileRefY;

            $FileRefRight = $FileRefX + $FileRefWidth;
            $FileRefBottom = $FileRefY + $FileRefHeight;

//			DebugWriteln("FileRefLeft=$FileRefLeft");
//			DebugWriteln("FileRefTop=$FileRefTop");
//			DebugWriteln("FileRefRight=$FileRefRight");
//			DebugWriteln("FileRefBottom=$FileRefBottom");

            $StartColumn = ($FileRefX - $LayerRefX) / $LayerRefColumnWidth;
            //	DebugWriteln("StartColumn=$StartColumn");
            $StartColumn = (int) $StartColumn;

            $EndColumn = ($FileRefRight - $LayerRefX) / $LayerRefColumnWidth;
            //	DebugWriteln("EndColumn=$EndColumn");
            $EndColumn = (int) ($EndColumn); // make sure we include any of a column it overlaps in

            $StartRow = ($FileRefY - $LayerRefY) / $LayerRefRowHeight;
            //	DebugWriteln("StartRow=$StartRow");
            $StartRow = (int) $StartRow;

            $EndRow = ($FileRefBottom - $LayerRefY) / $LayerRefRowHeight;
            //	DebugWriteln("EndRow=$EndRow");
            $EndRow = (int) ($EndRow); // make sure we include any of a Row it overlaps in
//			DebugWriteln("StartColumn=$StartColumn");
//			DebugWriteln("EndColumn=$EndColumn");
//			DebugWriteln("StartRow=$StartRow");
//			DebugWriteln("EndRow=$EndRow");

            for ($Row = $StartRow; $Row <= $EndRow; $Row++) {
                for ($Column = $StartColumn; $Column <= $EndColumn; $Column++) {
                    //			DebugWriteln("Inserting Row=$Row, Column=$Column");

                    $SpatialLayerGridToFileID = REL_SpatialLayerGridToFile::Insert($Database, $SpatialLayerGridID, $SpatialLayerFileID, $Row, $Column);

                    if ($ThePage !== null) {
                        $TheTable->TableRow(array($Count, $SpatialLayerGridToFileID, $SpatialLayerFileID,
                            $FileRefLeft, $FileRefRight, $FileRefTop, $FileRefBottom,
                            $Row, $Column));
                    }
                    $Count++;
                }
            }
        }

        if ($ThePage !== null) {
            $TheTable->TableEnd();

            $TotalDuration = GetMicrotime() - $TotalStart;
            $ThePage->BodyText("TotalDuration=$TotalDuration");
        }
    }

}

?>
