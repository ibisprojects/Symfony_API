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

    //**********************************************************************************
    // TBL_DBTables functions
    //**********************************************************************************

    public static function GetFieldValue($Database, $FieldName, $ID, $Default = 0) {
        $Result = TBL_DBTables::GetFieldValue($Database, "TBL_TaxonUnits", $FieldName, $ID, $Default);

        return($Result);
    }

    public static function SetFieldValue($Database, $FieldName, $ID, $Value) {
        TBL_DBTables::SetFieldValue($Database, "TBL_TaxonUnits", $FieldName, $ID, $Value);
    }

    //**********************************************************************************
    // Basic database functions
    //**********************************************************************************

    public static function GetSetFromID($Database, $ID) {
        $SelectString = "SELECT * " .
                "FROM TBL_TaxonUnits " .
                "WHERE ID=" . SafeInt($ID);

        $Set = $Database->Execute($SelectString);

        return($Set);
    }

    public static function GetSet($Database, $RequiredParentTSN = null, $TaxonRank = null, $IDSource_Aids_Using = null, $OrderByField = null) {
        $SelectString = "SELECT * " .
                "FROM TBL_TaxonUnits ";

        if ($RequiredParentTSN != null)
            TBL_DBTables::AddWhereClause($SelectString, "RequiredParentTSN=$RequiredParentTSN");
        if ($TaxonRank != null)
            TBL_DBTables::AddWhereClause($SelectString, "TaxonRank=$TaxonRank");
        if ($IDSource_Aids_Using != null)
            TBL_DBTables::AddWhereClause($SelectString, "IDSource_Aids_Using=$IDSource_Aids_Using");

        if ($OrderByField != null)
            $SelectString.=" ORDER BY $OrderByField";

//		DebugWriteln("SelectString=$SelectString");

        $Set = $Database->Execute($SelectString);

        return($Set);
    }

    public static function Insert($Database, $TSN, $KingdomID, $UnitName1, $UnitName2 = null, $UnitInd3 = null, $UnitName3 = null, $UnitInd4 = null, $UnitName4 = null, $InsertLogID, $TaxonRank, $ParentTSN, $RequiredParentTSN, $AddedByWebSiteID = null, $InsertOrganismInfo = true, $YearAdded = null) {
        if ($AddedByWebSiteID == null)
            $AddedByWebSiteID = GetWebSiteID();

        //DebugWriteln("UnitName1=$UnitName1");
        // insert the new taxon unit record
        $ID = 1;

        $ExecString = "EXEC insert_TBL_TaxonUnits " . SafeInt($TSN);

        //DebugWriteln("ExecString=".$ExecString);

        $ID = $Database->DoInsert($ExecString);

        // update the other values

        $RequiredParentTSN = sql::GetInt($RequiredParentTSN);
        $String = "UPDATE TBL_TaxonUnits " .
                "SET UnitName1='$UnitName1', " .
                "ParentTSN=$ParentTSN, " .
                "RequiredParentTSN=$RequiredParentTSN, " .
                "Used=1";

        if ($UnitName2 != null)
            $String.=", UnitName2='$UnitName2' ";
        if ($UnitInd3 != null)
            $String.=", UnitInd3='$UnitInd3' ";
        if ($UnitName3 != null)
            $String.=", UnitName3='$UnitName3' ";
        if ($UnitInd4 != null)
            $String.=", UnitInd4='$UnitInd4' ";
        if ($UnitName4 != null)
            $String.=", UnitName4='$UnitName4' ";
        if ($InsertLogID != null)
            $String.=", InsertLogID=$InsertLogID ";
        if ($YearAdded != null)
            $String.=", YearAdded=$YearAdded ";

        $String.=", TaxonRank=$TaxonRank";
        $String.=", KingdomID=$KingdomID ";
        $String.=", AddedByWebSiteID=$AddedByWebSiteID ";
        $String.="WHERE ID=$ID";

//		DebugWriteln("Update String=$String");

        $Database->Execute($String);

        // set the flatten fields
        TBL_TaxonUnits::SetFlattenFields($Database, $TSN);

        //$Name="Untitled";
        $Name = $UnitName1;

        if ($UnitName2 != null)
            $Name.=" $UnitName2";
        if ($UnitInd3 != null)
            $Name.=" $UnitInd3";
        if ($UnitName3 != null)
            $Name.=" $UnitName3";
        if ($UnitInd4 != null)
            $Name.=" $UnitInd4";
        if ($UnitName4 != null)
            $Name.=" $UnitName4";

        // make sure there is an entry in OrganismInfo		
        if ($InsertOrganismInfo)
            TBL_OrganismInfos::Insert($Database, $TSN, $Name); // make sure there is a cooresponding organismInfo for the new TSN

        return($ID);
    }

    //**********************************************************************************
    public static function Delete($Database, $ID) {
        //
        //	Deletes a TaxonUnit and its associated records://

        TBL_DBTables::Delete($Database, "TBL_TaxonUnits", $ID);
    }

    //******************************************************************************
    // Additional functions
    //******************************************************************************

    public static function WriteTaxonUnitPopup($TheTaxonTable, $Database, $TaxonRank, $ParentTSN, $Label, $ParameterName, $CurrentValue, $ClientFunction, $RowFlag = true, $CellFlag = true) {
//		DebugWriteln("ParameterName=$ParameterName");
//		DebugWriteln("CurrentValue=$CurrentValue");

        $SelectString = "SELECT * " .
                "FROM TBL_TaxonUnits " .
                "WHERE IDSource_Aids_Using=1 " .
                "AND TaxonRank=$TaxonRank";

        if ($ParentTSN != null)
            $SelectString.=" AND RequiredParentTSN=$ParentTSN";

//		$SelectString.=" ORDER BY UnitName1";
//		DebugWriteln("SelectString=$SelectString");

        $StartTime = GetMicroTime();

        $TaxonUnitSet = $Database->Execute($SelectString);

        $TaxonArray = array();
        while ($TaxonUnitSet->FetchRow()) {
            $TSN = $TaxonUnitSet->Field("TSN");
            $UnitName1 = $TaxonUnitSet->Field("UnitName1");

            $TaxonArray[$TSN] = $UnitName1;
//			DebugWriteln("$TSN: $UnitName1");
        }
//		DebugWriteln("----- Origanval Array");
//		DumpArray($TaxonArray);
        asort($TaxonArray);
//		DebugWriteln("----- Sorted Array");
//		DumpArray($TaxonArray);
//		DebugWriteln("Duration=".(GetMicroTime()-$StartTime));

        $StartTime1 = GetMicroTime();
//		DebugWriteln("MicroTime()=".GetMicroTime());
//		DebugWriteln("StartTime1=".$StartTime1);

        if ($RowFlag)
            $TheTaxonTable->TableRowStart();

        if ($CellFlag)
            $TheTaxonTable->TableCell(0, $Label . "&nbsp;&nbsp;");

        if ($CellFlag)
            $TheTaxonTable->TableCellStart(1); // was Left


            
//		$KeyArray=array_keys($TaxonArray); // get the TSNs
//		DebugWriteln("----- Key Array");
//		DumpArray($TaxonArray);
///DebugWriteln("CurrentValue=$CurrentValue");
//DebugWriteln("get=".gettype($CurrentValue));

        $TheTaxonTable->FormArray($ParameterName, $CurrentValue, $TaxonArray, 0, null, TRUE, "All", 0, false, 0, false, "onchange='$ClientFunction'", null, true); // last was 50%
//				$TheTaxonTable->FormRecordSet($ParameterName,$CurrentValue,$TaxonUnitSet,array("UnitName1"),"TSN",TRUE,"All",0,0,false,"onchange='$ClientFunction'",true,"85%"); // last was 50%
        if ($CellFlag)
            $TheTaxonTable->TableCellEnd();

        if ($RowFlag)
            $TheTaxonTable->TableRowEnd();

//		DebugWriteln("Microtime=".GetMicroTime());
//		DebugWriteln("Duration1=".(GetMicroTime()-$StartTime1));
    }

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
                "FROM TBL_TaxonUnits " .
                "WHERE TSN=:TSN";

