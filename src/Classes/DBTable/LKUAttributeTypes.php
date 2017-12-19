<?php

namespace Classes\DBTable;

//**************************************************************************************
// FileName: LKU_AttributeTypes.php
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
//require_once("C:/Inetpub/wwwroot/cwis438/Classes/DBTable/TBL_OrganismInfos.php");
use Classes\TBLDBTables;

//**************************************************************************************
// Class Definition
//**************************************************************************************
// gjn; perhaps these should ahve been named "ATTRIBUTE_TYPE_XXX"

define("ATTRIBUTE_HEIGHT", 2);
define("ATTRIBUTE_PERCENT_COVER", 3);
define("ATTRIBUTE_PRESENCE", 15);
define("ATTRIBUTE_DERIVED_BIOMASS", 46);
define("ATTRIBUTE_LINE_LENGTH", 67); // used for trail length thus far
define("ATTRIBUTE_DIFFICULTY", 61); // trail difficulty
define("ATTRIBUTE_ELEVATION_CHANGE", 65); // trail difficulty
define("ATTRIBUTE_ALLOWED_USES", 62); // allowed uses
define("ATTRIBUTE_ACCESSIBILITY", 68); // allowed uses
define("ATTRIBUTE_SURFACE_TYPE", 69); // allowed uses

define("ATTRIBUTE_TYPE_APPLIESTO_UNKNOWN", 0);
define("ATTRIBUTE_TYPE_APPLIESTO_GROUP_OR_INDIVIDUAL", 1);
define("ATTRIBUTE_TYPE_APPLIESTO_GROUP", 2);
define("ATTRIBUTE_TYPE_APPLIESTO_INDIVIDUAL", 3);

define("ATTRIBUTE_TYPE_VALUETYPE_LOOKUP", 1);
define("ATTRIBUTE_TYPE_VALUETYPE_FLOAT", 2);
define("ATTRIBUTE_TYPE_VALUETYPE_INTEGER", 3);
define("ATTRIBUTE_TYPE_VALUETYPE_BOOLEAN", 4);

$AttributeTypeAppliesToString = array(" -- Applies to --- ", "Group or individual", "Group", "Individual");

class LKUAttributeTypes {

    //**********************************************************************************
    // Basic database functions
    //**********************************************************************************

    public static function GetSet($Database, $AttributeCategoryID = null, $OrderByField = null) {
    //
    // type is required (we never put up types from different categories//
    
        $SelectString = "SELECT * " .
                "FROM LKU_AttributeTypes ";

        if ($AttributeCategoryID !== null)
            TBL_DBTables::AddWhereClause($SelectString, "AttributeCategoryID=$AttributeCategoryID ");

        if ($OrderByField !== null)
            TBL_DBTables::AddOrderByClause($SelectString, $OrderByField, false); // Descending flag currently hard coded to false, GJN
        else
            $SelectString.="ORDER BY ID";

        //DebugWriteln("SelectString=$SelectString");

        $Set = $Database->Execute($SelectString);

        return($Set);
    }

    public static function GetSetFromID($dbConn, $ID) {
        $SelectString = "SELECT * " .
                "FROM LKU_AttributeTypes " .
                "WHERE ID=:ID";		
        $stmt = $dbConn->prepare($SelectString);
        $stmt->bindValue("ID", $ID);
        $stmt->execute();
        $Set = $stmt->Fetch();

        return($Set);
    }

    public static function GetNameForID($Database, $ID) {
        $Name = "Untitled";

        $Set = LKU_AttributeTypes::GetSetFromID($Database, $ID);

        if ($Set->FetchRow())
            $Name = $Set->Field("Name");

        return($Name);
    }

    public static function GetDescriptionForID($Database, $ID) {
        $Description = "Untitled";

        $Set = LKU_AttributeTypes::GetSetFromID($Database, $ID);

        if ($Set->FetchRow())
            $Description = $Set->Field("Description");

        return($Description);
    }

