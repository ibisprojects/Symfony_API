<?php

namespace Classes\DBTable;

//**************************************************************************************
// FileName: LKU_Units.php
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
// Class Definition
//**************************************************************************************

class LKUUnits {

    //**********************************************************************************
    // TBL_DBTables functions
    //**********************************************************************************
    public static function GetFieldValue($Database, $FieldName, $ID, $Default = 0) {
        $Result = TBL_DBTables::GetFieldValue($Database, "LKU_Units", $FieldName, $ID, $Default);

        return($Result);
    }

    public static function SetFieldValue($Database, $FieldName, $ID, $Value) {
        TBL_DBTables::SetFieldValue($Database, "LKU_Units", $FieldName, $ID, $Value);
    }

    //******************************************************************************
    // Basic database functions
    //******************************************************************************

    public static function GetSetFromID($dbConn, $ID) {
        $SelectString = "SELECT * " .
                "FROM LKU_Units " .
                "WHERE ID=:ID";
	
        $stmt = $dbConn->prepare($SelectString);
        $stmt->bindValue("ID", $ID);
        $stmt->execute();
        $Set = $stmt->Fetch();
        
        return($Set);
    }

    public static function GetSet($Database) {
        $SelectString = "SELECT * " .
                "FROM LKU_Units " .
                "ORDER BY Name ";

        $Set = $Database->Execute($SelectString);

        return($Set);
    }

    public static function Delete($Database, $UnitsID) {
        TBL_DBTables::Delete($Database, "LKU_Units", $UnitsID);
    }

    //*******************************************************************
    // Additional functions
    //*******************************************************************
    public static function GetNameFromID($Database, $ID) {
        $Name = "";

        $Set = LKU_Units::GetSetFromID($Database, $ID);

        if ($Set->FetchRow()) {
            $Name = $Set->Field("Name");
        }
        return($Name);
    }

    public static function GetUnitTypeNameFromID($Database, $ID) {
        $Name = "";

        $Set = $Database->Execute("SELECT Name FROM LKU_UnitTypes WHERE ID=$ID");

        if ($Set->FetchRow()) {
            $Name = $Set->Field("Name");
        }
        return($Name);
    }

    public static function GetUnitsForUnitTypeID($Database, $ID) {
        $ID = (int) $ID;

        $Set = $Database->Execute("SELECT ID,Name FROM LKU_Units WHERE UnitTypeID=$ID");

        return($Set);
    }

    public static function GetStandardUnitSetForUnitTypeID($Database, $ID) {
        $SelectString = "SELECT ID,Name,Standard " .
                "FROM LKU_Units " .
                "WHERE UnitTypeID=$ID AND Standard=1";

        $Set = $Database->Execute($SelectString);

        //DebugWriteln("SelectString=".$SelectString);

        return($Set);
    }

    public static function GetStandardUnitNameFromUnitTypeID($Database, $ID) {
        //DebugWriteln("GetStandardUnitNameFromUnitTypeID");
        $Name = "";

        $Set = LKU_Units::GetStandardUnitSetForUnitTypeID($Database, $ID);

        if ($Set->FetchRow()) {
            $Name = $Set->Field("Name");
        }
        return($Name);
    }

    public static function ConvertToStandard($Database, $UnitID, $Value) {
        //$NumDecimals=strlen(substr(strrchr($Value,"."),1));
        //DebugWriteln("Value in LKU_Units::ConvertToStandrad is: $Value");
        //DebugWriteln("Nun Decimals is $NumDecimals");

        $Set = LKU_Units::GetSetFromID($Database, $UnitID);

        if ($Set->FetchRow()) {
            if ($UnitID == 26) { // Fahrenheit
                $Value = $Set->Field("Factor") * ($Value + $Set->Field("Offset")); // do the math, this will generate gazillion decimals...
                //$Value=round($Value,$NumDecimals);
            } else {
                $Value = $Value * $Set->Field("Factor") + $Set->Field("Offset");
                //$Value=round($Value,$NumDecimals);
            }
        }
        return($Value);
    }

    public static function ConvertFromStandard($Database, $UnitID, $Value) {
        $NumDecimals = strlen(substr(strrchr($Value, "."), 1));

        $Set = LKU_Units::GetSetFromID($Database, $UnitID);

        if ($Set->FetchRow()) {
            if ($UnitID == 26) { // Fahrenheit
                //$Value=$Set->Field("Factor")*($Value+$Set->Field("Offset"));
                // F = (c*9/5)+32
                $Value = (($Value * 9 / 5) + 32);

                $Value = round($Value, $NumDecimals);
            } else {
                $Value = ($Value - $Set->Field("Offset")) / $Set->Field("Factor");

                $Value = round($Value, $NumDecimals);
            }
        }
        return($Value);
    }

}

?>