//		DebugWriteln("SelectString=$SelectString");

        $stmt = $dbConn->prepare($SelectString);
        $stmt->bindValue("TSN", $TSN);
        $stmt->execute();
        $Set = $stmt->Fetch();

        return($Set);
    }

    public static function GetIDFromTSN($Database, $TSN) {
        $ID = null;

        $SelectString = "SELECT * " .
                "FROM TBL_TaxonUnits " .
                "WHERE TSN='" . $TSN . "'";

        $Set = $Database->Execute($SelectString);

        if ($Set->FetchRow())
            $ID = $Set->Field("ID");

        return($ID);
    }

    public static function SetUsed($Database, $TSN) {
        $SelectString = "UPDATE TBL_TaxonUnits " .
                "SET Used=1 " .
                "WHERE TSN='$TSN'";

        $Database->Execute($SelectString);

        $Set = TBL_OrganismInfos::GetSetFromTSN($Database, $TSN);

        if ($Set->FetchRow() == false) {
            TBL_OrganismInfos::Insert($Database, $TSN); // make sure there is a cooresponding organismInfo for the new TSN
        }
    }

    public static function Set_IDSource_Aids_Using($Database, $TSN, $ModifyDatabase = 1) {
        // Set the IDSource_Aids_Using flag based on the existence of
        // Manually created REL_IDSource_Aid_To_TSN records
        // Look for relationships in REL_IDSource_Aid_To_TSN
        // where the ID Aid is approved and the relationship is manually added
        $SelectString = "SELECT  count(*) " .
                "FROM REL_IDSource_Aid_To_TSN INNER JOIN TBL_IDSource_Aids " .
                "ON REL_IDSource_Aid_To_TSN.IDSource_AidID = TBL_IDSource_Aids.ID " .
                "WHERE (REL_IDSource_Aid_To_TSN.TSN = $TSN) " .
                "AND (REL_IDSource_Aid_To_TSN.ManualAdded = 1) " .
                "AND (TBL_IDSource_Aids.Status = " . STATUS_APPROVED . ")";
        $Set = $Database->Execute("$SelectString");
        $Count = $Set->Field(1);
        //DebugWriteln("Set_IDSource_Aids_Using: Count=$Count\n$SelectString\n");
        if ($Count > 0) {
            $TaxonID = TBL_TaxonUnits::GetIDFromTSN($Database, $TSN);
            if ($ModifyDatabase)
                TBL_TaxonUnits::SetFieldValue($Database, "IDSource_Aids_Using", $TaxonID, 1);

//			DebugWriteln("\tSet IDSource_Aids_Using for TSN $TSN to 1 ***");
        } else {
            // Look for decendent TSN records with manually created REL_IDSource_Aid_To_TSN records
            $Set = TBL_TaxonUnits::GetSetFromTSN($Database, $TSN);
            if ($Set->FetchRow()) {
                $TaxonRank = $Set->Field("TaxonRank");
                $KingdomID = $Set->Field("KingdomID");

                // Make a query to find REL_IDSource_Aid_To_TSN records
                // of decendent TSN's based on this TSN's rank
                $SelectString = "SELECT count(*) " .
                        "FROM REL_IDSource_Aid_To_TSN " .
                        "WHERE ManualAdded=1 " .
                        "AND IDSource_AidID IN " .
                        "(SELECT ID FROM TBL_IDSource_Aids " .
                        "WHERE Status=" . STATUS_APPROVED . ") " .
                        "AND TSN IN " .
                        "(SELECT TSN FROM TBL_TaxonUnits " .
                        "WHERE ";

                if ($TaxonRank == 10)
                // Kingdom
                    $SelectString.="KingdomID=$KingdomID) ";
                elseif ($TaxonRank == 30)
                // Phylum/Division
                    $SelectString.="PhylumTSN=$TSN) ";
                elseif ($TaxonRank == 60)
                // Class
                    $SelectString.="ClassTSN=$TSN) ";
                elseif ($TaxonRank == 100)
                // Order
                    $SelectString.="OrderTSN=$TSN) ";
                elseif ($TaxonRank == 140)
                // Family
                    $SelectString.="FamilyTSN=$TSN) ";
                elseif ($TaxonRank == 180)
                // Genus
                    $SelectString.="GenusTSN=$TSN) ";
                else
                // Infrarank or low level rank (e.g. species), so do not set any children
                    $SelectString = "";

                if ($SelectString != "") {
                    $CountSet = $Database->Execute($SelectString);
                    $Count = $CountSet->Field(1);

                    //DebugWriteln("TBL_TaxonUnits.Set_IDSource_Aids_Using: Count=$Count\nSelectString=$SelectString\n\n");
                    // If we get at lease one decendent record with a manually created REL,
                    // set the IDSource_Aids_Using flag to 1
                    if ($Count > 0) {
                        $IDSource_Aids_Using = 1;
                    } else {
                        $IDSource_Aids_Using = 0;
                    }

                    $TaxonID = TBL_TaxonUnits::GetIDFromTSN($Database, $TSN);
                    if ($ModifyDatabase)
                        TBL_TaxonUnits::SetFieldValue($Database, "IDSource_Aids_Using", $TaxonID, $IDSource_Aids_Using);

//					DebugWriteln("\tSet IDSource_Aids_Using for TSN $TSN to $IDSource_Aids_Using");
                }
            }
        }
    }

    public static function GetRankIDFromTSN($Database, $TSN) {
        $SelectString = "SELECT TaxonRank " .
                "FROM TBL_TaxonUnits " .
                "WHERE TSN=$TSN";

//		DebugWriteln("SelectString=".$SelectString);

        $Set = $Database->Execute($SelectString);

        return($Set->Field("TaxonRank"));
    }

    public static function GetSetFromInsertLogID($Database, $InsertLogID) {
        $SelectString = "SELECT * " .
                "FROM TBL_TaxonUnits " .
                "WHERE TSN < 0 AND " .
                "InsertLogID=" . SafeInt($InsertLogID);

        //DebugWriteln("SelectString=".$SelectString);
        $Set = $Database->Execute($SelectString);

        return($Set);
    }

    public static function GetCustomTSNs($Database) {
        $SelectString = "SELECT TSN " .
                "FROM TBL_TaxonUnits " .
                "WHERE (TSN < 0) " .
                "ORDER BY TSN";

        $Set = $Database->Execute($SelectString);

        return($Set);
    }

    //******************************************************************************
    // Rank functions (only called by TaxonBrowse)
    //******************************************************************************
    public static function GetNextRequiredRankID($TaxonRank) {
        global $RankIDs;

        $NextRankID = -1;

        $Index = GetRankIndex($TaxonRank);

        if (($Index != -1) && (($Index + 1) < count($RankIDs)))
            $NextRankID = $RankIDs[$Index + 1];

        return($NextRankID);
    }

    public static function GetRankIndex($TaxonRank) {
        global $RankIDs;

        $Index = -1;

        for ($i = 0; ($i < count($RankIDs)) && ($Index == -1); $i++) { // find index to the current TaxonRank
            if ($RankIDs[$i] == $TaxonRank)
                $Index = $i;
        }
        return($Index);
    }

    //******************************************************************************
    // Search Functions
    //******************************************************************************
    //*********************************************************************************
    public static function FindTSNFromScientificName($Database, $ScientificName, &$Count, $Kingdom = KINGDOM_ANY, $ExcludeSynonyms = true, $RequiredParentTSN = null) {
        //
        //	Takes a standard organism scientific name in the following format:
        //	
        //	(Genus species sub subspecies var variety (authority)
        //
	// 	Returns an organismSet with the following members://

        $Genus = "";
        $Species = "";
        $Subspecies = "";
        $Variety = "";

        // get the name components

        TBL_TaxonUnits::ParseScientificName($Database, $ScientificName, $Genus, $Species, $Subspecies, $Variety);

        $TSN = TBL_TaxonUnits::FindTSNForScientificNamePieces($Database, $Count, $Kingdom, $Genus, $Species, $Subspecies, $Variety, $ExcludeSynonyms, $RequiredParentTSN);

        return($TSN);
    }

    //*********************************************************************************
    public static function ParseScientificName($Database, $ScientificName, &$Genus, &$Species, &$Subspecies, &$Variety) {
        //
        //	Takes a standard organism scientific name in the following format:
        //	
        //	(Genus species sub subspecies var variety (authority)
        //
	// 	and breaks it into it's components.
        //
	//	Only called by FindTSNFromScientificName() above//

        $Genus = "";
        $Species = "";
        $Subspecies = "";
        $Variety = "";

        $Index = stripos($ScientificName, ",");
        if ($Index !== FALSE) {
            //		DebugWriteln("ScientificName=$ScientificName");
            $ScientificName = substr($ScientificName, 0, $Index);
            //		DebugWriteln("* ScientificName=$ScientificName");
        }
        $Index = stripos($ScientificName, ";");
        if ($Index !== FALSE)
            $ScientificName = substr($ScientificName, 0, $Index);

        $Tokens = explode(" ", $ScientificName);

        if (count($Tokens) > 0) { // have a genus
            $Genus = $Tokens[0];

            if (count($Tokens) > 1) { // have a species
                $Species = $Tokens[1];

                $Index = 2;

                while (($Index < count($Tokens)) &&
                (($Subspecies == "") || ($Variety == ""))) { // check until out of tokens or have both subspecies and variety
                    if (($Subspecies == "") &&
                            ($Tokens[$Index] == "ssp") || ($Tokens[$Index] == "ssp.")) {
                        $Index++;

                        if ($Index < count($Tokens)) {
                            $Subspecies = $Tokens[$Index];
                            $Index++;
                        }
                    } else if (($Variety == "") &&
                            ($Tokens[$Index] == "var") || ($Tokens[$Index] == "var.")) {
                        $Index++;

                        if ($Index < count($Tokens)) {
                            $Variety = $Tokens[$Index];
                            $Index++;
                        }
                    } else
                        $Index++;
                }
            }
        }
        //	DebugWriteln("Genus=".$Genus);
        //	DebugWriteln("Species=".$Species);
        //	DebugWriteln("Subspecies=".$Subspecies);
        //	DebugWriteln("Variety=".$Variety);
    }

    public static function FindTSNFFromUnitNames($Database, &$Count, $Kingdom = KINGDOM_ANY, $Genus = "", $Species = "", $Subspecies = "", $Variety = "", $ExcludeSynonyms = true, $RequiredParentTSN = null) {


        $Genus = SafeString($Genus);
        $Species = SafeString($Species);
        $Subspecies = SafeString($Subspecies);
        $Variety = SafeString($Variety);

        $QueryName = strtolower(trim((trim($Genus) . ' ' . trim($Species) . ' ' . trim($Subspecies) . ' ' . trim($Variety))));
        $Query = "SELECT TSN FROM TBL_TaxonUnits WHERE  LTRIM(RTRIM(LOWER( LTRIM(RTRIM(UnitName1))+ ' ' +LTRIM(RTRIM(UnitName2))+' '+LTRIM(RTRIM(UnitName3))+' '+LTRIM(RTRIM(UnitName4))))) LIKE '$QueryName' ";
        if ($Kingdom != KINGDOM_ANY)
            $Query = $Query . " AND KingdomID=" . $Kingdom;

        if ($ExcludeSynonyms) {
            $Query = $Query . " AND (RequiredParentTSN<>0 OR TaxonRank=10)";
        }

        if ($RequiredParentTSN !== null) {
            $Query = $Query . " AND (RequiredParentTSN=$RequiredParentTSN)";
        }
        //print_r("Query: ");
        //print_r($Query."\n");

        $Set = $Database->Execute($Query);
        $TSN = 0;
        if ($Set->FetchRow()) {
            $TSN = $Set->Field("TSN");

            $Count = 1;

            while ($Set->FetchRow() != FALSE) {
                $Count++;
            }
            if ($Count > 1) {
                $Query = $Query . " AND (RequiredParentTSN IS NOT NULL)";
                //DebugWriteln("Query======$Query");
                $NewSet = $Database->Execute($Query);
                if ($Set->FetchRow()) {
                    $TSN = $NewSet->Field("TSN");
                }
            }
        }
        return($TSN);
    }

    public static function FindTSNForScientificNamePieces($Database, &$Count, $Kingdom = KINGDOM_ANY, $Genus = "", $Species = "", $Subspecies = "", $Variety = "", $ExcludeSynonyms = true, $RequiredParentTSN = null) {
        //
        // Only called by SurveyAdd via the FindTSNFromScientificName() above//
//		DebugWriteln("Genus=".$Genus);
//		DebugWriteln("Species=".$Species);
//		DebugWriteln("Subspecies=".$Subspecies);
//		DebugWriteln("Variety=".$Variety);

        $Genus = SafeString($Genus);
        $Species = SafeString($Species);
        $Subspecies = SafeString($Subspecies);
        $Variety = SafeString($Variety);

        $TSN = 0;
        $Count = 0;

        // all querys must at least have a genus

        $Query = "SELECT TSN FROM TBL_TaxonUnits WHERE UnitName1 LIKE '" . $Genus . "'";



        // add the kingdom

        if ($Kingdom != KINGDOM_ANY)
            $Query = $Query . " AND KingdomID=" . $Kingdom;

        //DebugWriteln("Query=$Query");
        // add the species

        if ($Species != "") {  // add a species
            $Query = $Query . " AND UnitName2 LIKE '" . $Species . "'";

            if ($Subspecies != "") { // have a subspecies
                $Query = $Query . " AND UnitInd3 LIKE 'ssp.' AND UnitName3 LIKE '" . $Subspecies . "'";

                if ($Variety != "") {
                    $Query = $Query . " AND UnitInd4 LIKE 'var.' AND UnitName4 LIKE '" . $Variety . "'"; // add a variety in Name4
                } else {
                    $Query = $Query . " AND ((UnitName4='') OR (UnitName4 is null))"; // Name4 must be blank
                }
            } else { // no sub species, Name3 may have a variety
                if ($Variety != "") {
                    $Query = $Query . " AND UnitInd3 LIKE 'var.' AND UnitName3 LIKE '" . $Variety . "'"; // add a variety in Name4
                    $Query = $Query . " AND ((UnitName4='') OR (UnitName4 is null))"; // Name4 must be blank
                } else {
                    $Query = $Query . " AND ((UnitName3='') OR (UnitName3 is null)) " .
                            "AND ((UnitName4='') OR (UnitName4 is null))"; // Name4 must be blank
                }
            }
        } else {
            $Query = $Query . " AND ((UnitName2='') OR (UnitName2 is null)) " .
                    "AND ((UnitName3='') OR (UnitName3 is null)) " .
                    "AND ((UnitName4='') OR (UnitName4 is null))"; // just a genus
        }

        if ($ExcludeSynonyms) {
            $Query = $Query . " AND (RequiredParentTSN<>0 OR TaxonRank=10)";
        }

        if ($RequiredParentTSN !== null) {
            $Query = $Query . " AND (RequiredParentTSN=$RequiredParentTSN)";
        }

        // execute the query
        //DebugWriteln("Query=".$Query);
        $Set = $Database->Execute($Query);
        //	DebugWriteln("Set=".$Set);
        // if successful, get the TSN and count the number of matches

        if ($Set->FetchRow()) {
            $TSN = $Set->Field("TSN");

            $Count = 1;

            while ($Set->FetchRow() != FALSE) {
                $Count++;
            }

            // GJN
            // if we get multiple matches than one of them is likely a not accepted TSN from ITIS
            // and hence would not have a RequiredParentTSN; use the one where RequiredParentTSN is not null

            if ($Count > 1) {
                $Query = $Query . " AND (RequiredParentTSN IS NOT NULL)";
                //DebugWriteln("Query======$Query");
                $NewSet = $Database->Execute($Query);
                $TSN = $NewSet->Field("TSN");
            }
        } else {
            //		DebugWriteln("Genus=".$Genus);
            //		DebugWriteln("Species=".$Species);
            //		DebugWriteln("Subspecies=".$Subspecies);
            //		DebugWriteln("Variety=".$Variety);
        }
        //DebugWriteln("Query=$Query");
        //DebugWriteln("TSN======$TSN");
        return($TSN);
    }

    public static function GetTSNFromSciName($Database, $SciName, $Details = FALSE) {
        // This function is extremelly similar to FindTSNForScientificNamePieces,
        // except it does not require UnitInd3 to contain "ssp." and UnitInd4 to contain
        // "var."  This is extremely important for matching 3 and 4-word names, especially for
        // those that have different qualifiers, or have no qualifiers at all.
        // By default, this function returns only the TSN ($Details=FALSE)
        // You also have the option of setting $Details to TRUE, which
        // will give you the Scientific Name of the TSN as it appears in TaxonUnits, if
        // you would like it for comparison purposes.  Note that if Details=TRUE, your TSN 
        // will be returned in an array, with Result[0] containing the TSN, and Result[1] 
        // containing the scientific name.
        // -LMM 7-17-13
        // Escape ' for database
        $SciName = str_replace("'", "''", $SciName);

        // Note that this will match names where the qualifiers are not the same.
        // E.g. Lymantria dispar pv. dispar given to this function will match
        // lymantria dispar cv. dispar in the database, lymantria dispar ssp. dispar
        // will match lymantria dispar dispar, and lymantria dispar dispar will match
        // lymantria dispar subsp. dispar.
        $SciName = TBL_TaxonUnits::StripQualifiersInScientificName($SciName);

        $SciNamePieces = explode(" ", $SciName);

        $query = "SELECT TSN, UnitInd1, UnitName1, UnitInd2, UnitName2, UnitInd3, UnitName3, UnitInd4, UnitName4
                        FROM TBL_TaxonUnits
                        WHERE UnitName1 LIKE '$SciNamePieces[0]'";

        if (isset($SciNamePieces[1])) {
            $query .= " AND UnitName2 LIKE '$SciNamePieces[1]'";
        } else {
            $query .= " AND (UnitName2 LIKE '' OR UnitName2 IS NULL)";
        }

        if (isset($SciNamePieces[2])) {
            $query .= " AND UnitName3 LIKE '$SciNamePieces[2]'";
        } else {
            $query .= " AND (UnitName3 LIKE '' OR UnitName3 IS NULL)";
        }
        if (isset($SciNamePieces[3])) {
            $query .= " AND UnitName4 LIKE '$SciNamePieces[3]'";
        } else {
            $query .= " AND (UnitName4 LIKE '' OR UnitName4 IS NULL)";
        }

        // This excludes "floating" Taxon Units that have a parent, 
        // therefore aren't in the standard heierarchy.
        $query .= " AND (RequiredParentTSN<>0 OR TaxonRank=10)";

        $Set = $Database->Execute($query);

        if ($Set->FetchRow()) {
            $TSN = $Set->Field("TSN");

            if ($Details) {
                $UnitInd1 = $Set->Field("UnitInd1");
                $UnitName1 = $Set->Field("UnitName1");
                $UnitInd2 = $Set->Field("UnitInd2");
                $UnitName2 = $Set->Field("UnitName2");
                $UnitInd3 = $Set->Field("UnitInd3");
                $UnitName3 = $Set->Field("UnitName3");
                $UnitInd4 = $Set->Field("UnitInd4");
                $UnitName4 = $Set->Field("UnitName4");

                $ScientificName = "$UnitInd1 $UnitName1 $UnitInd2 $UnitName2 $UnitInd3 $UnitName3 $UnitInd4 $UnitName4";

                // Remove trailing spaces
                $ScientificName = trim($ScientificName);

                // Remove whitespace
                $ScientificName = preg_replace('/\s+/', ' ', $ScientificName);

                $Results = array($TSN, $ScientificName);
                return $Results;
            } else {
                return $TSN;
            }
        }
    }

    //******************************************************************************
    // Functions getting required parents
    //******************************************************************************

    public static function GetRequiredParentTSN($Database, $TSN) {
        //
        //	Returns the parent TSN that is "required" to be above the specified TSN
        //	Returns 0 if a required parent TSN could not be found//

        $Count = 0;
        $TargetRank = -1;
        $NumRequiredRanks = 9;
        global $RequiredRanks;
        $Rank = -1;

        $OriginalTSN = $TSN;

        while ((($Rank == -1) || ($Rank > $TargetRank)) && ($TSN != 0)) {
            // the following settings allow us to exit the while if the db request fails

            $Rank = 0;
            $ParentTSN = 0;

            $SelectString = "SELECT ParentTSN,TaxonRank " .
                    "FROM TBL_TaxonUnits " .
                    "WHERE TSN=$TSN";

            $RecordSet = $Database->Execute($SelectString);

            $RecordSet->FetchRow();

            $ParentTSN = $RecordSet->Field(1);
            $Rank = $RecordSet->Field(2);

            // get the required rank

            if ($TargetRank == -1) { // first time query
                for ($i = 1; ($i < $NumRequiredRanks) && ($TargetRank == -1); $i++) {
                    if ($RequiredRanks[$i] >= $Rank) {
                        $TargetRank = $RequiredRanks[$i - 1];
                    }
                }
            }

            // else we will exit with ParentTSN==required parent TSN

            if ($Rank > $TargetRank)
                $TSN = $ParentTSN;
        }
        if ($TSN == $OriginalTSN)
            $TSN = 0;

        return((int) $TSN);
    }

    //******************************************************************************
    // Functions for managing the tree
    //******************************************************************************
    public static function GetNextCustomTSNValue($Database) {
        $TSNSelectString = "SELECT * FROM TBL_TaxonUnits WHERE TSN<0 ORDER BY TSN";
        $TSNSet = $Database->Execute($TSNSelectString);
        $TSN = $TSNSet->Field("TSN") - 1;
        return($TSN);
    }

    public static function CreateValidFamilyTree($Database, $KingdomID, $InsertlogID, $TaxonRank, $ParentTSN, $RequiredParentTSN, $Genus, $Species = null, $Subspecies = null, $Variety = null) {
        //
        //	
        $TSN = TBL_TaxonUnits::GetNextCustomTSNValue($Database);

        $ID = 0;

        // add the genus if needed

        if ($Genus != "") {
            $GenusString = "SELECT * " .
                    "FROM TBL_TaxonUnits " .
                    "WHERE UnitName1='$Genus' " .
                    "AND TaxonRank=180";

            $GenusSet = $Database->Execute($GenusString);

            if (!$GenusSet->FetchRow()) {
                $ID = TBL_TaxonUnits::Insert($Database, $TSN, $KingdomID, $Genus, null, null, null, null, null, $InsertlogID, 180, $ParentTSN, $RequiredParentTSN);

                $TSN-=1;

                $ParentTSN = $TSN;
            }
        }

        // add the species if needed

        if ($Species != "") {
            $SpeciesString = "SELECT * " .
                    "FROM TBL_TaxonUnits " .
                    "WHERE UnitName1='$Genus' " .
                    "AND UnitName2='$Species' " .
                    "AND TaxonRank=220";

            $SpeciesSet = $Database->Execute($SpeciesString);

            if (!$SpeciesSet->FetchRow()) {
                $ID = TBL_TaxonUnits::Insert($Database, $TSN, $KingdomID, $Genus, $Species, null, null, null, null, $InsertlogID, 220, $ParentTSN, $RequiredParentTSN);

                $TSN-=1;

                $ParentTSN = $TSN;
            }
        }

        // add the subspecies if needed

        if ($Subspecies != "") {
            $SubspeciesString = "SELECT * " .
                    "FROM TBL_TaxonUnits " .
                    "WHERE UnitName1='$Genus' " .
                    "AND UnitName2='$Species' " .
                    "AND UnitName3='$Subspecies' " .
                    "AND TaxonRank=230";

            $SubspeciesSet = $Database->Execute($SubspeciesString);

            if (!$SubspeciesSet->FetchRow()) {
//				$ID=TBL_TaxonUnits::Insert($Database,$TSN);

                $ID = TBL_TaxonUnits::Insert($Database, $TSN, $KingdomID, $Genus, $Species, "ssp.", $Subspecies, null, null, $InsertlogID, 230, $ParentTSN, $RequiredParentTSN);

                $TSN-=1;

                $ParentTSN = $TSN;
            }

            if ($Variety != "") { // &&$KingdomID!=5&&$KingdomID!=1) // jjg not sure why this was limited
                $VarietyString = "SELECT * " .
                        "FROM TBL_TaxonUnits " .
                        "WHERE UnitName1='$Genus' " .
                        "AND UnitName2='$Species' " .
                        "AND UnitName3='$Subspecies' " .
                        "AND UnitName4='$Variety' " .
                        "AND TaxonRank=240";

                $VarietySet = $Database->Execute($VarietyString);

                if (!$VarietySet->FetchRow()) {
                    $ID = TBL_TaxonUnits::Insert($Database, $TSN, $KingdomID, $Genus, $Species, "ssp.", $Subspecies, "var.", $Variety, $InsertlogID, 240, $ParentTSN, $RequiredParentTSN);

                    $TSN-=1;
                }
            }
        } else { // add an entry where the variety is the 3rd name (Genus species var. variety)
//    		DebugWriteln("Hello");
            if ($Variety != "") { // &&$KingdomID!=5&&$KingdomID!=1) // jjg not sure why this was limited
                $VarietyString = "SELECT * " .
                        "FROM TBL_TaxonUnits " .
                        "WHERE UnitName1='$Genus' " .
                        "AND UnitName2='$Species' " .
                        "AND UnitName3='$Variety' " .
                        "AND TaxonRank=240";

                $VarietySet = $Database->Execute($VarietyString);

                if (!$VarietySet->FetchRow()) {
                    $ID = TBL_TaxonUnits::Insert($Database, $TSN, $KingdomID, $Genus, $Species, "var.", $Variety, null, null, $InsertlogID, 240, $ParentTSN, $RequiredParentTSN);

                    $TSN-=1;
                }
            }
        }

        return ($ID);
    }

    public static function UpdateCommonName($Database, $TSN, $CommonNames) {
        //DebugWriteln(count($CommonNames));
        /*  	for ($i=0;$i<count($CommonNames);$i++)
          {
          $ID=1;

          $ExecString="EXEC insert_TBL_TaxonUnits ".SafeInt($TSN);

          //		DebugWriteln("ExecString=".$ExecString);

          $ID=$Database->DoInsert($ExecString);

          $UpdateString="UPDATE TBL_Venaculars ".
          "SET Name='$CommonNames[$i]', ";

          if($TSN<0)
          $UpdateString.="Source=2, ";
          else
          $UpdateString.="Source=1, ";

          $UpdateString.="LanguageID=1, InsertLogID=1 ";

          $UpdateString.="WHERE TSN=$TSN";

          //DebugWriteln("String=$UpdateString");

          $Database->Execute($String);
          }
         */
    }

    public static function MoveChildren($Database, $OldParentTSN, $NewParentTSN, $ModifyDatabase = 1) {
        // Returns the number of children moved, or -1 if the
        // new parent TSN does not exist.
        $MoveCount = -1;

        // Verify that the new parent TSN exists
        $SelectString = "SELECT count(*) " .
                "FROM TBL_TaxonUnits " .
                "WHERE TSN=$NewParentTSN";
        //DebugWriteln("MoveChildren: $SelectString");
        $Set = $Database->Execute($SelectString);
        $Count = $Set->Field(1);

        if ($Count == 1) {
            $MoveCount = 0;
            $SelectString = "SELECT TSN, ParentTSN, RequiredParentTSN " .
                    "FROM TBL_TaxonUnits " .
                    "WHERE ParentTSN=$OldParentTSN " .
                    "OR RequiredParentTSN=$OldParentTSN ";

            $Set = $Database->Execute($SelectString);

            // For each taxon with either ParentTSN or RequiredParentTSN
            // equal to the old parent TSN
            while ($Set->FetchRow()) {
                $ChildTSN = $Set->Field("TSN");
                $ChildParentTSN = $Set->Field("ParentTSN");
                $ChildRequiredParentTSN = $Set->Field("RequiredParentTSN");

                // Set the ParentTSN to the new value, and set RequiredParentTSN
                // to NULL.
                $UpdateString = "UPDATE TBL_TaxonUnits SET " .
                        "ParentTSN=$NewParentTSN, " .
                        "RequiredParentTSN=NULL " .
                        "WHERE TSN=$ChildTSN ";
                //DebugWriteln("\tMoveChildren: UpdateString=$UpdateString");
                if ($ModifyDatabase)
                    $Database->Execute($UpdateString);
                $MoveCount++;

                // Update the RequiredParentTSN field
                $RequiredParentTSN = (int) TBL_TaxonUnits::GetRequiredParentTSN(
                                $Database, $ChildTSN);
                $UpdateString = "UPDATE TBL_TaxonUnits SET " .
                        "RequiredParentTSN=$RequiredParentTSN " .
                        "WHERE TSN=$ChildTSN ";
                //DebugWriteln("\tMoveChildren: UpdateString=$UpdateString");
                if ($ModifyDatabase)
                    $Database->Execute($UpdateString);
            }

            // Get the Flatten fields for the new parent, so we can
            // update them if needed
            $ParentSet = TBL_TaxonUnits::GetSetFromTSN($Database, $NewParentTSN);
            $ParentKingdomID = $ParentSet->Field("KingdomID");
            $ParentPhylumTSN = $ParentSet->Field("PhylumTSN");
            $ParentClassTSN = $ParentSet->Field("ClassTSN");
            $ParentOrderTSN = $ParentSet->Field("OrderTSN");
            $ParentFamilyTSN = $ParentSet->Field("FamilyTSN");
            $ParentGenusTSN = $ParentSet->Field("GenusTSN");

            // Change the flatten fields, if needed
            TBL_TaxonUnits::ChangeFlattenFields($Database, $NewParentTSN, $ParentKingdomID, $ParentPhylumTSN, $ParentClassTSN, $ParentOrderTSN, $ParentFamilyTSN, $ParentGenusTSN, $ModifyDatabase);
        }
        return($MoveCount);
    }

    public static function Changeparent($Database, $TSN, $NewParentTSN, $ModifyDatabase = 1) {
        // Returns 1 for success, 0 for failure
        $SuccessCode = 0;

        // Verify that the new parent TSN exists
        $SelectString = "SELECT count(*) " .
                "FROM TBL_TaxonUnits " .
                "WHERE TSN=$NewParentTSN";
        //DebugWriteln("Changeparent: SelectString=$SelectString");
        $Set = $Database->Execute($SelectString);
        $PCount = $Set->Field(1);

        // Verify that the child TSN exists
        $SelectString = "SELECT count(*) " .
                "FROM TBL_TaxonUnits " .
                "WHERE TSN=$TSN";
        //DebugWriteln("Changeparent: SelectString=$SelectString");
        $Set = $Database->Execute($SelectString);
        $CCount = $Set->Field(1);

        if ($PCount == 1 && $CCount == 1) {
            // Change the ParentTSN field
            $UpdateString = "UPDATE TBL_TaxonUnits SET " .
                    "ParentTSN=$NewParentTSN, " .
                    "RequiredParentTSN=NULL " .
                    "WHERE TSN=$TSN ";
            //DebugWriteln("Changeparent: UpdateString=$UpdateString");
            if ($ModifyDatabase)
                $Database->Execute($UpdateString);

            // Change the RequiredParentTSN field
            $RequiredParentTSN = (int) TBL_TaxonUnits::GetRequiredParentTSN($Database, $TSN);
            $UpdateString = "UPDATE TBL_TaxonUnits SET " .
                    "RequiredParentTSN=$RequiredParentTSN " .
                    "WHERE TSN=$TSN ";
            //DebugWriteln("Changeparent: UpdateString=$UpdateString");
            if ($ModifyDatabase)
                $Database->Execute($UpdateString);

            // Update the flatten fields, if needed
            $ParentSet = TBL_TaxonUnits::GetSetFromTSN($Database, $NewParentTSN);
            $ParentKingdomID = $ParentSet->Field("KingdomID");
            $ParentPhylumTSN = $ParentSet->Field("PhylumTSN");
            $ParentClassTSN = $ParentSet->Field("ClassTSN");
            $ParentOrderTSN = $ParentSet->Field("OrderTSN");
            $ParentFamilyTSN = $ParentSet->Field("FamilyTSN");
            $ParentGenusTSN = $ParentSet->Field("GenusTSN");
            TBL_TaxonUnits::ChangeFlattenFields($Database, $TSN, $ParentKingdomID, $ParentPhylumTSN, $ParentClassTSN, $ParentOrderTSN, $ParentFamilyTSN, $ParentGenusTSN, $ModifyDatabase);

            $SuccessCode = 1;
        }
        return($SuccessCode);
    }

    public static function ChangeKingdomID($Database, $TSN, $NewKingdomID, $ModifyDatabase = 1) {
        // Recursively change the kingdom ID of this TSN and all its children
        // NO LONGER USED - replaced by ChangeFlattenFields
        $TaxonSet = TBL_TaxonUnits::GetSetFromTSN($Database, $TSN);
        $CurrentKingdomID = $TaxonSet->Field("KingdomID");
        //DebugWriteln("ChangeKingdomID: changing $TSN from $CurrentKingdomID to $NewKingdomID");
        // Make sure we have work to do
        if ($CurrentKingdomID == $NewKingdomID)
            return(0);

        $UpdateString = "UPDATE TBL_TaxonUnits SET " .
                "KingdomID=$NewKingdomID " .
                "WHERE TSN=$TSN ";
        //DebugWriteln("ChangeKingdomID: UpdateString=$UpdateString");
        if ($ModifyDatabase)
            $Database->Execute($UpdateString);
        $TSNsProcessed = 1;

        // Now check invalid taxons that are synonyms of this TSN
        $SelectString = "SELECT * FROM TBL_TaxonUnits " .
                "WHERE TSN IN " .
                "(SELECT SynonymTSN FROM TBL_Synonyms " .
                "WHERE AuthenticTSN=$TSN)";
        $SynonymSet = $Database->Execute($SelectString);
        while ($SynonymSet->FetchRow()) {
            $SynonymTSN = $SynonymSet->Field("TSN");
            $SynonymKingdomID = $SynonymSet->Field("KingdomID");

            if ($SynonymKingdomID != $NewKingdomID) {
                $UpdateString = "UPDATE TBL_TaxonUnits SET " .
                        "KingdomID=$NewKingdomID " .
                        "WHERE TSN=$SynonymTSN ";
                //DebugWriteln("ChangeKingdomID: UpdateString=$UpdateString");
                if ($ModifyDatabase)
                    $Database->Execute($UpdateString);
                $TSNsProcessed++;
            }
        }

        // Now check all the children of this TSN
        $SelectString = "SELECT * FROM TBL_TaxonUnits " .
                "WHERE ParentTSN=$TSN";
        $ChildSet = $Database->Execute($SelectString);
        while ($ChildSet->FetchRow()) {
            $ChildTSN = $ChildSet->Field("TSN");
            $TSNsProcessed+=TBL_TaxonUnits::ChangeKingdomID(
                            $Database, $ChildTSN, $NewKingdomID, $ModifyDatabase);
        }

        return($TSNsProcessed);
    }

    public static function SetFlattenFields($Database, $TSN, $ModifyDatabase = 1) {
        // Set the flatten fields for this TSN only
        // Get the current values for this TSN
        $TaxonSet = TBL_TaxonUnits::GetSetFromTSN($Database, $TSN);
        $ParentTSN = $TaxonSet->Field("ParentTSN");
        $TaxonRank = $TaxonSet->Field("TaxonRank");
        $CurrentKingdomID = $TaxonSet->Field("KingdomID");
        $CurrentPhylumTSN = $TaxonSet->Field("PhylumTSN");
        if (!$CurrentPhylumTSN)
            $CurrentPhylumTSN = 0;
        $CurrentClassTSN = $TaxonSet->Field("ClassTSN");
        if (!$CurrentClassTSN)
            $CurrentClassTSN = 0;
        $CurrentOrderTSN = $TaxonSet->Field("OrderTSN");
        if (!$CurrentOrderTSN)
            $CurrentOrderTSN = 0;
        $CurrentFamilyTSN = $TaxonSet->Field("FamilyTSN");
        if (!$CurrentFamilyTSN)
            $CurrentFamilyTSN = 0;
        $CurrentGenusTSN = $TaxonSet->Field("GenusTSN");
        if (!$CurrentGenusTSN)
            $CurrentGenusTSN = 0;

        // Get the new values from this TSN's parents
        $ParentSet = TBL_TaxonUnits::GetSetFromTSN($Database, $ParentTSN);
        $NewKingdomID = $ParentSet->Field("KingdomID");
        $NewPhylumTSN = $ParentSet->Field("PhylumTSN");
        if (!$NewPhylumTSN)
            $NewPhylumTSN = 0;
        $NewClassTSN = $ParentSet->Field("ClassTSN");
        if (!$NewClassTSN)
            $NewClassTSN = 0;
        $NewOrderTSN = $ParentSet->Field("OrderTSN");
        if (!$NewOrderTSN)
            $NewOrderTSN = 0;
        $NewFamilyTSN = $ParentSet->Field("FamilyTSN");
        if (!$NewFamilyTSN)
            $NewFamilyTSN = 0;
        $NewGenusTSN = $ParentSet->Field("GenusTSN");
        if (!$NewGenusTSN)
            $NewGenusTSN = 0;

        // Check if the current TSN is a required rank;
        // if so set the appropriate flatten fields
        if ($TaxonRank == RANK_PHYLUM) {
            $NewPhylumTSN = $TSN;
            $NewClassTSN = 0;
            $NewOrderTSN = 0;
            $NewFamilyTSN = 0;
            $NewGenusTSN = 0;
        } elseif ($TaxonRank == RANK_CLASS) {
            $NewClassTSN = $TSN;
            $NewOrderTSN = 0;
            $NewFamilyTSN = 0;
            $NewGenusTSN = 0;
        } elseif ($TaxonRank == RANK_ORDER) {
            $NewOrderTSN = $TSN;
            $NewFamilyTSN = 0;
            $NewGenusTSN = 0;
        } elseif ($TaxonRank == RANK_FAMILY) {
            $NewFamilyTSN = $TSN;
            $NewGenusTSN = 0;
        } elseif ($TaxonRank == RANK_GENUS) {
            $NewGenusTSN = $TSN;
        }

//		DebugWriteln("Changing $TSN from $CurrentKingdomID,$CurrentPhylumTSN,".
//				"$CurrentClassTSN,$CurrentOrderTSN,$CurrentFamilyTSN,$CurrentGenusTSN ".
//				"to $NewKingdomID,$NewPhylumTSN,$NewClassTSN,$NewOrderTSN,".
//				"$NewFamilyTSN,$NewGenusTSN");
        // Make sure we have work to do
        if ($CurrentKingdomID == $NewKingdomID &&
                $CurrentPhylumTSN == $NewPhylumTSN &&
                $CurrentClassTSN == $NewClassTSN &&
                $CurrentOrderTSN == $NewOrderTSN &&
                $CurrentFamilyTSN == $NewFamilyTSN &&
                $CurrentGenusTSN == $NewGenusTSN)
            return(0);

        $UpdateString = "UPDATE TBL_TaxonUnits SET " .
                "KingdomID=$NewKingdomID, " .
                "PhylumTSN=$NewPhylumTSN, " .
                "ClassTSN =$NewClassTSN,  " .
                "OrderTSN =$NewOrderTSN,  " .
                "FamilyTSN=$NewFamilyTSN, " .
                "GenusTSN =$NewGenusTSN " .
                "WHERE TSN=$TSN ";
//		DebugWriteln("SetFlattenFields: UpdateString=$UpdateString");
        if ($ModifyDatabase)
            $Database->Execute($UpdateString);

        return(1);
    }

    public static function ChangeFlattenFields($Database, $TSN, $KingdomID, $PhylumTSN, $ClassTSN, $OrderTSN, $FamilyTSN, $GenusTSN, $ModifyDatabase = 1) {
        // Recursively change the kingdom ID, PhylumTSN, etc. fields
        // of this TSN and all its children
        $TaxonSet = TBL_TaxonUnits::GetSetFromTSN($Database, $TSN);
        $TaxonRank = $TaxonSet->Field("TaxonRank");
//if ($TaxonRank>RANK_PHYLUM) return(0);
        $CurrentKingdomID = $TaxonSet->Field("KingdomID");
        $CurrentPhylumTSN = $TaxonSet->Field("PhylumTSN");
        if (!$CurrentPhylumTSN)
            $CurrentPhylumTSN = 0;
        $CurrentClassTSN = $TaxonSet->Field("ClassTSN");
        if (!$CurrentClassTSN)
            $CurrentClassTSN = 0;
        $CurrentOrderTSN = $TaxonSet->Field("OrderTSN");
        if (!$CurrentOrderTSN)
            $CurrentOrderTSN = 0;
        $CurrentFamilyTSN = $TaxonSet->Field("FamilyTSN");
        if (!$CurrentFamilyTSN)
            $CurrentFamilyTSN = 0;
        $CurrentGenusTSN = $TaxonSet->Field("GenusTSN");
        if (!$CurrentGenusTSN)
            $CurrentGenusTSN = 0;

        // Check if the current TSN is a required rank;
        // if so set the appropriate flatten fields
        if ($TaxonRank == RANK_PHYLUM) {
            $PhylumTSN = $TSN;
            $ClassTSN = 0;
            $OrderTSN = 0;
            $FamilyTSN = 0;
            $GenusTSN = 0;
        } elseif ($TaxonRank == RANK_CLASS) {
            $ClassTSN = $TSN;
            $OrderTSN = 0;
            $FamilyTSN = 0;
            $GenusTSN = 0;
        } elseif ($TaxonRank == RANK_ORDER) {
            $OrderTSN = $TSN;
            $FamilyTSN = 0;
            $GenusTSN = 0;
        } elseif ($TaxonRank == RANK_FAMILY) {
            $FamilyTSN = $TSN;
            $GenusTSN = 0;
        } elseif ($TaxonRank == RANK_GENUS) {
            $GenusTSN = $TSN;
        }

        // Make sure we have work to do
        if ($CurrentKingdomID == $KingdomID &&
                $CurrentPhylumTSN == $PhylumTSN &&
                $CurrentClassTSN == $ClassTSN &&
                $CurrentOrderTSN == $OrderTSN &&
                $CurrentFamilyTSN == $FamilyTSN &&
                $CurrentGenusTSN == $GenusTSN)
            return(0);

//		DebugWriteln("ChangeFlattenFields: changing $TSN ($TaxonRank) from ".
//				"$CurrentKingdomID,$CurrentPhylumTSN,$CurrentClassTSN,".
//					"$CurrentOrderTSN,$CurrentFamilyTSN,$CurrentGenusTSN to ".
//				"$KingdomID,$PhylumTSN,$ClassTSN,$OrderTSN,$FamilyTSN,$GenusTSN");

        $UpdateString = "UPDATE TBL_TaxonUnits SET " .
                "KingdomID=$KingdomID, " .
                "PhylumTSN=$PhylumTSN, " .
                "ClassTSN =$ClassTSN,  " .
                "OrderTSN =$OrderTSN,  " .
                "FamilyTSN=$FamilyTSN, " .
                "GenusTSN =$GenusTSN " .
                "WHERE TSN=$TSN ";
//		DebugWriteln("ChangeFlattenFields: UpdateString=$UpdateString");
        if ($ModifyDatabase)
            $Database->Execute($UpdateString);
        $TSNsProcessed = 1;

        // Check invalid taxons that are synonyms of this TSN
        $SelectString = "SELECT * FROM TBL_TaxonUnits " .
                "WHERE TSN IN " .
                "(SELECT SynonymTSN FROM TBL_Synonyms " .
                "WHERE AuthenticTSN=$TSN)";
        $SynonymSet = $Database->Execute($SelectString);
        while ($SynonymSet->FetchRow()) {
            $SynonymTSN = $SynonymSet->Field("TSN");
            $SynonymKingdomID = $SynonymSet->Field("KingdomID");
            if (!$SynonymKingdomID)
                $SynonymKingdomID = 0;
            $SynonymPhylumTSN = $SynonymSet->Field("PhylumTSN");
            if (!$SynonymPhylumTSN)
                $SynonymPhylumTSN = 0;
            $SynonymClassTSN = $SynonymSet->Field("ClassTSN");
            if (!$SynonymClassTSN)
                $SynonymClassTSN = 0;
            $SynonymOrderTSN = $SynonymSet->Field("OrderTSN");
            if (!$SynonymOrderTSN)
                $SynonymOrderTSN = 0;
            $SynonymFamilyTSN = $SynonymSet->Field("FamilyTSN");
            if (!$SynonymFamilyTSN)
                $SynonymFamilyTSN = 0;
            $SynonymGenusTSN = $SynonymSet->Field("GenusTSN");
            if (!$SynonymGenusTSN)
                $SynonymGenusTSN = 0;

            if ($SynonymKingdomID != $KingdomID ||
                    $SynonymPhylumTSN != $PhylumTSN ||
                    $SynonymClassTSN != $ClassTSN ||
                    $SynonymOrderTSN != $OrderTSN ||
                    $SynonymFamilyTSN != $FamilyTSN ||
                    $SynonymGenusTSN != $GenusTSN) {
                $UpdateString = "UPDATE TBL_TaxonUnits SET " .
                        "KingdomID=$KingdomID, " .
                        "PhylumTSN=$PhylumTSN, " .
                        "ClassTSN =$ClassTSN,  " .
                        "OrderTSN =$OrderTSN,  " .
                        "FamilyTSN=$FamilyTSN, " .
                        "GenusTSN =$GenusTSN  " .
                        "WHERE TSN=$SynonymTSN ";
//				DebugWriteln("ChangeFlattenFields: Synonym UpdateString=$UpdateString");
                if ($ModifyDatabase)
                    $Database->Execute($UpdateString);
                $TSNsProcessed++;
            }
        }

        // Now recursively check all the children of this TSN
        $SelectString = "SELECT * FROM TBL_TaxonUnits " .
                "WHERE ParentTSN=$TSN";
        $ChildSet = $Database->Execute($SelectString);
        while ($ChildSet->FetchRow()) {
            $ChildTSN = $ChildSet->Field("TSN");
            $TSNsProcessed+=TBL_TaxonUnits::ChangeFlattenFields(
                            $Database, $ChildTSN, $KingdomID, $PhylumTSN, $ClassTSN, $OrderTSN, $FamilyTSN, $GenusTSN, $ModifyDatabase);
        }

        return($TSNsProcessed);
    }

    function MoveTSNForeignKeyReferences($Database, $NewTSN, $OldTSN, $ModifyDatabase = 1) {
        // The OldTSN is no longer valid or is being deleted. Move all foreign
        // key references to the new TSN
        // Return the number of records that have been changed
        $ChangedRecordCount = 0;

        // Verify that the new parent TSN exists
        $SelectString = "SELECT count(*) " .
                "FROM TBL_TaxonUnits " .
                "WHERE TSN=$NewTSN";
        //DebugWriteln("MoveTSNForeignKeyReferences: SelectString=$SelectString");
        $Set = $Database->Execute($SelectString);
        $Count = $Set->Field(1);
        if ($Count != 1) {
            //DebugWriteln("MoveTSNForeignKeyReferences: ERROR - TSN $NewTSN does not exist in TBL_TaxonUnits.");
        } else {
            // Get the table ID for TBL_TaxonUnits
            $SelectString = "SELECT ID " .
                    "FROM LKU_DatabaseTables " .
                    "WHERE (Name = 'TBL_TaxonUnits')";
            $Set = $Database->Execute("$SelectString");
            $TBL_TaxonUnitsID = $Set->Field("ID");

            // Get all the foreign key references to TBL_TaxonUnits.TSN
            $DatabaseFieldSet = LKU_DatabaseFields::GetReferenceSet($Database, $TBL_TaxonUnitsID);

            // For each foreign key on TSN, redirect it to the new TSN
            $TableCount = 0;
            while ($DatabaseFieldSet->FetchRow()) {
                // Get the name of the referencing table
                $TableName = $DatabaseFieldSet->Field("TableName");

                // Get the name of the referencing field
                $FieldName = $DatabaseFieldSet->Field("FieldName");
                //DebugWriteln("MoveTSNForeignKeyReferences: Inspecting table $TableName Field $FieldName");
                // If the old TSN is a synonym for something, leave that alone
                if (!($TableName == "TBL_Synonyms" and $FieldName == "SynonymTSN" )) {
                    // Just for auditing - how many records will be affected?
                    $SelectString = "SELECT count(*) " .
                            "FROM $TableName " .
                            "WHERE $FieldName = $OldTSN";
                    $TempSet = $Database->Execute($SelectString);
                    $RecordCount = $TempSet->Field(1);
                    $ChangedRecordCount+=$RecordCount;
                    //DebugWriteln("MoveTSNForeignKeyReferences: Change will impact $RecordCount records");
                    // Move the reference to the new TSN
                    if ($RecordCount > 0) {
                        $UpdateString = "UPDATE $TableName SET " .
                                "$FieldName=$NewTSN " .
                                "WHERE $FieldName=$OldTSN ";
                        //DebugWriteln("\nMoveTSNForeignKeyReferences: Change will impact $RecordCount records");
                        //DebugWriteln("MoveTSNForeignKeyReferences: Table $TableName Field $FieldName ".
                        //		"old TSN=$OldTSN, new TSN=$NewTSN");
                        //DebugWriteln("MoveTSNForeignKeyReferences: UpdateString=$UpdateString");
                        if ($ModifyDatabase)
                            $Database->Execute($UpdateString);
                    }

                    $TableCount++;
                }
            }
        }
        return($ChangedRecordCount);
    }

    //***************************************************************************************
    //	Management Functions
    //***************************************************************************************
    public static function UpdateTaxonUnitUsedBit($Database) {
        //
        // Updates the "Used" bit in the database
        // This is only availabel from Taxon_Options.php in the admin//

        $UpdateString = "UPDATE TBL_TaxonUnits " .
                "Set Used=1 " .
                "WHERE TSN IN (SELECT DISTINCT TSN " .
                "FROM TBL_OrganismInfos)";

        //	DebugWriteln("UpdateString=$UpdateString");

        $Database->Execute($UpdateString);

        // go up the taxon tree marking parents as used

        for ($i = 0; $i < 29; $i++) {
            $UpdateString = "UPDATE TBL_TaxonUnits " . // update the TBL_TaxonUnits
                    "SET Used=1 " .
                    "WHERE TSN IN (" .
                    "SELECT ParentTSN AS TSN " . // that have a TSN from a child that is used
                    "FROM TBL_TaxonUnits " .
                    "WHERE (ParentTSN IS NOT NULL) AND (Used=1)) " .
                    "AND (Used=0)";

            //		DebugWriteln("UpdateString=$UpdateString");

            $Database->Execute($UpdateString);
        }
    }

    //***************************************************************************************
    public static function UpdateRequiredParents($Database1, $Database2) {
        //
        //	Updates the TaxonUnits with the correct required parents
        //	This should be run after the ITIS database uploaded
        //
	// Only called by Taxon_Options.php in admin//

        $SelectString = "SELECT TSN,TaxonRank " .
                "FROM TBL_TaxonUnits " .
                "WHERE ((RequiredParentTSN<=0) OR (RequiredParentTSN is NULL)) " .
                "AND ((ParentTSN>0) AND (ParentTSN IS NOT NULL)) " .
                "ORDER BY TaxonRank";

//		DebugWriteln("SelectString=$SelectString");

        $RecordSet = $Database1->Execute($SelectString);

        $Count = 0;
        while (($RecordSet->FetchRow()) && ($Count < 10000000)) {
            $TSN = $RecordSet->Field(1);

//			DebugWriteln("Count=$Count, TSN=$TSN");

            $ParentTSN = (int) TBL_TaxonUnits::GetRequiredParentTSN($Database2, $TSN);

            $UpdateString = "UPDATE TBL_TaxonUnits " .
                    "SET RequiredParentTSN=$ParentTSN " .
                    "WHERE TSN=$TSN";

//			DebugWriteln("UpdateString=$UpdateString");

            $Database2->Execute($UpdateString);

            $Count++;
        }
        return($Count);
    }

    //***************************************************************************************
    // These really are utilities but they are associated with ITIS
    //***************************************************************************************
    public static function GetITISLink($TSN) {
        if ($TSN < 0) {
            $Link = "No TSN available yet from " . // No TSN has been assigned yet by
                    GetLink("http://www.itis.usda.gov", "ITIS", null, "_top", null);
        } else {
//			using GetLink params in normal manner does not work for ITIS Search - pdd; external link anyway so make exception here and force it to direct ITIS link -gjn
            /*
              $Link=GetLink("http://www.itis.usda.gov/servlet/SingleRpt/SingleRpt?".
              "search_topic=TSN&search_kingdom=every&search_span=exactly_for&search_value=".
              $TSN."&categories=All&source=html&search_credRating=All'",$TSN,null,"_info",null);
             */

            $Link = "<a href='http://www.itis.gov/servlet/SingleRpt/SingleRpt?" .
                    "search_topic=TSN&search_value=$TSN&search_kingdom=every" .
                    "&search_span=exactly_for&categories=All&source=html&search_credRating=All' target='_info'>$TSN</a>";
        }


        return($Link);
    }

    /*
     * Returns a select string to obtain the unaccepted TSNs.  This is used both to
     * find unaccepted TSNs and to see if a knonw TSN is accepted in a inner query.
     */

    function GetUnacceptedSelect() {
        $UnacceptedSelect = "SELECT TSN " .
                "FROM TBL_TaxonUnits " .
                "WHERE ((TaxonRank<>10 AND RequiredParentTSN IS NULL) " . // not accepted (no parent and not a kingdom)
                "OR (TaxonRank<>10 AND RequiredParentTSN=0)) ";
//				"OR TSN IN (SELECT SynonymTSN FROM TBL_Synonyms) "; // a synonym

        return($UnacceptedSelect);
    }

    function StripQualifiersInScientificName($ScientificName, $IncludeRegExp = 0) {

        // It should be
        // noted that for ID Source, this function will be called for Terms  
        // as well since some Terms are actually of Type "Scientific Name."
        // See defect #2443 for more info.  -LMM 7-19-12


        $Qualifiers = array("pathovar", "subvar", "variety", "cultivar", "subspecies", "var",
            "subsp", "subf", "x", "X",
            "ssp", "ff spp", "f sp", "f", "spp", "sp", "pv", "cv");


        // strip periods, replace with space
        $NewScientificName1 = str_replace(".", " ", $ScientificName);

        // remove multiple spaces
        $NewScientificName = preg_replace('/\s+/', ' ', $NewScientificName1);

        // remove specified qualifiers
        // this method does not work with, e.g., "Cerastium alpinum ssp. alpinum var. alpinum"
        // it seems too complicated having to rely on the order of the Qualifiers array
        // when you use str_ireplace - LMM 7-17-13
        //$NewScientificName2=str_ireplace($Qualifiers," ",$NewScientificName1);

        $SciNameParts = explode(" ", $NewScientificName);
        $StrippedSciNameParts = array_diff($SciNameParts, $Qualifiers);
        $NewScientificName = implode(" ", $StrippedSciNameParts);



        // Include regular expression for pattern matchine, if requested
        if ($IncludeRegExp && !($ScientificName == $NewScientificName)) {
            $NewScientificName2 = str_replace($Qualifiers, ".*", $NewScientificName1);
            $NewScientificName = preg_replace('/\s+/', ' ', $NewScientificName2);
        }


        return($NewScientificName);
    }

    public static function IsAPest($Database, $TSN, $GiveDetails = FALSE) {
        // This function tells you if a given TSN has any parents where IDSource_IgnoreBranch is set
        // to false.  Since only higher branches are set, you must check all the way up the tree to 
        // know whether or not the scientific name is considered an ID Source pest.
        // 
        // The default way to use the function will just return a boolean value.
        // If GiveDetails is set to True, the taxonomic hierarchy will be returned.
        // 
        // -LMM 3-6-13

        $IsAPest = TRUE;
        $AtKingdomLevel = FALSE;
        $Details = "";

        // First check to see if the TSN is a synonym.  If so, get AuthenticTSN
        $AuthenticTSN = TBL_Synonyms::GetValidTSNFromSynonym($Database, $TSN);

        if (!empty($AuthenticTSN)) {

            if ($GiveDetails) {
                $SciName = TBL_TaxonUnits::GetScientificNameFromTSN($Database, $TSN);
                $Details .= " $SciName ($TSN) is a Synonym.  Its Valid TSN is $AuthenticTSN<br />";
            }

            $TSN = $AuthenticTSN;
        }

        do {

            $Query = "SELECT RequiredParentTSN, TaxonRank, IDSource_IgnoreBranch
                            FROM TBL_TaxonUnits
                            WHERE TSN=$TSN";

            $Set = $Database->Execute($Query);

            while ($Set->FetchRow()) {
                $ParentTSN = $Set->Field("RequiredParentTSN");
                $IgnoreBranch = $Set->Field("IDSource_IgnoreBranch");
                $TaxonRank = $Set->Field("TaxonRank");
            }

            // just trying to make sure we actually got a record back, if not, tsn is not in database
            if (!empty($TaxonRank)) {
                if ($GiveDetails) {
                    $SciName = TBL_TaxonUnits::GetScientificNameFromTSN($Database, $TSN);
                    $Details .= " $SciName ($TSN)";
                }

                if ($IgnoreBranch) {
                    $IsAPest = FALSE;

                    if ($GiveDetails) {
                        $Details.= " <i>Ignore</i> >";
                    }
                } else {
                    $Details.= " >";
                }

                if ($TaxonRank == "10") {
                    $AtKingdomLevel = TRUE;
                }

                $TSN = $ParentTSN;
            } else { // TSN is not in Database 
                return "TSN $TSN is not in the database<br />";
            }
        } while ($IsAPest && !$AtKingdomLevel);

        if ($GiveDetails) {
            if ($IsAPest) {
                $header = "<b>PEST</b>";
            } else {
                $header = "<b>NOT A PEST</b>";
            }

            // Get rid of trailing ">" 
            $Details = substr($Details, 0, -1);

            return "<p>" . $header . $Details . "</p>";
        } else {
            return $IsAPest;
        }
    }

}

?>