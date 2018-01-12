<?php

namespace Classes\DBTable;

//**************************************************************************************
// FileName: REL_SpatialLayerGridToData.php
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

class RELSpatialLayerGridToData {

    //******************************************************************************
    // Basic database functions
    //******************************************************************************

    public static function GetSet($Database) {
        $SelectString = "SELECT * " .
                "FROM REL_SpatialLayerGridToData ";

        $Set = $Database->Execute($SelectString);

        return($Set);
    }

    public static function GetSetFromID($Database, $ID) {
        $ID = SQL::SafeInt($ID);

        $SelectString = "SELECT * " .
                "FROM REL_SpatialLayerGridToData " .
                "WHERE ID='" . $ID . "'";

        $Set = $Database->Execute($SelectString);

        return($Set);
    }

    public static function GetRows($Database, &$CurrentRow, $NumRows, $TotalRows, $OrderByField, $DescendingFlag, $Fields = null, $SpatialLayerGridID = null, $SpatialLayerDataID = null) {
        if ($CurrentRow >= $TotalRows) {
            $LastPage = (int) ((($TotalRows + $NumRows - 1) / $NumRows) - 1); // from PageSettings

            $CurrentRow = $LastPage * $NumRows; // go to the last page
        }

//		DebugWriteln("CurrentRow=$CurrentRow");
        // get the query for $CurrentRow+$NumRows rows in reversed order

        $SelectString1 = "SELECT TOP " . ($TotalRows - $CurrentRow) . " ID " .
                "FROM REL_SpatialLayerGridToData ";

        if ($SpatialLayerGridID !== null)
            TBL_DBTables::AddWhereClause($SelectString1, "SpatialLayerGridID=$SpatialLayerGridID");
        if ($SpatialLayerDataID !== null)
            TBL_DBTables::AddWhereClause($SelectString1, "SpatialLayerDataID=$SpatialLayerDataID");

        TBL_DBTables::AddOrderByClause($SelectString1, $OrderByField, !$DescendingFlag); // query the rows in the opposite order of what the user wants
        // create the query that gets the top $NumRows and reverses the order to make it the way the user wants

        $SelectString = TBL_DBTables::GetSelectClause(0, $NumRows, $Fields);

        $SelectString.="FROM REL_SpatialLayerGridToData " .
                "WHERE ID IN ($SelectString1) " .
                "ORDER BY $OrderByField ";

        if ($DescendingFlag)
            $SelectString.="DESC "; // can't use order by function, finds previous order by


//		DebugWriteln("SelectString=$SelectString");

        $Set = $Database->Execute($SelectString);

        return($Set);
    }

    public static function GetTotalRows($Database, $SpatialLayerGridID = null, $SpatialLayerDataID = null) {
        // get the query for $CurrentRow+$NumRows rows in reversed order

        $SelectString = "SELECT COUNT(*) " .
                "FROM REL_SpatialLayerGridToData ";

        if ($SpatialLayerGridID !== null)
            TBL_DBTables::AddWhereClause($SelectString, "SpatialLayerGridID=$SpatialLayerGridID");
        if ($SpatialLayerDataID !== null)
            TBL_DBTables::AddWhereClause($SelectString, "SpatialLayerDataID=$SpatialLayerDataID");

//		DebugWriteln("SelectString=$SelectString");

        $Set = $Database->Execute($SelectString);

        return($Set->Field(1));
    }

    public static function Insert($dbConn, $SpatialLayerGridID, $SpatialLayerDataID, $LayerRow, $LayerColumn, $AreaID) {
        $ExecString="INSERT INTO \"REL_SpatialLayerGridToData\" (
				\"SpatialLayerGridID\",
				\"LayerRow\",
				\"LayerColumn\",
				\"SpatialLayerDataID\",
				\"AreaID\"
			) VALUES (
				$SpatialLayerGridID,
				$LayerRow,
				$LayerColumn,
				$SpatialLayerDataID,
				$AreaID
			)";

        $stmt = $dbConn->prepare($ExecString);
        $stmt->execute();

        return $dbConn->lastInsertId('REL_SpatialLayerGridToData_ID_seq');
    }

    public static function Delete($Database, $ID) {
        TBL_DBTables::Delete($Database, "REL_SpatialLayerGridToData", $ID);
    }

    //******************************************************************************
    // Additional functions
    //******************************************************************************
    /*   public static function DeleteFromSpatialLayerGridID($Database,$SpatialLayerGridID)
      //
      // this is ok because these are leaf nodes
      //
      {
      $SpatialLayerGridID=SafeInt($SpatialLayerGridID);

      $DeleteString="DELETE FROM REL_SpatialLayerGridToData ".
      "WHERE SpatialLayerGridID=".$SpatialLayerGridID;

      //		DebugWriteln("DeleteString=".$DeleteString);

      $Database->Execute($DeleteString);
      }
     */
}

?>
