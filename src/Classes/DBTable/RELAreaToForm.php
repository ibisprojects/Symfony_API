<?php
namespace Classes\DBTable;
//**************************************************************************************
// FileName: REL_AreaToForm.php
// Author: gjn
// Owner: gjn
// Basic static database interaction class
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
// Global Variables (use as constants)
//**************************************************************************************
//**************************************************************************************
// Static Functions
//**************************************************************************************
use Classes\TBLDBTables;

class RELAreaToForm {

    public static function GetSet($dbConn, $AreaID = 0, $FormID = 0) {
        $SelectString = "SELECT  REL_AreaToForm.ID, REL_AreaToForm.AreaID, REL_AreaToForm.FormID, TBL_Areas.AreaName 
    		FROM REL_AreaToForm INNER JOIN
                TBL_Areas ON REL_AreaToForm.AreaID = TBL_Areas.ID";

        if ($AreaID > 0)
            TBLDBTables::AddWhereClause($SelectString, "AreaID= :AreaID");
        if ($FormID > 0)
            TBLDBTables::AddWhereClause($SelectString, "FormID= :FormID");

        TBLDBTables::AddOrderByClause($SelectString, "AreaName", false);

        $stmt = $dbConn->prepare($SelectString);
        if ($AreaID > 0)
            $stmt->bindValue("AreaID", $AreaID);
        if ($FormID > 0)
            $stmt->bindValue("FormID", $FormID);
        $stmt->execute();
        $data = array();
        //print_r($SelectString);
         while($FormEntry = $stmt->fetch()){
             $data[] = array("AreaID"=>$FormEntry["AreaID"],"AreaName"=>$FormEntry["AreaName"]);
             //print_r($FormID);
         }
         
        return $data;
    }

    public static function GetSetFromID($Database, $ID) {
        $SelectString = "SELECT * FROM REL_AreaToForm 
    		WHERE ID = $ID";

        $Set = $Database->Execute($SelectString);

        return($Set);
    }

    // *********************************************************************************

    public static function Insert($Database, $AreaID, $FormID) {
        $ID = -1;

        $ExecString = "EXEC insert_REL_AreaToForm $AreaID, $FormID";

        $ID = $Database->DoInsert($ExecString);

        return($ID);
    }

    public static function Update($Database, $ID, $AreaID, $FormID) {
        $UpdateString = "UPDATE REL_AreaToForm " .
                "SET AreaID='$AreaID', " .
                "SET FormID='$FormID'";

        $UpdateString = $UpdateString . "WHERE ID=" . $ID;

        $Database->Execute($UpdateString);

        return($ID);
    }

    public static function Delete($Database, $ID = 0) {
        TBL_DBTables::Delete($Database, "REL_AreaToForm", $ID);
    }

    //*******************************************************************
    // Additional functions
    //*******************************************************************
//
}

?>
