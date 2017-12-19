<?php

namespace Classes;

//**************************************************************************************
// FileName: TBL_DBTables.php
//
// Represents a table in the database.  
// This is the parent class for all other database table classes.
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
//**************************************************************************************
// Definitions
//**************************************************************************************
//$RelationshipStrings=array("Unknown","Inside","Overlap");
// These definitions are for ValueTypes used generically throughout the system

define("VALUE_TYPE_UNKNOWN", 0);
define("VALUE_TYPE_LOOKUP", 1);
define("VALUE_TYPE_FLOAT", 2);
define("VALUE_TYPE_INTEGER", 3);
define("VALUE_TYPE_BOOLEAN", 4);

$ValueTypeStrings = array(" -- Select a Data Type -- ", "Categorical", "Numeric (Decimal)", "Numeric (Integer)", "Boolean");

//$ValueTypeStrings=array("Unknown","Look Up","Floating point number","Integer number");
// These definitions are specific to the GODM ("invasvie") database

define("TBL_ORGANISMDATA", 1);
define("TBL_PROJECTS", 2);
define("TBL_AREAS", 3);
define("TBL_VISITS", 4);
define("TBL_ATTRIBUTEDATA", 5);
define("TBL_ORGANISMINFO", 6);
define("TBL_INDIVIDUALS", 7);
define("TBL_CONTROLAGENT", 8);
define("TBL_TAXONUNITS", 9);
define("TSN", 10); // TBL_TaxonUnits.TSN // jjg - we should be able to remove this
define("TBL_INSERTLOG", 11);
define("TBL_METADATA", 12);
define("TBL_ORGANIZATIONS", 13);
define("TBL_TREATMENTS", 14);

// These are portable definitions

define("DEBUGGING_DBTABLE", false);
define("TIMING_DBTABLE", false);

//define("NOT_SPECIFIED","");
//**************************************************************************************
// Class Definition
//**************************************************************************************

class TBLDBTables {

    //**********************************************************************************
    // Basic database functions, these functions are overriden by child classes.
    // The child class should define a function without the table name and then
    // call these classes with the $TableName set to their table
    //**********************************************************************************
    public static function GetFieldValue($dbConn, $TableName, $FieldName, $ID, $Default = 0) {
        $Result = $Default;

        $SelectString = "SELECT $FieldName " .
                "FROM $TableName " .
                "WHERE ID=$ID";
        $stmt = $dbConn->prepare($SelectString);
        $stmt->execute();
        $Set = $stmt->fetch();
        if (!$Set) {
            $Result = $Set["$FieldName"];
        }
        return($Result);
    }

    public static function SetFieldValue($dbConn, $TableName, $FieldName, $ID, $Value) {
        //
        // Sets a specific field value in a specific record in a specific table
        //
    //	Database - DBConnection, Database connection
        //	TableName - String, Name of the table containing the record to set a field in
        //	FieldName - String, Name of the field in the table to set
        //	ID - Integer, "ID" of the record to be set
        //	


        if ($Value === null)
            $Value = "NULL"; // gjn; to support nulls; use is_null PHP function instead?

        else if (is_string($Value)) { //is a string or date add the single quotes? gjn. Yikes. 
            $Value = "'$Value'";
        }

        $UpdateString = "UPDATE $TableName " .
                "SET $FieldName=$Value " . // gjn; Note: Very important to check this! It was: SET $FieldName='$Value' but this was causing PHP Warnings to appear on hurricane which has a setting to display warnings. I have tested it setting to NULL for both INT and varchar db fields and it seems to be good and not generate PHP warnings.
                "WHERE ID=$ID";

        $stmt = $dbConn->prepare($UpdateString);
        $stmt->execute();
    }

    //**********************************************************************************
    // Child classes will typically include the following functions:
    //**********************************************************************************
    public static function GetSetFromID($Database, $TableName, $ID) {
        $SelectString = "SELECT * " .
                "FROM $TableName " .
                "WHERE ID='$ID'";

        $Set = $Database->Execute($SelectString);

        return($Set);
    }