    public static function GetAttributeCategoryIDForID($Database, $ID) {
        $AttributeCategoryID = "Untitled";

        $Set = LKU_AttributeTypes::GetSetFromID($Database, $ID);

        if ($Set->FetchRow())
            $AttributeCategoryID = $Set->Field("AttributeCategoryID");

        return($AttributeCategoryID);
    }

    //**********************************************************************************
    // Insert, Update, Delete
    //**********************************************************************************

    public static function Insert($Database, $AttributeCategoryID = 1) { // default to organism data attributes ???? 
        $AttributeTypeID = -1;

        $ExecString = "EXEC insert_LKU_AttributeTypes '$AttributeCategoryID'";

        $AttributeTypeID = $Database->DoInsert($ExecString);

        return($AttributeTypeID);
    }

    public static function SmartUpdate($Database, $AttributeCategoryID, $AttributeTypeID, $Name, $Description, $AppliesTo, $ValueType, $UnitTypeID, $MinimumValue, $MaximumValue, $ZeroIndicatesAbsent) {
        if ($AttributeTypeID == 0) {
            $AttributeTypeID = LKU_AttributeTypes::Insert($Database, $AttributeCategoryID); // create the new record
        }

        LKU_AttributeTypes::Update($Database, $AttributeCategoryID, $AttributeTypeID, $Name, $Description, $AppliesTo, $ValueType, $UnitTypeID, $MinimumValue, $MaximumValue, $ZeroIndicatesAbsent);

        // make sure the new record has attribute values if of type lookup (add code later)

        return($AttributeTypeID);
    }

    public static function Update($Database, $AttributeCategoryID, $AttributeTypeID, $Name, $Description, $AppliesTo, $ValueType, $UnitTypeID, $MinimumValue = NOT_SPECIFIED, $MaximumValue = NOT_SPECIFIED, $ZeroIndicatesAbsent = NOT_SPECIFIED) {
        $UpdateString = "UPDATE LKU_AttributeTypes " .
                "SET AttributeCategoryID='$AttributeCategoryID', " .
                "Name='$Name', " .
                "Description='$Description', " .
                "AppliesTo='$AppliesTo', " .
                "ValueType='$ValueType', " .
                "UnitTypeID='$UnitTypeID' ";

        TBL_DBTables::AddIntUpdate($UpdateString, 'MinimumValue', $MinimumValue);
        TBL_DBTables::AddIntUpdate($UpdateString, 'MaximumValue', $MaximumValue);
        TBL_DBTables::AddIntUpdate($UpdateString, 'ZeroIndicatesAbsent', $ZeroIndicatesAbsent);

        $UpdateString.="WHERE ID=" . $AttributeTypeID;

        //DebugWriteln("UpdateString=".$UpdateString);

        $Database->Execute($UpdateString);
    }

    public static function Delete($Database, $AttributeTypeID) {
        TBL_DBTables::Delete($Database, "LKU_AttributeTypes", $AttributeTypeID);
    }

    //**********************************************************************************
    // Additional database functions
    //**********************************************************************************

    public static function GetSetFromName($Database, $Name) {
        $SelectString = "SELECT * " .
                "FROM LKU_AttributeTypes " .
                "WHERE Name='$Name'";

//		DebugWriteln("SelectString=$SelectString");

        $Set = $Database->Execute($SelectString);

        return($Set);
    }

    public static function GetSetFromAssociatedTable($Database, $AssociatedTableID) {
        $SelectString = "SELECT * " .
                "FROM LKU_AttributeTypes " .
                "WHERE AttributeCategoryID IN " .
                "(SELECT ID FROM LKU_AttributeCategories WHERE DatabaseTableID=$AssociatedTableID)";

//		DebugWriteln("SelectString=$SelectString");

        $Set = $Database->Execute($SelectString);

        return($Set);
    }

