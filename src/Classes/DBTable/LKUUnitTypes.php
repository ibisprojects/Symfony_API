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
    //******************************************************************************
    // Basic database functions
    //******************************************************************************

    public static function GetSetFromID($dbConn, $ID) {
        $SelectString = "SELECT * " .
                "FROM \"LKU_UnitTypes\" " .
                "WHERE \"ID\"=:ID";
        $stmt = $dbConn->prepare($SelectString);
        $stmt->bindValue("ID", $ID);
        $stmt->execute();
        $Set = $stmt->Fetch();
        return($Set);
    }
}

?>