    //**********************************************************************************
    // Static database functions
    //**********************************************************************************
    public static function Delete($Database, $ForeignTableName, $ForeignRecordID, $ModifyDatabase = true) {
        //
        // Deletes the specified ForeignRecord based on the specified table name and ID.
        // jjg - we should change the $ForeignTableName to $ForeignTableID
        //
    // Inputs:
        //	$ID=ID of the record being deleted (may be a TSN)
        //	$ForeignTableName=name of the table with a record being deleted (becomes the foreign table)
        //	$ForeignRecordID=ID of the record being deleted (must be from the "ID" field)
        //	$DeleteRecords=true to have records actually be deleted, false is for debugging
        // 	$DeleteForeignRecord = true to have the ForeignRecord deleted (DeleteRecords and this flag must be true)//

        $StartTime = GetMicrotime();
        if (TIMING_DBTABLE)
            DebugWriteln("ForeignTableName=$ForeignTableName");

        if (DEBUGGING_DBTABLE)
            DebugWriteln("************* Entered ***************");
        if (DEBUGGING_DBTABLE)
            DebugWriteln("ForeignTableName=$ForeignTableName");
        if (DEBUGGING_DBTABLE)
            DebugWriteln("ForeignRecordID=$ForeignRecordID");
        if (DEBUGGING_DBTABLE)
            DebugWriteln("ModifyDatabase=$ModifyDatabase");


        //**************************************************************************
        // get the foreign database table ID

        $SelectString = "SELECT ID " .
                "FROM LKU_DatabaseTables " .
                "WHERE Name='$ForeignTableName'";

        if (DEBUGGING_DBTABLE)
            DebugWriteln("SelectString=$SelectString");

        $Set = $Database->Execute($SelectString);

        $ForeignTableID = $Set->Field(1);

        //**************************************************************************
        // get a record set with all the field vlues in the foreign record that is about to be deleted
        // (ID or TSN could be foreign keys)

        $SelectString = "SELECT * " .
                "FROM $ForeignTableName " .
                "WHERE ID=$ForeignRecordID ";

        if (DEBUGGING_DBTABLE)
            DebugWriteln("SelectString=$SelectString");

        $ForeignRecordSet = $Database->Execute($SelectString);

        //**************************************************************************
        // get the set of fields that are lookups to the foreign table
        // (the one we are deleting a record from)

        $SelectString = "SELECT CanBeZero,CanBeNULL,DeleteOnForeignDelete,DatabaseTableID, " .
                "ForeignDatabaseFieldID,Name " .
                "FROM LKU_DatabaseFields " .
                "WHERE ForeignDatabaseTableID=$ForeignTableID ";

        if (DEBUGGING_DBTABLE)
            DebugWriteln("SelectString=$SelectString");

        $FieldSet = $Database->Execute($SelectString);

        //**************************************************************************
        // loop through each field that could be linked to the record being deleted
        // either setting the fields to NULL, 0 or deleting the referencing record

        $StartTime1 = GetMicrotime();
        while ($FieldSet->FetchRow()) {
            $StartTime2 = GetMicrotime();

            $FieldName = $FieldSet->Field("Name");
            $ForeignFieldID = (int) $FieldSet->Field("ForeignDatabaseFieldID"); // id of the field referencing the foreign record
            $CanBeZero = (int) $FieldSet->Field("CanBeZero");
            $CanBeNULL = (int) $FieldSet->Field("CanBeNULL");
            $DeleteOnForeignDelete = (int) $FieldSet->Field("DeleteOnForeignDelete");

            $DatabaseTableID = $FieldSet->Field("DatabaseTableID"); // referencing table id
            if (DEBUGGING_DBTABLE)
                DebugWriteln("DatabaseTableID=$DatabaseTableID");

            if (TIMING_DBTABLE)
                DebugWriteln("Duration2.1=" . (GetMicrotime() - $StartTime2));
            $StartTime2 = GetMicrotime();

            // get the name of the table containing the foreign key

            $TableName = TBL_DBTables::GetFieldValue($Database, "LKU_DatabaseTables", "Name", $DatabaseTableID);

            if (DEBUGGING_DBTABLE)
                DebugWriteln("TableName=$TableName");
            if (DEBUGGING_DBTABLE)
                DebugWriteln("FieldName=$FieldName");
            if (DEBUGGING_DBTABLE)
                DebugWriteln("ForeignFieldID=$ForeignFieldID");
            if (DEBUGGING_DBTABLE)
                DebugWriteln("CanBeZero=$CanBeZero");
            if (DEBUGGING_DBTABLE)
                DebugWriteln("CanBeNULL=$CanBeNULL");
            if (DEBUGGING_DBTABLE)
                DebugWriteln("DeleteOnForeignDelete=$DeleteOnForeignDelete");

            // get the name of the foreign key field and the value of the foreign key

            $ForeignFieldName = TBL_DBTables::GetFieldValue($Database, "LKU_DatabaseFields", "Name", $ForeignFieldID);

            if (TIMING_DBTABLE)
                DebugWriteln("Duration2.2=" . (GetMicrotime() - $StartTime2));
            $StartTime2 = GetMicrotime();

            if (DEBUGGING_DBTABLE)
                DebugWriteln("ForeignFieldName=$ForeignFieldName");

// 			if ($ForeignRecordSet->FetchRow()) // jjg - not sure why this is needed (record will always be there because it's the one were deleteing)
            {
                // get the value of the foreign key in the foreign record (that is being deleted)

                $ForeignKey = (int) $ForeignRecordSet->Field($ForeignFieldName); // this is a primary key in the foreign table (ID or TSN)
                // take the appropriate action on foreign keys

                if ($DeleteOnForeignDelete) { // must delete the table with the foreign key
                    if (TIMING_DBTABLE)
                        DebugWriteln("Duration2.3=" . (GetMicrotime() - $StartTime2));
                    $StartTime2 = GetMicrotime();

                    $SelectString = "SELECT ID " .
                            "FROM $TableName " .
                            "WHERE $FieldName=$ForeignKey ";

                    if (DEBUGGING_DBTABLE)
                        DebugWriteln("SelectString=$SelectString");

                    $RecordSet = $Database->Execute($SelectString);

                    if (TIMING_DBTABLE)
                        DebugWriteln("Duration2.4=" . (GetMicrotime() - $StartTime2));
                    $StartTime2 = GetMicrotime();
// DebugWriteln("TableName=$TableName");
                    while ($RecordSet->FetchRow()) {
                        $ID = $RecordSet->Field("ID");
//DebugWriteln("TableName=$TableName, ID=$ID");
                        switch ($TableName) {
                            //   					case "REL_SpatialGriddedToOrganismInfo_GoogleMaps":
                            //   						REL_SpatialGriddedToOrganismInfo::Delete($Database,$ID);
                            //   						break;
                            //   					case "REL_SpatialGriddedToArea_GoogleMaps":
                            //   						REL_SpatialGriddedToArea::Delete($Database,$ID);
                            //   						break;
                            case "TBL_Areas":
                                TBL_Areas::Delete($Database, $ID);
                                break;
                            case "TBL_AttributeData":
                                TBL_AttributeData::Delete($Database, $ID);
                                break;
                            case "TBL_Media":
                                TBL_Media::Delete($Database, $ID);
                                break;
                            case "TBL_OrganismData":
                                TBL_OrganismData::Delete($Database, $ID);
                                break;
                            case "TBL_ScriptSteps":
                                TBL_ScriptSteps::Delete($Database, $ID);
                                break;
                            //   					case "TBL_SpatialGridded_GoogleMaps":
                            //   						TBL_SpatialGridded::Delete($Database,$ID);
                            //   						break;
                            case "TBL_Visits":
                                TBL_Visits::Delete($Database, $ID);
                                break;
                            case "TBL_SpatialLayerTypes":
                                TBL_SpatialLayerTypes::Delete($Database, $ID);
                                break;
                            case "TBL_GISIN_SpeciesStatuses":
                                TBL_GISIN_SpeciesStatuses::Delete($Database, $ID);
                                break;
                            default:
                                TBL_DBTables::Delete($Database, $TableName, $ID, $ModifyDatabase);
                                break;
                        }


                        if (TIMING_DBTABLE)
                            DebugWriteln("Duration2.5=" . (GetMicrotime() - $StartTime2));
                        $StartTime2 = GetMicrotime();
                    }
                }
                else if ($CanBeZero == 1) { // set the field to 0, "unknown"
                    $UpdateString = "UPDATE $TableName " .
                            "SET $FieldName=0 " .
                            "WHERE $FieldName=$ForeignKey";

                    if (DEBUGGING_DBTABLE)
                        DebugWriteln("UpdateString=$UpdateString");

                    if ($ModifyDatabase)
                        $Database->Execute($UpdateString);
                    else
                        DebugWriteln("Setting $FieldName=0 in $TableName where $FieldName=$ForeignKey");
                }
                else { // if ($CanBeNULL==1) // set the field to null (this is the only remaining option)
                    $UpdateString = "UPDATE $TableName " .
                            "SET $FieldName=NULL " .
                            "WHERE $FieldName=$ForeignKey";

                    if (DEBUGGING_DBTABLE)
                        DebugWriteln("UpdateString=$UpdateString");

                    if ($ModifyDatabase)
                        $Database->Execute($UpdateString);
                    else
                        DebugWriteln("Setting $FieldName=NULL in $TableName where $FieldName=$ForeignKey");
                }
            }

            if (TIMING_DBTABLE)
                DebugWriteln("Duration2.6=" . (GetMicrotime() - $StartTime2));
        }
        if (TIMING_DBTABLE)
            DebugWriteln("Duration1=" . (GetMicrotime() - $StartTime1));


        //**************************************************************************
        // now delete the specified record if desired

        $DeleteString = "DELETE FROM $ForeignTableName " .
                "WHERE ID=$ForeignRecordID";

        if (DEBUGGING_DBTABLE)
            DebugWriteln("DeleteString=$DeleteString");

        if ($ModifyDatabase)
            $Database->Execute($DeleteString);
        else
            DebugWriteln("Deleting record in $ForeignTableName where ID=$ForeignRecordID");

        if (DEBUGGING_DBTABLE)
            DebugWriteln("************* Returned ***************");

        if (TIMING_DBTABLE)
            DebugWriteln("Duration=" . (GetMicrotime() - $StartTime));
    }

