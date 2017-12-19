<?php

namespace Classes\DBTable;

//**************************************************************************************
// FileName: LKU_AttributeValues.php
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

define("ATTRIBUTE_VALUE_PRESENT", 32);
define("ATTRIBUTE_VALUE_ABSENT", 33);

define("ATTRIBUTE_VALUE_ACCESSABLE", 110);
define("ATTRIBUTE_VALUE_INACCESSABLE", 111);
define("ATTRIBUTE_VALUE_DOG", 112);
define("ATTRIBUTE_VALUE_HORSE", 113);
define("ATTRIBUTE_VALUE_BIKING", 114);
define("ATTRIBUTE_VALUE_SINGLE_TRACK", 115);
define("ATTRIBUTE_VALUE_ATV", 116);
define("ATTRIBUTE_VALUE_SNOWMOBILE", 117);
define("ATTRIBUTE_VALUE_X-COUNTRY_SKIING", 118);
define("ATTRIBUTE_VALUE_HIKING", 119);

define("ATTRIBUTE_VALUE_NATURAL", 120);
define("ATTRIBUTE_VALUE_PAVED", 121);
define("ATTRIBUTE_VALUE_GRAVEL", 122);
define("ATTRIBUTE_VALUE_UNKNOWN", 123);
define("ATTRIBUTE_VALUE_DOGS_ON_LEASH", 124);
define("ATTRIBUTE_VALUE_WOOD", 125);

//**************************************************************************************
// Class Definition
//**************************************************************************************

class LKUAttributeValues {

    //**********************************************************************************
    // Basic database functions
    //**********************************************************************************

    public static function GetSet($Database, $AttrbuteTypeID = null) {
        $SelectString = "SELECT * " .
                "FROM LKU_AttributeValues ";

        if ($AttrbuteTypeID !== null)
            $SelectString.="WHERE AttributeTypeID=$AttrbuteTypeID";

//		DebugWriteln("SelectString=$SelectString");

        $Set = $Database->Execute($SelectString);

        return($Set);
    }

    public static function Delete($Database, $AttributeValueID) {
        TBL_DBTables::Delete($Database, "LKU_AttributeValues", $AttributeValueID);
    }

    public static function GetSetFromID($Database, $ID) {
        $SelectString = "SELECT * " .
                "FROM LKU_AttributeValues " .
                "WHERE ID='$ID'";

//		DebugWriteln("SelectString=$SelectString");

        $Set = $Database->Execute($SelectString);

        return($Set);
    }

    public static function GetSetFromTypeAndID($Database, $AttributeTypeID, $ID) {
        $SelectString = "SELECT * " .
                "FROM LKU_AttributeValues " .
                "WHERE AttributeTypeID='$AttributeTypeID' AND " .
                "ID='$ID'";

//		DebugWriteln("SelectString=$SelectString");

        $Set = $Database->Execute($SelectString);

        return($Set);
    }

    public static function GetSetFromType($dbConn, $AttributeTypeID) {
        $SelectString = "SELECT * " .
                "FROM LKU_AttributeValues " .
                "WHERE AttributeTypeID=:AttributeTypeID";

        $stmt = $dbConn->prepare($SelectString);
        $stmt->bindValue("AttributeTypeID", $AttributeTypeID);
        $stmt->execute();
        $data = array();
        while ($AttributeValueEntry= $stmt->fetch()) {            
            $data []= array("ID"=>$AttributeValueEntry["ID"],"Name"=>$AttributeValueEntry["Name"],"Description"=>$AttributeValueEntry["Description"]);
        }
        return($data);
    }

    public static function GetNameForID($Database, $ID) {
        $Name = "Untitled";

        $Set = LKU_AttributeValues::GetSetFromID($Database, $ID);

        if ($Set->FetchRow())
            $Name = $Set->Field("Name");

        return($Name);
    }

    public static function GetNameSetFromID($Database, $ID) {
        $SelectString = "SELECT * " .
                "FROM LKU_AttributeValues " .
                "WHERE ID='$ID'";

        $Set = $Database->Execute($SelectString);

        return($Set);
    }

    //**********************************************************************************
    // INSERT, UPDATE, etc. (new 9/11/12)
    //**********************************************************************************

    public static function Insert($Database, $AttributeTypeID) { // insert $AttributeTypeID, return $AttributeValueID
        $AttributeValueID = -1;

        $ExecString = "EXEC insert_LKU_AttributeValues '$AttributeTypeID'";

        $AttributeValueID = $Database->DoInsert($ExecString);

        return($AttributeValueID);
    }

    public static function SmartUpdate($Database, $AttributeTypeID, $AttributeValueID, $NewValueName, $NewValueDescription) { // insert $AttributeTypeID, return $AttributeValueID, update with $Name and $Description
        if ($AttributeValueID == 0) {
            $AttributeValueID = LKU_AttributeValues::Insert($Database, $AttributeTypeID); // create the new record
        }

        LKU_AttributeValues::Update($Database, $AttributeTypeID, $AttributeValueID, $NewValueName, $NewValueDescription);

        // make sure the new record has attribute values if of type lookup (add code later)

        return($AttributeValueID);
    }

    public static function Update($Database, $AttributeTypeID, $AttributeValueID, $NewValueName, $NewValueDescription) { // update record where ID in LKU_AttributeValueID = $AttributeValueID you pass in
        $UpdateString = "UPDATE LKU_AttributeValues " .
                "SET AttributeTypeID='$AttributeTypeID', " .
                "Name='$NewValueName', " .
                "Description='$NewValueDescription'";

        $UpdateString.="WHERE ID=" . $AttributeValueID;

        //DebugWriteln("UpdateString=".$UpdateString);

        $Database->Execute($UpdateString);
    }

    //**********************************************************************************
    // Additional database functions
    //**********************************************************************************
    public static function GetString($Database, $AttributeTypeID, $AttributeValueID, $FloatValue) {
        if ($AttributeTypeID == 0)
            $AttributeTypeID = 0;
        if ($AttributeValueID == 0)
            $AttributeValueID = 0;

        $String = "";

        $SelectString = "SELECT Description,Name " .
                "FROM LKU_AttributeValues " .
                "WHERE AttributeTypeID=$AttributeTypeID " .
                "AND ID=$AttributeValueID";

        //	DebugWriteln("SelectString=$SelectString");

        $Set = $Database->Execute($SelectString);

        if ($Set->FetchRow()) {
            $String = $Set->Field("Name");
        } else
            $String = $FloatValue;

        return($String);
    }

    public static function GetNum($Database, $AttbriuteTypeID) {
        $SelectString = "SELECT Count(ID) " .
                "FROM LKU_AttributeValues " .
                "WHERE AttributeTypeID=$AttbriuteTypeID";

        //	DebugWriteln("SelectString=$SelectString");

        $Set = $Database->Execute($SelectString);

        return($Set->Field(1));
    }

    public static function GetPresenceNumber($Value) {
    //
    // Hack to force the value for a Presence attribute to a 0 or 1//
	
        $Value = (int) $Value;

        if ($Value == ATTRIBUTE_VALUE_PRESENT) {
            $Value = "1";
        } else {
            $Value = "0";
        }
        return($Value);
    }

    public static function GetIDFromName($dbConn, $Name) {
        $SelectString = "SELECT * " .
                "FROM LKU_AttributeValues " .
                "WHERE Name='$Name'";

        $stmt = $dbConn->prepare($SelectString);
        $stmt->execute();
        $Set = $stmt->Fetch();

        return($Set["ID"]);
    }

}

?>
