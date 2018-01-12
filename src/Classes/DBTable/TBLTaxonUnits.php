<?php

namespace Classes\DBTable;

//**************************************************************************************
// FileName: TBL_TaxonUnits.php
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
//require_once("C:/Inetpub/wwwroot/cwis438/Classes/DBTable/TBL_IDSource_Aids.php");
//**************************************************************************************
// Definitions
//**************************************************************************************

define("KINGDOM_ANY", 0);
define("KINGDOM_MONERA", 1);
define("KINGDOM_PROTISTA", 2);
define("KINGDOM_PLANTAE", 3);
define("KINGDOM_FUNGI", 4);
define("KINGDOM_ANIMALIA", 5);

define("RANK_KINGDOM", 10);
define("RANK_PHYLUM", 30);
define("RANK_CLASS", 60);
define("RANK_ORDER", 100);
define("RANK_FAMILY", 140);
define("RANK_GENUS", 180);
define("RANK_SPECIES", 220);
define("RANK_SUBSPECIES", 230);
define("RANK_VARIETY", 240);
define("RANK_INFRA_SPECIFIC", 900);
// taxonomic rankings

$NumRankIDs = 9;
$RequiredRanks = array(10, 30, 60, 100, 140, 180, 220, 230, 240);
$RankIDs = array(// these are the required ranks
    10, // Kingdom
    30, // Phylum/Division
    60, // Class
    100, // Order
    140, // Family
    180, // Genus
    220, // species
    230, // subspecies
    240);  // variety

$RankStrings = array("Kingdom", "Phylum/Division", "Class", "Order", "Family", "Genus", "Species");

//**************************************************************************************
// Class Definition
//**************************************************************************************

class TBLTaxonUnits {
    public static function GetScientificNameFromTSN($Database, $TSN, $HTMLFlag = FALSE) {
        $OrganismSet = TBLTaxonUnits::GetSetFromTSN($Database, $TSN);

        //	DebugWriteln("Set=".$OrganismSet);

        $String = TBLTaxonUnits::GetScientificNameFromSet($OrganismSet, $HTMLFlag);

        return($String);
    }

    public static function StringIsEmpty($String) {
        if ($String == null || $String == "" || !is_string($String) || strlen($String) <= 0) {
            return TRUE;
        }
        return FALSE;
    }

    public static function GetScientificNameFromSet($OrganismSet, $IncludeHTML = FALSE) {
        //
        //	Returns a full scientific name from an organism set that includes:
        //		UnitName1,UnitName2,UnitInd3,UnitName3,UnitInd4,UnitName4
        //
	//	Each component of the scientific name is added if it exisits.
        //	The UnitInd# fields contain either "ssp." or "var."
        //
        $Name = "";

        //	if ($OrganismSet->FetchRow())
        //	{
        //		DebugWriteln("hello");

        if ($IncludeHTML == TRUE)
            $Name = $Name . "<i>";

        $Name = $Name . $OrganismSet["UnitName1"]; // Could be phylum/division,class,order,family,or genus

        if (!TBLTaxonUnits::StringIsEmpty($OrganismSet["UnitName2"])) { // species
            $Name = $Name . " " . $OrganismSet["UnitName2"];
        }

        if (!TBLTaxonUnits::StringIsEmpty($OrganismSet["UnitName3"])) { // subspecies or variety
            $Name = $Name . " " . $OrganismSet["UnitInd3"];
            $Name = $Name . " " . $OrganismSet["UnitName3"];
        }

        if (!TBLTaxonUnits::StringIsEmpty($OrganismSet["UnitName4"])) { // variety
            $Name = $Name . " " . $OrganismSet["UnitInd4"];
            $Name = $Name . " " . $OrganismSet["UnitName4"];
        }
//			DebugWriteln("Name=$Name");

        if ($IncludeHTML == TRUE)
            $Name = $Name . "</i>";
        //	}
        return($Name);
    }

    public static function GetSetFromTSN($dbConn, $TSN) {
        $SelectString = "SELECT * " .
                "FROM \"TBL_TaxonUnits\" " .
                "WHERE \"TSN\"=:TSN";

//		DebugWriteln("SelectString=$SelectString");

        $stmt = $dbConn->prepare($SelectString);
        $stmt->bindValue("TSN", $TSN);
        $stmt->execute();
        $Set = $stmt->Fetch();

        return($Set);
    }
}

?>