    //**********************************************************************************
    // These functions aid in moving record "up" and "down" using "OrderNumber" fields
    //**********************************************************************************
    public static function MoveUp($Database, $TableName, $OrderFieldName, $TargetRecordID, $AdditionalCriteria = "") {
        //
        //	This function moves a record "up" in a list that is ordered by a "OrderNumber"
        //	in the database records.
        //
	//	$Database -
        //	$TableName - Table that the records are in
        //	$OrderFieldName - Field for the order number (typically "OrderNumber")
        //	$TargetRecordID - Record to move up
        //	$AdditionalCriteria - Additional "WHERE" criteria (i.e. "WebSiteID=12")//

        $Set = TBL_DBTables::GetSetFromID($Database, $TableName, $TargetRecordID);

        $OrderNumber = $Set->Field($OrderFieldName);

        $SelectString = "SELECT * " .
                "FROM $TableName " .
                "WHERE $AdditionalCriteria " .
                "AND $OrderFieldName<$OrderNumber " .
                "ORDER BY $OrderFieldName DESC";

//		DebugWriteln("SelectString=$SelectString");

        $PreviousSet = $Database->Execute($SelectString);

        if ($PreviousSet->FetchRow()) {
            // set the target to the previous steps OrderNumber

            $UpdateString = "UPDATE $TableName " .
                    "SET $OrderFieldName='" . $PreviousSet->Field("$OrderFieldName") . "' " .
                    "WHERE ID=" . $TargetRecordID;

//			DebugWriteln("UpdateString=$UpdateString");
            $Database->Execute($UpdateString);

            // set the previous step to the target steps OrderNumber

            $UpdateString = "UPDATE $TableName " .
                    "SET $OrderFieldName='" . $OrderNumber . "' " .
                    "WHERE ID=" . $PreviousSet->Field("ID");

//			DebugWriteln("UpdateString=$UpdateString");
            $Database->Execute($UpdateString);
        }
    }

