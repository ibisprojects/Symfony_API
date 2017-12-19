<?php

namespace Classes\DBTable;

//**************************************************************************************
// FileName: REL_OrganismInfoToTSN.php
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

//**************************************************************************************
// Definitions
//**************************************************************************************
//**************************************************************************************
// Global Variables(use as constants)
//**************************************************************************************
//**************************************************************************************
// Utility Functions
//**************************************************************************************

class RELOrganismInfoToTSN {

    //**********************************************************************************
    // Basic database functions
    //**********************************************************************************
    public static function GetSet($dbConn, $OrganismInfoID = null, $TSN = null) {
        $OrganismInfoID = $OrganismInfoID;

        $SelectString = "SELECT * " .
                "FROM REL_OrganismInfoToTSN ";

        if ($OrganismInfoID !== null)
            TBLDBTables::AddWhereClause($SelectString, "OrganismInfoID=:OrganismInfoID");
        if ($TSN !== null)
            TBLDBTables::AddWhereClause($SelectString, "TSN=:TSN");

//		DebugWriteln("SelectString=$SelectString");

        $stmt = $dbConn->prepare($SelectString);
        if ($OrganismInfoID !== null)
            $stmt->bindValue("OrganismInfoID", $OrganismInfoID);
        if ($TSN !== null)
            $stmt->bindValue("TSN", $TSN);
        $stmt->execute();       

        return($stmt);
    }

    public static function Insert($Database, $OrganismInfoID, $TSN) {
        $ID = -1;

        $Set = REL_OrganismInfoToTSN::GetSet($Database, $OrganismInfoID, $TSN);

        if ($Set->FetchRow()) {
            $ID = $Set->Field("ID");
        } else {
            $ExecString = "EXEC insert_REL_OrganismInfoToTSN '$OrganismInfoID','$TSN'";

            $ID = $Database->DoInsert($ExecString);
        }
        return($ID);
    }

    public static function Delete($Database, $OrganismInfoToTSNID) {
        TBL_DBTables::Delete($Database, "REL_OrganismInfoToTSN", $OrganismInfoToTSNID);
    }

}

?>
