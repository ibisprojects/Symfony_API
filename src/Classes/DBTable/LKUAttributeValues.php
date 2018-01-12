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
    public static function GetSetFromType($dbConn, $AttributeTypeID) {
        $SelectString = "SELECT * " .
                "FROM \"LKU_AttributeValues\" " .
                "WHERE \"AttributeTypeID\"=:AttributeTypeID";

        $stmt = $dbConn->prepare($SelectString);
        $stmt->bindValue("AttributeTypeID", $AttributeTypeID);
        $stmt->execute();
        $data = array();
        while ($AttributeValueEntry= $stmt->fetch()) {
            $data []= array("ID"=>$AttributeValueEntry["ID"],"Name"=>$AttributeValueEntry["Name"],"Description"=>$AttributeValueEntry["Description"]);
        }
        return($data);
    }

    public static function GetIDFromName($dbConn, $Name) {
        $SelectString = "SELECT * " .
                "FROM \"LKU_AttributeValues\" " .
                "WHERE \"Name\"='$Name'";

        $stmt = $dbConn->prepare($SelectString);
        $stmt->execute();
        $Set = $stmt->Fetch();

        return($Set["ID"]);
    }
}

?>