    public static function MoveDown($Database, $TableName, $OrderFieldName, $TargetRecordID, $AdditionalCriteria = "") {
        //
        //	This function moves a record "down" in a list that is ordered by a "OrderNumber"
        //	in the database records.
        //
	//	$Database -
        //	$TableName - Table that the records are in
        //	$OrderFieldName - Field for the order number (typically "OrderNumber")
        //	$TargetRecordID - Record to move up
        //	$AdditionalCriteria - Additional "WHERE" criteria (i.e. "WebSiteID=12")//
        //$Set=TBL_WebsiteMenuItems::GetSetFromID($Database,$TargetRecordID);
        $Set = TBL_DBTables::GetSetFromID($Database, $TableName, $TargetRecordID);

        $OrderNumber = $Set->Field("OrderNumber");

        $SelectString = "SELECT * " .
                "FROM $TableName " .
                "WHERE $AdditionalCriteria " .
                "AND $OrderFieldName>$OrderNumber " .
                "ORDER BY $OrderFieldName";

        $PreviousSet = $Database->Execute($SelectString);

        if ($PreviousSet->FetchRow()) {
            // set the target to the previous steps OrderNumber

            $UpdateString = "UPDATE $TableName " .
                    "SET $OrderFieldName='" . $PreviousSet->Field("$OrderFieldName") . "' " .
                    "WHERE ID=" . $TargetRecordID;

            $Database->Execute($UpdateString);

            // set the previous step to the target steps OrderNumber

            $UpdateString = "UPDATE $TableName " .
                    "SET $OrderFieldName='" . $OrderNumber . "' " .
                    "WHERE ID=" . $PreviousSet->Field("ID");

            $Database->Execute($UpdateString);
        }
    }

