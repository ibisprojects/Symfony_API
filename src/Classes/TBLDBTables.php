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

use Classes\DBTable\TBLAreas;
use Classes\DBTable\TBLAttributeData;
use Classes\DBTable\TBLMedia;
use Classes\DBTable\TBLOrganismData;
use Classes\DBTable\TBLScriptSteps;
use Classes\DBTable\TBLVisits;
use Classes\DBTable\TBLSpatialLayerTypes;
use Classes\DBTable\TBLGISINSpeciesStatuses;
use API\Classes\Constants;

$ValueTypeStrings = array(" -- Select a Data Type -- ", "Categorical", "Numeric (Decimal)", "Numeric (Integer)", "Boolean");

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

        $SelectString = "SELECT \"$FieldName\" ".
                "FROM \"$TableName\" ".
                "WHERE \"ID\"=$ID";
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

        else if (is_string($Value)) //is a string or date add the single quotes? gjn. Yikes.
        {
            $Value = "'$Value'";
        }
        else if (is_bool($Value))
        {
            $Value=$Value === true ? 'true' : 'false';
        }

        $UpdateString = "UPDATE \"$TableName\" " .
                "SET \"$FieldName\"=$Value " . // gjn; Note: Very important to check this! It was: SET $FieldName='$Value' but this was causing PHP Warnings to appear on hurricane which has a setting to display warnings. I have tested it setting to NULL for both INT and varchar db fields and it seems to be good and not generate PHP warnings.
                "WHERE \"ID\"=$ID";

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
    public static function Delete($dbConn, $ForeignTableName, $ForeignRecordID) {
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

        //**************************************************************************
        // get the foreign database table ID

        $SelectString = "SELECT \"ID\" ".
                "FROM \"LKU_DatabaseTables\" ".
                "WHERE \"Name\"='$ForeignTableName'";

        $stmt = $dbConn->prepare($SelectString);
        $stmt->execute();

        $ForeignTableID = $stmt->fetchColumn();
        $stmt = null;

        //**************************************************************************
        // get a record set with all the field vlues in the foreign record that is about to be deleted
        // (ID or TSN could be foreign keys)

        $SelectString = "SELECT * ".
                "FROM \"$ForeignTableName\" ".
                "WHERE \"ID\"=$ForeignRecordID ";

        $stmt = $dbConn->prepare($SelectString);
        $stmt->execute();

        $ForeignRecordSet = $stmt->fetch();
        $stmt = null;

        //**************************************************************************
        // get the set of fields that are lookups to the foreign table
        // (the one we are deleting a record from)

        $SelectString = "SELECT \"CanBeZero\",\"CanBeNULL\",\"DeleteOnForeignDelete\",\"DatabaseTableID\", ".
                "\"ForeignDatabaseFieldID\",\"Name\" ".
                "FROM \"LKU_DatabaseFields\" ".
                "WHERE \"ForeignDatabaseTableID\"=$ForeignTableID ";

        $stmt = $dbConn->prepare($SelectString);
        $stmt->execute();

        //**************************************************************************
        // loop through each field that could be linked to the record being deleted
        // either setting the fields to NULL, 0 or deleting the referencing record

        while ($row = $stmt->fetch()) {
            $FieldName = $row["Name"];
            $ForeignFieldID = (int) $row["ForeignDatabaseFieldID"]; // id of the field referencing the foreign record
            $CanBeZero = (int) $row["CanBeZero"];
            $DeleteOnForeignDelete = (int) $row["DeleteOnForeignDelete"];
            $DatabaseTableID = $row["DatabaseTableID"]; // referencing table id

            // get the name of the table containing the foreign key

            $TableName = TBLDBTables::GetFieldValue($dbConn, "LKU_DatabaseTables", "Name", $DatabaseTableID);

            // get the name of the foreign key field and the value of the foreign key

            $ForeignFieldName = TBLDBTables::GetFieldValue($dbConn, "LKU_DatabaseFields", "Name", $ForeignFieldID);

            // get the value of the foreign key in the foreign record (that is being deleted)

            $ForeignKey = (int) $ForeignRecordSet[$ForeignFieldName]; // this is a primary key in the foreign table (ID or TSN)

            // take the appropriate action on foreign keys

            if ($DeleteOnForeignDelete) { // must delete the table with the foreign key
                $SelectString = "SELECT \"ID\" ".
                        "FROM \"$TableName\" ".
                        "WHERE \"$FieldName\"=$ForeignKey ";

                $stmtw = $dbConn->prepare($SelectString);
                $stmtw->execute();

                while ($tableRow = $stmtw->fetch()) {
                    $ID = $tableRow["ID"];

                    switch ($TableName) {
                        case "TBL_Areas":
                            TBLAreas::Delete($dbConn, $ID);
                            break;
                        case "TBL_AttributeData":
                            TBLAttributeData::Delete($dbConn, $ID);
                            break;
                        case "TBL_Media":
                            TBLMedia::Delete($dbConn, $ID);
                            break;
                        case "TBL_OrganismData":
                            TBLOrganismData::Delete($dbConn, $ID);
                            break;
                        case "TBL_ScriptSteps":
                            TBLScriptSteps::Delete($dbConn, $ID);
                            break;
                        case "TBL_Visits":
                            TBLVisits::Delete($dbConn, $ID);
                            break;
                        case "TBL_SpatialLayerTypes":
                            TBLSpatialLayerTypes::Delete($dbConn, $ID);
                            break;
                        case "TBL_GISIN_SpeciesStatuses":
                            TBLGISINSpeciesStatuses::Delete($dbConn, $ID);
                            break;
                        default:
                            TBLDBTables::Delete($dbConn, $TableName, $ID);
                            break;
                    }
                }
            } else if ($CanBeZero == 1) { // set the field to 0, "unknown"
                $UpdateString = "UPDATE \"$TableName\" ".
                        "SET \"$FieldName\"=0 ".
                        "WHERE \"$FieldName\"=$ForeignKey";

                $stmtw = $dbConn->prepare($UpdateString);
                $stmtw->execute();
            } else { // set the field to null (this is the only remaining option)
                $UpdateString = "UPDATE \"$TableName\" ".
                        "SET \"$FieldName\"=NULL ".
                        "WHERE \"$FieldName\"=$ForeignKey";

                $stmtw = $dbConn->prepare($UpdateString);
                $stmtw->execute();
            }

            $stmtw = null;
        }

        $stmt = null;

        //**************************************************************************
        // now delete the specified record if desired

        $DeleteString = "DELETE FROM \"$ForeignTableName\" ".
                "WHERE \"ID\"=$ForeignRecordID";

        $stmt = $dbConn->prepare($DeleteString);
        $stmt->execute();
        $stmt = null;
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
        if ($Value !== Constants::NOT_SPECIFIED) {
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

            $SelectString.=" \"$Field\"=$Value ";
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
         */ $SelectString.='"'.$OrderByField.'"' . " " . $SortOrder;
    }

}

?>
