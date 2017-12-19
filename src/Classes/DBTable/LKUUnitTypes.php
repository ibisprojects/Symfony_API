<?php

namespace Classes\DBTable;

//**************************************************************************************
// FileName: LKU_UnitTypes.php
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

define("UNIT_TYPE_DISTANCE", 1);
define("UNIT_TYPE_AREA", 2);
define("UNIT_TYPE_VOLUME", 3);
define("UNIT_TYPE_MASS", 4);
define("UNIT_TYPE_FLUID_FLOW", 5);
define("UNIT_TYPE_VOLUME_PER_AREA", 6);
define("UNIT_TYPE_MASS_PER_AREA", 7);
define("UNIT_TYPE_TIME", 8);
define("UNIT_TYPE_TEMPERATURE", 9);
define("UNIT_TYPE_WEIGHT_RATIO", 10);
define("UNIT_TYPE_CONCENTRATION", 11);
define("UNIT_TYPE_PARTS_PER", 12); // gjn; how is this different from concentration?
define("UNIT_TYPE_PERCENT", 13);
define("UNIT_TYPE_AREA_SPREAD", 14);
define("UNIT_TYPE_LINEAR_SPREAD", 15);
define("UNIT_TYPE_COUNT_PER_AREA", 16);
define("UNIT_TYPE_PH", 17);
define("UNIT_TYPE_COUNT", 18);
define("UNIT_TYPE_COUNT_PER_METER_SQUARED", 19); // gjn; same as count per area, delete?

define("UNIT_TYPE_CENTIMETERS", 27); // gjn; this is used by SurveyAdd2_EcoNab.php but 
// there is no corresponding record in the database 
// for centimeters as a unit type.

$UnitTypeStrings = array(
    " -- Select a Unit Type -- ", // reserved for the NoneValue in FormRowArray
    "Distance",
    "Area",
    "Volume",
    "Mass",
    "Fluid Flow",
    "Volume per area",
    "Mass per area",
    "Time",
    "Temperature",
    "Weight Ratio",
    "Concentration",
    "Parts per",
    "Percent",
    "Area spread",
    "Linear spread",
    "Count per area",
    "PH",
    "Count",
    "Count per meter squared");

//**************************************************************************************
// Class Definition
//**************************************************************************************

class LKUUnitTypes {

    //**********************************************************************************
    // TBL_DBTables functions
    //**********************************************************************************

    public static function GetFieldValue($Database, $FieldName, $ID, $Default = 0) {
        $Result = TBL_DBTables::GetFieldValue($Database, "LKU_UnitTypes", $FieldName, $ID, $Default);

        return($Result);
    }

    public static function SetFieldValue($Database, $FieldName, $ID, $Value) {
        TBL_DBTables::SetFieldValue($Database, "LKU_UnitTypes", $FieldName, $ID, $Value);
    }

    //******************************************************************************
    // Basic database functions
    //******************************************************************************

    public static function GetSetFromID($dbConn, $ID) {
        $SelectString = "SELECT * " .
                "FROM LKU_UnitTypes " .
                "WHERE ID='$ID'";
        $stmt = $dbConn->prepare($SelectString);
        $stmt->bindValue("ID", $ID);
        $stmt->execute();
        $Set = $stmt->Fetch();
        return($Set);
    }

    public static function GetSet($Database) {
        $SelectString = "SELECT * " .
                "FROM LKU_UnitTypes " .
                "ORDER BY Name ";

        $Set = $Database->Execute($SelectString);

        return($Set);
    }

    public static function Delete($Database, $UnitTypeID) {
        TBL_DBTables::Delete($Database, "LKU_UnitTypes", $UnitTypeID);
    }

    //*******************************************************************
    // Additional functions
    //*******************************************************************
}

?>