    //**********************************************************************************
    // These functions aid in the creation of SQL queries 
    //**********************************************************************************
    public static function GetSelectClause($FirstRow = 0, $NumRows = null, $Fields = null) {
        //
        //	Returns a select clause of a T-SQL string.
        //	- FirstRow = Used to increase the number of row queried, if used it must
        //		be used in conjunction with 
        $SelectString = "SELECT ";

        if ($NumRows != null) {
            $SelectString.="TOP " . ($FirstRow + $NumRows) . " ";
        }

        if ($Fields != null) {
            for ($i = 0; $i < count($Fields); $i++) {
                if ($i > 0)
                    $SelectString.=",";

                $SelectString.="" . $Fields[$i] . "";
            }
        } else
            $SelectString.="*"; // get all fields

        $SelectString.=" ";

        return($SelectString);
    }

    public static function AddBitUpdate(&$SelectString, $Field, $Value) {
        //$Value=SQL::GetBit($Value);

        TBLDBTables::AddUpdateClause($SelectString, $Field, $Value);
    }

    public static function AddIntUpdate(&$SelectString, $Field, $Value) {
        //$Value=SQL::GetInt($Value);

        TBLDBTables::AddUpdateClause($SelectString, $Field, $Value);

//		DebugWriteln("SelectString=$SelectString");
    }

