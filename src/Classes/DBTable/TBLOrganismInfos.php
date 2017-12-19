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

    //******************************************************************************
    // Private functions 
    //******************************************************************************
    public static function AddSearchWhereClause(&$SelectString, $Database, $WebsiteID = null, $SearchString = null, $FirstLetter = null, $CommonName = null, $ScientificName = null, $AreaID = null, $OrganismGroup = null, $OrganismType = null) {
        if ($FirstLetter != null) {
            TBL_DBTables::AddWhereClause($SelectString, "Name LIKE '$FirstLetter%'");
        }

        if ($WebsiteID != null) { //
            $Set = TBL_Websites::GetSetFromID($Database, $WebsiteID);

            if ($Set->Field("LimitProjects") == 1) {
                TBL_DBTables::AddWhereClause($SelectString, "ID IN (" .
                        "SELECT OrganismInfoID " .
                        "FROM REL_WebsiteToOrganismInfo " .
                        "WHERE (WebsiteID=$WebsiteID))");
            }
        }

        if (($SearchString !== null) && ($SearchString != "")) {
            // get a query that will return the OrganismInfoIDs with the associated scientific names

            $ScientificNameString = TaxonSearch::GetTSNQueryFromScientificName($SearchString);

            $ScientificNameString = "SELECT OrganismInfoID " .
                    "FROM REL_OrganismInfoToTSN " .
                    "WHERE TSN IN ($ScientificNameString)";

            // get a query that will return the OrganismInfoIDs with the associated common names

            $CommonNameString = TaxonSearch::GetTSNQueryFromCommonName($SearchString);

            $CommonNameString = "SELECT OrganismInfoID " .
                    "FROM REL_OrganismInfoToTSN " .
                    "WHERE TSN IN ($CommonNameString)";

            //

            TBL_DBTables::AddWhereClause($SelectString, "((TBL_OrganismInfos.ID IN ($ScientificNameString)) " .
                    "OR (TBL_OrganismInfos.ID IN ($CommonNameString))" .
                    "OR (Name LIKE '%$SearchString%'))");
        }

        if ($CommonName != null) {
            $String = TaxonSearch::GetTSNQueryFromCommonName($CommonName);

            $String = "SELECT OrganismInfoID " .
                    "FROM REL_OrganismInfoToTSN " .
                    "WHERE TSN IN ($String)";

            TBL_DBTables::AddWhereClause($SelectString, "(TBL_OrganismInfos.ID IN ($String))");
        }

        if ($ScientificName != null) {
            $String = TaxonSearch::GetTSNQueryFromScientificName($ScientificName);

            $String = "SELECT OrganismInfoID " .
                    "FROM REL_OrganismInfoToTSN " .
                    "WHERE TSN IN ($String)";

            TBL_DBTables::AddWhereClause($SelectString, "(TBL_OrganismInfos.ID IN ($String))");
        }

        if ($AreaID > 0) {
            $Set = TBL_Areas::GetSetFromID($Database, $AreaID);

            $AreaSubtypeID = $Set->Field("AreaSubtypeID");

            $AreaIDSelect = null;

            switch ($AreaSubtypeID) {
                case AREA_SUBTYPE_NATION:
                    $SelectLevel3 = // states contained in the nation
                            "SELECT DISTINCT ID " .
                            "FROM TBL_Areas " .
                            "WHERE ParentID=$AreaID ";

                    $SelectLevel2 = // counties contained in the states in the nation
                            "SELECT DISTINCT ID " .
                            "FROM TBL_Areas " .
                            "WHERE ParentID IN ($SelectLevel3) ";

                    $SelectLevel1 = // areas contained by the area (i.e. a plot within a county)
                            "SELECT DISTINCT Area2ID " .
                            "FROM REL_AreaToArea " .
                            "WHERE Area1ID IN ($SelectLevel2) ";

                    $AreaIDSelect.="SELECT ID FROM TBL_Areas WHERE AreaID IN ($SelectLevel1) ";
                    break;
                case AREA_SUBTYPE_STATE:
                    $SelectLevel2 = // counties in the state
                            "SELECT DISTINCT ID " .
                            "FROM TBL_Areas " .
                            "WHERE ParentID=$AreaID ";

                    $SelectLevel1 = // areas contained by the area (i.e. a plot within a county)
                            "SELECT DISTINCT Area2ID " .
                            "FROM REL_AreaToArea " .
                            "WHERE Area1ID IN ($SelectLevel2) ";

                    $AreaIDSelect.="SELECT ID FROM TBL_Areas WHERE AreaID IN ($SelectLevel1) ";
                    break;
                case AREA_SUBTYPE_COUNTY:
                    $SelectLevel1 = // areas contained by the area (i.e. a plot within a county)
                            "SELECT DISTINCT Area2ID " .
                            "FROM REL_AreaToArea " .
                            "WHERE Area1ID=$AreaID ";

                    $AreaIDSelect.="SELECT ID FROM TBL_Areas WHERE AreaID IN ($SelectLevel1) ";
                    break;
                default: // something that is linked to a county?

                    $SelectPlotsInArea = // plots overlapping with the counties that overlap with the area
                            "SELECT DISTINCT Area2ID " .
                            "FROM REL_AreaToArea " .
                            "WHERE Area1ID=$AreaID ";

                    $AreaIDSelect.="SELECT ID FROM TBL_Areas WHERE AreaID IN ($SelectPlotsInArea) ";
                    break;
            }

//			DebugWriteln("AreaIDSelect=$AreaIDSelect");
            // create a query that looks for non-survey areas that contain survey areas with the organism

            $String = "SELECT DISTINCT OrganismInfoID " .
                    "FROM TBL_OrganismData " .
                    "INNER JOIN TBL_Visits ON TBL_OrganismData.VisitID=TBL_Visits.ID " .
                    "WHERE AreaID=$AreaID ";

            if ($AreaIDSelect !== null)
                $String.="OR AreaID IN ($AreaIDSelect)";

//			DebugWriteln("String=$String");

            TBL_DBTables::AddWhereClause($SelectString, "(TBL_OrganismInfos.ID IN ($String))");
        }
        if ($OrganismGroup > 0) {
            switch ((int) $OrganismGroup) {
                case ORGANISM_GROUP_PLANT:
                    TBL_DBTables::AddWhereClause($SelectString, "((OrganismType=" . ORGANISM_TYPE_PLANT_TERRESTRIAL . ") " .
                            "OR (OrganismType=" . ORGANISM_TYPE_PLANT_AQUATIC . "))");
                    break;
                case ORGANISM_GROUP_ANIMAL:
                    TBL_DBTables::AddWhereClause($SelectString, "((OrganismType=" . ORGANISM_TYPE_ANIMAL_VERTIBRATE_TERRESTRIAL . ") " .
                            "OR (OrganismType=" . ORGANISM_TYPE_ANIMAL_INVERTIBRATE_TERRESTRIAL . ") " .
                            "OR (OrganismType=" . ORGANISM_TYPE_ANIMAL_VERTIBRATE_AQUATIC . ") " .
                            "OR (OrganismType=" . ORGANISM_TYPE_ANIMAL_INVERTIBRATE_AQUATIC . "))");
                    break;
                case ORGANISM_GROUP_DISEASE:
                    TBL_DBTables::AddWhereClause($SelectString, "((OrganismType=" . ORGANISM_TYPE_DISEASE_ANIMAL . ") OR (OrganismType=" . ORGANISM_TYPE_DISEASE_PLANT . "))");
                    break;
            }
        }
//		DebugWriteln("OrganismType=$OrganismType");
        if ($OrganismType > 0) {
            TBL_DBTables::AddWhereClause($SelectString, "(OrganismType=$OrganismType)");
        }

//		DebugWriteln("SelectString=$SelectString");
    }

    //**********************************************************************************
    // TBL_DBTables functions
    //**********************************************************************************
    public static function GetFieldValue($Database, $FieldName, $ID, $Default = 0) {
        $Result = TBL_DBTables::GetFieldValue($Database, "TBL_OrganismInfos", $FieldName, $ID, $Default);

        return($Result);
    }

    public static function SetFieldValue($Database, $FieldName, $ID, $Value) {
        TBL_DBTables::SetFieldValue($Database, "TBL_OrganismInfos", $FieldName, $ID, $Value);
    }

    //**********************************************************************************
    // Basic database functions
    //**********************************************************************************

    public static function GetSetFromID($dbConn, $ID = "", $Name = "") {
        $SelectString = "SELECT * " .
                "FROM TBL_OrganismInfos ";

        if ($Name != "")
            TBLDBTables::AddWhereClause($SelectString, "Name=:Name'$Name'");

        if ($ID != "")
            TBLDBTables::AddWhereClause($SelectString, "ID=:ID");

        $stmt = $dbConn->prepare($SelectString);
        if ($Name != "")
            $stmt->bindValue("Name", $Name);
        if ($ID != "")
            $stmt->bindValue("ID", $ID);
        $stmt->execute();
        $Set = $stmt->Fetch();

        return($Set);
    }

    public static function GetTotalRows($Database, $WebsiteID = null, $SearchString = null, $FirstLetter = null, $CommonName = null, $ScientificName = null, $AreaID = null, $OrganismGroup = null, $OrganismType = null) {
        // get the query for $CurrentRow+$NumRows rows in reversed order

        $SelectString = "SELECT COUNT(*) " .
                "FROM TBL_OrganismInfos ";

        $SelectString.=" WHERE (Name IS NOT NULL) AND (Name <> ' ')";

        TBL_OrganismInfos::AddSearchWhereClause($SelectString, $Database, $WebsiteID, $SearchString, $FirstLetter, $CommonName, $ScientificName, $AreaID, $OrganismGroup, $OrganismType);

//		DebugWriteln("SelectString=$SelectString");

        $Set = $Database->Execute($SelectString);

        return($Set->Field(1));
    }

    public static function GetRows($Database, $CurrentRow, $NumRows, $TotalRows, $OrderByField, $DescendingFlag, $Fields = null, $WebsiteID = null, $SearchString = null, $FirstLetter = null, $CommonName = null, $ScientificName = null, $AreaID = null, $OrganismGroup = null, $OrganismType = null) {
        if ($CurrentRow >= $TotalRows) {
            $LastPage = (int) ((($TotalRows + $NumRows - 1) / $NumRows) - 1); // from PageSettings

            $CurrentRow = $LastPage * $NumRows; // go to the last page
        }

//		DebugWriteln("CurrentRow=$CurrentRow");
        // get the query for $CurrentRow+$NumRows rows in reversed order
//   		$SelectString1="SELECT TOP ".($TotalRows-$CurrentRow)." TBL_OrganismInfos.ID AS ID,Description,".
//   			"Name,(UnitName1 + ' ' + UnitName2 + ' ' + UnitName3 + ' ' + UnitName4) AS ScientificName ";
//   		$SelectString1="SELECT TOP ".($TotalRows-$CurrentRow)." TBL_OrganismInfos.ID AS ID,".
//   			"Name,(UnitName1 + ' ' + UnitName2 + ' ' + UnitName3 + ' ' + UnitName4) AS ScientificName ";

        $SelectString1 = "SELECT TOP " . ($TotalRows - $CurrentRow) . " TBL_OrganismInfos.ID " .
                "FROM TBL_OrganismInfos ";
//				"INNER JOIN TBL_TaxonUnits ON TBL_TaxonUnits.TSN=TBL_OrganismInfos.TSN ";

        $SelectString1.=" WHERE (Name IS NOT NULL) AND (Name <> ' ')";

        TBL_OrganismInfos::AddSearchWhereClause($SelectString1, $Database, $WebsiteID, $SearchString, $FirstLetter, $CommonName, $ScientificName, $AreaID, $OrganismGroup, $OrganismType);

        TBL_DBTables::AddOrderByClause($SelectString1, $OrderByField, !$DescendingFlag); // query the rows in the opposite order of what the user wants
        // create the query that gets the top $NumRows and reverses the order to make it the way the user wants

        $SelectString = TBL_DBTables::GetSelectClause(0, $NumRows, $Fields); // returns "*" if no fields
//  		$SelectString="SELECT TOP $CurrentRow TBL_OrganismInfos.ID AS ID,Description,".
        //  			"Name,(UnitName1 + ' ' + UnitName2 + ' ' + UnitName3 + ' ' + UnitName4) AS ScientificName ";

        $SelectString.=
                "FROM TBL_OrganismInfos " .
//				"INNER JOIN TBL_TaxonUnits ON TBL_TaxonUnits.TSN=TBL_OrganismInfos.TSN ".
                "WHERE TBL_OrganismInfos.ID IN ($SelectString1) ";

//    	$SelectString.=" ORDER BY ScientificName";

        TBL_DBTables::AddOrderByClause($SelectString, $OrderByField, $DescendingFlag); // query the rows in the opposite order of what the user wants
        //DebugWriteln("SelectString1=$SelectString1");
        //DebugWriteln("SelectString=$SelectString");

        $Set = $Database->Execute($SelectString);

        return($Set);
    }

    public static function Insert($Database, $TSN = null, $Name = NOT_SPECIFIED) {
        $ID = 0;

        // go try to find a venacular for a default name

        if ($Name == NOT_SPECIFIED) { // go create a reasonable name for it...
            $CommonNamesSet = TBL_Venaculars::GetSet($Database, $TSN);

            if ($CommonNamesSet->FetchRow()) {
                $Name = $CommonNamesSet->Field("Name"); //get name of first record in set of common namkes associated with this TSN
            } else { // go get UnitName1 and/or Unitname 2 from TBL_TaxonUnits fro this TSN
                $Name = TBL_TaxonUnits::GetScientificNameFromTSN($Database, $TSN, false);
            }
        }

//		DebugWriteln("OrganismInfoTypeID=".$OrganismInfoTypeID);

        $ExecString = "EXEC insert_TBL_OrganismInfos '$Name'";

//		DebugWriteln("ExecString=".$ExecString);

        $ID = $Database->DoInsert($ExecString);

        // 

        if ($TSN != 0) {
            REL_OrganismInfoToTSN::Insert($Database, $ID, $TSN);

            // also have to update the used bit

            TBL_TaxonUnits::SetUsed($Database, $TSN);
        }

        return($ID);
    }

    public static function SmartUpdate($Database, $TSN, $OrganismInfoID, $Name, $Description, $Habitat, $UniqueFeatures, $History, $Controls, $LifeHistory, $OrganismType, $Citations) {
        if ($OrganismInfoID == 0) {
            $OrganismInfoID = TBL_OrganismInfos::Insert($Database, $TSN); // create the new record
        }

        if ($Name == NOT_SPECIFIED) { // go create a reasonable name for it...
            $CommonNamesSet = TBL_Venaculars::GetSet($Database, $TSN);

            if ($CommonNamesSet->FetchRow()) {
                $Name = $CommonNamesSet->Field("Name"); //get name of first record in set of common namkes associated with this TSN
            } else { // go get UnitName1 and/or Unitname 2 from TBL_TaxonUnits fro this TSN
                $Name = TBL_TaxonUnits::GetScientificNameFromTSN($Database, $TSN, false);
            }
        }

        TBL_OrganismInfos::Update($Database, $OrganismInfoID, $Name, $Description, $Habitat, $UniqueFeatures, $History, $Controls, $LifeHistory, $OrganismType, $Citations);

        // make sure the organism appears in the web site that is editing it

        $WebsiteSet = TBL_Websites::GetSetFromID($Database, GetWebSiteID());
        $WebsiteID = $WebsiteSet->Field("ID");

        //if ($WebsiteSet->Field("LimitOrganismInfos"))
        //{
        $RELSet = REL_WebsiteToOrganismInfo::GetSet($Database, $WebsiteID, $OrganismInfoID);

        if ($RELSet->FetchRow() == false) { // if no rel exists, go create one for the websiteid
            REL_WebsiteToOrganismInfo::Insert($Database, $WebsiteID, $OrganismInfoID);
        }
        //}

        return($OrganismInfoID);
    }

    public static function Update($Database, $OrganismInfoID, $Name, $Description, $Habitat, $UniqueFeatures, $History, $Controls, $LifeHistory, $OrganismType, $Citations) {
        //	DebugWriteln("OrganismInfoID=$OrganismInfoID");

        $UpdateString = "UPDATE TBL_OrganismInfos " .
                "SET Name='$Name', " .
                "Description='$Description', " .
                "Habitat='$Habitat', " .
                "UniqueFeatures='$UniqueFeatures', " .
                "History='$History', " .
                "Controls='$Controls', " .
                "LifeHistory='$LifeHistory', " .
//				"CitSciFlag='$CitSciFlag', ".
                "OrganismType='$OrganismType', " .
                "NameOrder='0', " . // set to 0 so it can be set below
                "Citations='$Citations' " .
                "WHERE ID=" . $OrganismInfoID;

//		DebugWriteln("UpdateString=".$UpdateString);

        $Database->Execute($UpdateString);

        //************************************************************************
        // update the NameOrder.  This is placed between 
        // get the set to see if the order number is set

        $Set = TBL_OrganismInfos::GetSetFromID($Database, $OrganismInfoID);

        $NameOrder = $Set->Field("NameOrder");

        if ($NameOrder == 0) { // setup the order number for the name
            $PreviousOrder = 0; // assume 0 to start incase this is the first name alphabetically
            $NextOrder = -1; // assume -1 incase this is the last one alphabeticallys

            $SelectString = "SELECT ID,Name,NameOrder FROM TBL_OrganismInfos ORDER BY Name";
            $Set = $Database->Execute($SelectString);

            $Found = false;
            while (($Set->FetchRow()) && ($Found == false)) {
                $ID = $Set->Field("ID");
                $NameOrder = $Set->Field("NameOrder");

                if ($ID == $OrganismInfoID) {
                    $Found = true;
//					DebugWriteln("NameOrder=$NameOrder");
//					DebugWriteln("PreviousOrder=$PreviousOrder");
                    if ($Set->FetchRow())
                        $NextOrder = $Set->Field("NameOrder");
                }
                else { // assume this one is the previous one,undates until one is found
                    $PreviousOrder = $NameOrder;
//					DebugWriteln("PreviousOrder=$PreviousOrder");
                }
            }

            if ($Found) { // should always be found
                $NewOrder = $PreviousOrder + 100;
//				DebugWriteln("PreviousOrder=$PreviousOrder, NextOrder=$NextOrder");

                if ($NextOrder != -1) { // find the middle betweem the existing names
                    $NewOrder = (int) (($PreviousOrder + $NextOrder) / 2);
                }
                $UpdateString = "UPDATE TBL_OrganismInfos SET NameOrder=$NewOrder WHERE ID=$OrganismInfoID";
//				DebugWriteln("UpdateString=$UpdateString");

                $Database->Execute($UpdateString);
            }
        }
    }

    //**********************************************************************************
    public static function Delete($Database, $OrganismInfoID) {
    //
    //	Deletes an TaxonUnit and its associated records://
	
        TBL_DBTables::Delete($Database, "TBL_OrganismInfos", $OrganismInfoID);
    }

    //******************************************************************************
    // Additional functions
    //******************************************************************************

    public static function GetInfoLink($Database, $OrganismInfoID, $HTMLFlag = true, $CallingPage = null, $CallingLabel = null) {
    //
    // This is the main public static function to be called to put up a name and link to an OrganismInfo//
	
        $Name = TBL_OrganismInfos::GetName($Database, $OrganismInfoID, $HTMLFlag);

        $Parameters = "OrganismInfoID=" . $OrganismInfoID;
        if ($CallingPage != null)
            $Parameters.="&CallingPage=$CallingPage";
        if ($CallingLabel != null)
            $Parameters.="&CallingLabel=$CallingLabel";

        $Link = GetLink("/cwis438/Browse/Organism/OrganismInfo_Info.php", $Name, $Parameters);

        return($Link);
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

    public static function GetSetFromTSN($Database, $TSN, $GroupFlag = null) {
    //
    // Returns the set of OrganismInfos associated with an TSN
    // (jjg - needs to roll when we use a ralationship for this)//
    
        $String1 = "SELECT OrganismInfoID " .
                "FROM REL_OrganismInfoToTSN " .
                "WHERE TSN=$TSN";

        $SelectString = "SELECT * " .
                "FROM TBL_OrganismInfos " .
                "WHERE ID IN ($String1)";

        if ($GroupFlag === 1)
            TBL_DBTables::AddWhereClause($SelectString, "(GroupFlag=1)");
        if ($GroupFlag === 0)
            TBL_DBTables::AddWhereClause($SelectString, "(GroupFlag=0 OR GroupFlag IS NULL)");

//		DebugWriteln("SelectString=$SelectString");

        $Set = $Database->Execute($SelectString);

        return($Set);
    }

    public static function GetIDFromTSN($Database, $TSN, $GroupFlag = null) {
    //
    // Returns the first OrganismInfoID assocaited with a TSN//
    
        $OrganismInfoID = null;

        $Set = TBL_OrganismInfos::GetSetFromTSN($Database, $TSN, $GroupFlag);

        if ($Set->FetchRow())
            $OrganismInfoID = $Set["ID"];
        else {
            $OrganismInfoID = TBL_OrganismInfos::Insert($Database, $TSN);
        }
        return($OrganismInfoID);
    }

}

?>