    public static function GetSetFromOrganismType($Database, $OrganismType, $ParentFormEntryID = NOT_SPECIFIED) {
        if ($ParentFormEntryID !== NOT_SPECIFIED) {
            // DebugWriteln("ParentFormEntryID=$ParentFormEntryID");
            // loop through all form entries with this parentformentryid
            // check for existing entries with attributetypeid in unique set of sttribute types for this organism group
            // 		so as to not allow folks to choose the ssame attribute type twice
            // thus add an additional where clause for each existing entry's attributetypeid

            $ExistingAttributesForParentFormEntrySet = TBL_FormEntries::GetSetFromParentFormEntryID($Database, $ParentFormEntryID, null);

            $AdditionalWhereClause = "";

            while ($ExistingAttributesForParentFormEntrySet->FetchRow()) {
                $AttributeTypeID = $ExistingAttributesForParentFormEntrySet->Field("AttributeTypeID");

                if ($AdditionalWhereClause != "")
                    $AdditionalWhereClause.=" AND ";
                $AdditionalWhereClause.="(ID <> $AttributeTypeID) ";
            }
        }

//		DebugWriteln("--OrganismType=$OrganismType");
        switch ($OrganismType) {
            case ORGANISM_TYPE_PLANT_TERRESTRIAL: // 1,2,3,4,5,8,11,13,14,15,46,47,48,49,52,56,58,86
                $SelectString = "SELECT * " .
                        "FROM LKU_AttributeTypes " .
                        "WHERE (AttributeCategoryID=1) ";
                break;

            case ORGANISM_TYPE_PLANT_AQUATIC: // 1,2,3,4,5,8,11,13,14,15,16,46,47,48,49,52,56,58
                $SelectString = "SELECT * " .
                        "FROM LKU_AttributeTypes " .
                        "WHERE (AttributeCategoryID=1) ";
                break;
            case ORGANISM_TYPE_ANIMAL_VERTIBRATE_TERRESTRIAL: // (OrgType 3) 2,6,7,8,9,10,11,13,15,49,52,56,71,82
                $SelectString = "SELECT * " .
                        "FROM LKU_AttributeTypes " .
                        "WHERE (AttributeCategoryID=1) ";
                break;
            case ORGANISM_TYPE_ANIMAL_INVERTIBRATE_TERRESTRIAL: // 2,6,7,8,9,11,13,15,49,52,56,57,82
                $SelectString = "SELECT * " .
                        "FROM LKU_AttributeTypes " .
                        "WHERE (AttributeCategoryID=1) ";
                break;
            case ORGANISM_TYPE_ANIMAL_VERTIBRATE_AQUATIC: // 2,6,7,8,9,10,11,13,15,16,49,52,56,82
                $SelectString = "SELECT * " .
                        "FROM LKU_AttributeTypes " .
                        "WHERE (AttributeCategoryID=1) ";
                break;
            case ORGANISM_TYPE_ANIMAL_INVERTIBRATE_AQUATIC: // 2,6,7,8,9,10,11,13,15,16,49,52,56,82
                $SelectString = "SELECT * " .
                        "FROM LKU_AttributeTypes " .
                        "WHERE (AttributeCategoryID=1) ";
                break;
            case ORGANISM_TYPE_DISEASE_ANIMAL:
                $SelectString = "SELECT * " .
                        "FROM LKU_AttributeTypes ";
                break;
            case ORGANISM_TYPE_DISEASE_PLANT:
                $SelectString = "SELECT * " .
                        "FROM LKU_AttributeTypes ";
                break;
            default:
                $SelectString = "SELECT * " .
                        "FROM LKU_AttributeTypes ";
        }

        if ($AdditionalWhereClause !== "") {
            TBL_DBTables::AddWhereClause($SelectString, $AdditionalWhereClause);
            //   		$SelectString.=$AdditionalWhereClause;
        }

        $SelectString.="ORDER BY Name";

//		DebugWriteln("SelectString=$SelectString");
        $Set = $Database->Execute($SelectString);

        return($Set);
    }

}

?>