    public static function AddFloatUpdate(&$SelectString, $Field, $Value) {
        //$Value=SQL::GetFloat($Value);

        TBLDBTables::AddUpdateClause($SelectString, $Field, $Value);

//		DebugWriteln("SelectString=$SelectString");
    }

    public static function AddStringUpdate(&$SelectString, $Field, $Value) {
        //$Value=SQL::GetString($Value);

        TBLDBTables::AddUpdateClause($SelectString, $Field, $Value);

//		DebugWriteln("SelectString=$SelectString");
    }

    public static function AddDateUpdate(&$SelectString, $Field, $Value) {
        //$Value=SQL::GetDate($Value);

        TBLDBTables::AddUpdateClause($SelectString, $Field, $Value);

//		DebugWriteln("SelectString=$SelectString");
    }

    public static function AddUpdateClause(&$SelectString, $Field, $Value) {
        if ($Value !== NOT_SPECIFIED) {
            $EqualIndex = strpos($SelectString, "UPDATE");

            if ($EqualIndex !== FALSE) { // if we are not building a middle string (there is an UPDATE), make sure there is a SET
                $EqualIndex = strpos($SelectString, "SET");

                if ($EqualIndex === FALSE)
                    $SelectString.=" SET "; // not set, add it
            }

            $EqualIndex = strpos($SelectString, "=");

            if ($EqualIndex !== FALSE) { // have and equals sign  
                $SelectString.=","; // add a comma because we already have an equals
            }

            $SelectString.=" $Field=$Value ";
        }
//		DebugWriteln("SelectString=$SelectString");
    }

    public static function AddWhereClause(&$SelectString, $Condition, $Mode = null) {
        if ($Mode == 1) {
            $SelectString.=" AND ";
        } else {
            $Index = strpos($SelectString, "WHERE");

            if ($Index === false) {
                $SelectString.=" WHERE ";
            } else { // WhereAdded is true
                $SelectString.=" AND ";
            }
        }
        $SelectString.=$Condition;
    }

    public static function AddWhereClause2(&$SelectString, $Condition, $OrFlag = false) {
        $Index = strpos($SelectString, "WHERE");

        if ($Index === false) {
            $SelectString.=" WHERE ";
        } else if ($OrFlag === true) {
            $SelectString.=" OR ";
        } else { // WhereAdded is true
            $SelectString.=" AND ";
        }
        $SelectString.=$Condition;
    }

    public static function AddWhereValues(&$SelectString, $FieldName, $Values) {
        //
        //	$Values - single value or an array of possible values//
        // add the correct conjunction

        $Index = strpos($SelectString, "WHERE");

        if ($Index === false) {
            $SelectString.=" WHERE ";
        } else { // WhereAdded is true
            $SelectString.=" AND ";
        }

        // add the ors

        if (is_array($Values)) {
            $SelectString.=" ( ";

            for ($i = 0; $i < count($Values); $i++) {
                if ($i != 0)
                    $SelectString.=" OR ";

                $SelectString.="$FieldName='" . $Values[$i] . "'";
            }
            $SelectString.=" ) ";
        }
        else {
            $SelectString.=" $FieldName='$Value' ";
        }
    }

    public static function AddOrderByClause(&$SelectString, $OrderByField, $DescendingFlag) {
        $SortOrder = "ASC";

        if ($DescendingFlag == true)
            $SortOrder = "DESC";

        $Index = strpos($SelectString, "ORDER BY");

//		if ($Index===false)
        {
            $SelectString.=" ORDER BY ";
        }
        /* 		else // WhereAdded is true
          {
          $SelectString.=", ";
          }
         */ $SelectString.=$OrderByField . " " . $SortOrder;
    }

}

?>
