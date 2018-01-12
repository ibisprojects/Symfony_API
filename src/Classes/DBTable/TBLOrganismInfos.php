<?php

namespace Classes\DBTable;

//**************************************************************************************
// FileName: TBL_OrganismInfos.php
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
use Classes\DBTable\TBLTaxonUnits;
use Classes\DBTable\RELOrganismInfoToTSN;
use Classes\TBLDBTables;

//**************************************************************************************
// Definitions
//**************************************************************************************

define("ORGANISM_GROUP_PLANT", 1);
define("ORGANISM_GROUP_ANIMAL", 2);
define("ORGANISM_GROUP_DISEASE", 3);

define("ORGANISM_TYPE_PLANT_TERRESTRIAL", 1);
define("ORGANISM_TYPE_PLANT_AQUATIC", 2);
define("ORGANISM_TYPE_ANIMAL_VERTIBRATE_TERRESTRIAL", 3);
define("ORGANISM_TYPE_ANIMAL_INVERTIBRATE_TERRESTRIAL", 4);
define("ORGANISM_TYPE_ANIMAL_VERTIBRATE_AQUATIC", 5);
define("ORGANISM_TYPE_ANIMAL_INVERTIBRATE_AQUATIC", 6);
define("ORGANISM_TYPE_DISEASE_ANIMAL", 7);
define("ORGANISM_TYPE_DISEASE_PLANT", 8);

$TBL_OrganismInfos_OrganismTypes = array("Unknown", "Terrestrial Plant", "Aquatic Plant",
    "Terrestrial Vertibrate Animal", "Terrestrial Invertibrate Animal",
    "Aquatic Vertibrate Animal", "Aquatic Invertibrate Animal",
    "Animal Disease", "Plant Disease");

//**************************************************************************************
// Class Definition
//**************************************************************************************

class TBLOrganismInfos {
    //**********************************************************************************
    // Basic database functions
    //**********************************************************************************

    public static function GetSetFromID($dbConn, $ID = "", $Name = "") {
        $SelectString = "SELECT * " .
                "FROM \"TBL_OrganismInfos\" ";

        if ($Name != "")
            TBLDBTables::AddWhereClause($SelectString, "\"Name\"=:Name'$Name'");

        if ($ID != "")
            TBLDBTables::AddWhereClause($SelectString, "\"ID\"=:ID");

        $stmt = $dbConn->prepare($SelectString);
        if ($Name != "")
            $stmt->bindValue("Name", $Name);
        if ($ID != "")
            $stmt->bindValue("ID", $ID);
        $stmt->execute();
        $Set = $stmt->Fetch();

        return($Set);
    }

    public static function GetName($Database, $OrganismInfoID, $HTMLFlag = true, $ShowSciNameFlag = true) {
    //
    // This public static function should be used to get the name of an OrganismInfo.  If a name is defined
    // this public static function will return it, otherwise it will return the first scientific name
    // (which should be the only one since groups should have a name)//

        $Name = "Untitled";

        if ($OrganismInfoID > 0) {
            $Set = TBLOrganismInfos::GetSetFromID($Database, $OrganismInfoID);

            $Name = $Set["Name"];

            if ($ShowSciNameFlag == true) {
                $TSNSet = RELOrganismInfoToTSN::GetSet($Database, $OrganismInfoID);

                if ($TSNSet) {
                    $SciName = " (";

                    do {
                        if ($SciName != " (")
                            $SciName.=", ";
                        $TSNRes = $TSNSet->Fetch();
                        $HTMLFlag= False;
                        $SciName.=TBLTaxonUnits::GetScientificNameFromTSN($Database, $TSNRes["TSN"], $HTMLFlag);
                    }
                    while ($TSNSet->Fetch());

                    $SciName.=")";

                    $Name.=$SciName;
                }
            }
        }
        return($Name);
    }
}

?>
