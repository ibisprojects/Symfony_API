<?php

namespace Classes\DBTable;

//**************************************************************************************
// FileName: TBL_AttributeData.php
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

use Classes\TBLDBTables;
use Classes\Utilities\SQL;
use API\Classes\Constants;

define("ATTRIBUTE_UNKNOWN", 0);
define("ATTRIBUTE_LOOKUP", 1);
define("ATTRIBUTE_FLOAT", 2);
define("ATTRIBUTE_INTEGER", 3);
define("ATTRIBUTE_BOOLEAN", 4);
define("ATTRIBUTE_STRING", 5);
define("ATTRIBUTE_DATETIME", 6);

//**************************************************************************************
// Class Definition
//**************************************************************************************

class TBLAttributeData {

    //**********************************************************************************
    // Private functions
    //**********************************************************************************
    static private function DoUpdate($dbConn, $ID, $AttributeTypeID, $AttributeValue, $SubplotID, $Uncertainty) {
        //
        // This public static function is called by Insert() and Update() below.
        // This public static function does not update the Gridded data as that must be handled differently.//

        $AttributeTypeSet = LKUAttributeTypes::GetSetFromID($dbConn, $AttributeTypeID);

        // update

        $UpdateString = "UPDATE \"TBL_AttributeData\" " .
                "SET \"AttributeTypeID\"=$AttributeTypeID, ";

        switch ($AttributeTypeSet["ValueType"]) {
            case ATTRIBUTE_UNKNOWN:
                break;
            case ATTRIBUTE_LOOKUP:
                $AttributeValue = (int) $AttributeValue;

                if ($AttributeValue === 0)
                    $AttributeValue = null;

                $UpdateString.="\"AttributeValueID\"=$AttributeValue";
                break;
            case ATTRIBUTE_FLOAT:
                $AttributeValue = (float) $AttributeValue;
                $UpdateString.="\"FloatValue\"='$AttributeValue'";
                break;
            case ATTRIBUTE_INTEGER:
                $AttributeValue = (int) $AttributeValue;
                $UpdateString.="\"IntValue\"='$AttributeValue'";
                break;
            case ATTRIBUTE_STRING:
                $AttributeValue = (string) $AttributeValue;
                $UpdateString.="\"StringValue\"='$AttributeValue'";
                break;
            case ATTRIBUTE_DATETIME:
                $AttributeValue = $AttributeValue;
                $UpdateString.="\"DateTime\"='$AttributeValue'";
                break;
        }

        if ($SubplotID !== Constants::NOT_SPECIFIED)
            $UpdateString.=",\"SubplotID\"=" . SQL::GetInt($SubplotID);
        if ($Uncertainty !== Constants::NOT_SPECIFIED)
            $UpdateString.=",\"Uncertainty\"=" . SQL::GetFloat($Uncertainty);

        $UpdateString.=" WHERE \"ID\"=$ID";

        $stmt = $dbConn->prepare($UpdateString);
        $stmt->execute();
        $stmt = null;
    }

    //**********************************************************************************
    // TBL_DBTables functions
    //**********************************************************************************
    public static function GetFieldValue($dbConn, $FieldName, $ID, $Default = 0) {
        $Result = TBLDBTables::GetFieldValue($dbConn, "TBL_AttributeData", $FieldName, $ID, $Default);

        return($Result);
    }

    public static function SetFieldValue($Database, $FieldName, $ID, $Value) {
        TBL_DBTables::SetFieldValue($Database, "TBL_AttributeData", $FieldName, $ID, $Value);
    }

    //**********************************************************************************
    // Basic database functions
    //**********************************************************************************
    public static function GetSet($dbConn, $VisitID = null, $OrganismDataID = null, $TreatmentID = null, $SubplotID = null, $AttributeTypeID = null, $AreaID = null) {
        $SelectString = "SELECT * " .
                "FROM \"TBL_AttributeData\" ";

        if ($VisitID > 0)
            TBLDBTables::AddWhereClause($SelectString, "\"VisitID\"=$VisitID");
        if ($OrganismDataID > 0)
            TBLDBTables::AddWhereClause($SelectString, "\"OrganismDataID\"=$OrganismDataID");
        if ($TreatmentID > 0)
            TBLDBTables::AddWhereClause($SelectString, "\"TreatmentID\"=$TreatmentID");
        if ($SubplotID > 0)
            TBLDBTables::AddWhereClause($SelectString, "\"SubplotID\"=$SubplotID");
        if ($AttributeTypeID > 0)
            TBLDBTables::AddWhereClause($SelectString, "\"AttributeTypeID\"=$AttributeTypeID");
        if ($AreaID > 0)
            TBLDBTables::AddWhereClause($SelectString, "\"AreaID\"=$AreaID");

        $stmt = $dbConn->prepare($SelectString);
        $stmt->execute();

        $Set = $stmt->fetch();

        if (!$Set) {
            return false;
        }

        return($Set);
    }

    public static function GetSetFromID($Database, $ID) {
        $SelectString = "SELECT * " .
                "FROM TBL_AttributeData " .
                "WHERE ID=$ID";
//		DebugWriteln("SelectString=".$SelectString);

        $Set = $Database->Execute($SelectString);

        return($Set);
    }

    public static function Insert($dbConn, $VisitID = null, $OrganismDataID = null, $TreatmentID = null, $AttributeTypeID = null, $AttributeValue = null, $SubplotID = null, $Uncertainty = null, $AreaID = null, $FormEntryID = null, $NumDecimals = null, $UnitID = null) {
        //
        // Attribute data is tied to one Visit, OrganismData, or Treatment so only one of these should
        // be provided.  Added AreaID at the end//

        $ExecString = "INSERT INTO \"TBL_AttributeData\" (\"VisitID\") VALUES (NULL)";

        $stmt = $dbConn->prepare($ExecString);
        $stmt->execute();

        $ID = $dbConn->lastInsertId('TBL_AttributeData_ID_seq');
        $stmt = null;

        // add the appropriate ids

        $UpdateString = "UPDATE \"TBL_AttributeData\" " .
                "SET \"AttributeTypeID\"=$AttributeTypeID";

        if ($VisitID > 0)
            $UpdateString.=",\"VisitID\"=$VisitID";
        if ($OrganismDataID > 0)
            $UpdateString.=",\"OrganismDataID\"=$OrganismDataID";
        if ($TreatmentID > 0)
            $UpdateString.=",\"TreatmentID\"=$TreatmentID";
        if ($AreaID > 0)
            $UpdateString.=",\"AreaID\"=$AreaID";
        if ($FormEntryID > 0)
            $UpdateString.=",\"FormEntryID\"=$FormEntryID";
        if ($NumDecimals > 0)
            $UpdateString.=",\"NumDecimals\"=$NumDecimals";
        if ($UnitID != null)
            $UpdateString.=",\"UnitID\"=$UnitID";

        $UpdateString.=" WHERE \"ID\"=$ID";

        $stmt = $dbConn->prepare($UpdateString);
        $stmt->execute();
        $stmt = null;

        // update the value

        TBLAttributeData::DoUpdate($dbConn, $ID, $AttributeTypeID, $AttributeValue, $SubplotID, $Uncertainty);

        // Update Project's NumMeasurements

        TBLProjects::IncrementNumMeasurements($Database,$ID);

        return($ID);
    }

    public static function Update($Database, $ID, $AttributeValue, $AttributeTypeID = Constants::NOT_SPECIFIED, $SubplotID = Constants::NOT_SPECIFIED, $Uncertainty = Constants::NOT_SPECIFIED) {
//		DebugWriteln("TBL_AttributeData: Update");
        REL_SpatialGriddedToOrganismInfo::UpdateAttributeData($Database, null, $ID, false);

        // get the attribute type

        if ($SubplotID === 0)
            $SubplotID = null;

        if ($AttributeTypeID === Constants::NOT_SPECIFIED) { // then do SQL::GetInt()
            $Set = TBL_AttributeData::GetSetFromID($Database, $ID);

            $AttributeTypeID = $Set->Field("AttributeTypeID");
        }

        TBL_AttributeData::DoUpdate($Database, $ID, $AttributeTypeID, $AttributeValue, $SubplotID, $Uncertainty);

        REL_SpatialGriddedToOrganismInfo::UpdateAttributeData($Database, null, $ID, true);

        return($ID);
    }

    //**********************************************************************************
    public static function Delete($dbConn, $ID) {
        TBLDBTables::Delete($dbConn, "TBL_AttributeData", $ID);
    }

    //**********************************************************************************
    //	Additional Functions
    //**********************************************************************************
    public static function GetValueFromID($Database, $AttributeDataID) {
        $Value = null;

        $Set = TBL_AttributeData::GetSetFromID($Database, $AttributeDataID);
        $NumDecimals = $Set->Field("NumDecimals");

        $FormEntryID = $Set->Field("FormEntryID");
        //DebugWriteln("FormEntryID=$FormEntryID");
        if ($FormEntryID > 0) {
            $FormEntrySet = TBL_FormEntries::GetSetFromID($Database, $FormEntryID);
            $UnitID = $FormEntrySet->Field("UnitID");

            //$Standard=LKU_Units::GetFieldValue($Database,"Standard",$UnitID);
        } else {
            $Set = TBL_AttributeData::GetSetFromID($Database, $AttributeDataID);
            $UnitID = $Set->Field("UnitID");
        }
        $AttributeTypeID = $Set->Field("AttributeTypeID");

        $AttributeTypeSet = LKU_AttributeTypes::GetSetFromID($Database, $AttributeTypeID);

        $UpdateString = null;

        $ValueType = $AttributeTypeSet->Field("ValueType");

        //DebugWriteln("ValueType=$ValueType");
        switch ($AttributeTypeSet->Field("ValueType")) {
            case ATTRIBUTE_UNKNOWN:
                break;
            case ATTRIBUTE_LOOKUP:
                $Value = $Set->Field("AttributeValueID");
                break;
            case ATTRIBUTE_FLOAT:
                $Value = $Set->Field("FloatValue");
                //DebugWriteln("Value=".(float)$Value);
                //if (!$Standard)
                {
                    // convert number stored in $Title to what was specified by form entry id
                    //$Value=LKU_Units::ConvertFromStandard($Database,$UnitID,$Value);
                }

                //$Value=(float)$Value;
                if ($FormEntryID > 0) {
                    $Value = round($Value, $NumDecimals);
                }

                break;
            case ATTRIBUTE_INTEGER:
                $Value = $Set->Field("IntValue");
                //DebugWriteln("Value before coversion=$Value");
                //if (!$Standard)
                {
                    // convert number stored in $Title to what was specified by form entry id
                    //DebugWriteln("UnitID=$UnitID and Value=$Value");
                    //$Value=LKU_Units::ConvertFromStandard($Database,$UnitID,$Value);
                    //DebugWriteln("Value after coversion=$Value");
                }
                break;
            case ATTRIBUTE_STRING:
                $Value = $Set->Field("StringValue");
                break;
            case ATTRIBUTE_DATETIME:
                $Value = $Set->Field("DateTime");
                break;
        }
        //DebugWriteln("Value=================$Value");
        return($Value);
    }

    //**********************************************************************************
    // Additional Functions
    //**********************************************************************************
    //**********************************************************************************
    // Table functions
    //**********************************************************************************
    public static function WriteFunctions() {
        ?>
        <SCRIPT LANGUAGE="JavaScript">
            function DoAttributeDataEdit(WebSiteID, AttributeDataID, CallingPage)
            {
                window.location = "/cwis438/contribute/AttributeData_Edit.php" +
                        "?AttributeDataID=" + AttributeDataID +
                        "&TakeAction=Edit" +
                        "&CallingPage=" + CallingPage +
                        "&WebSiteID=" + WebSiteID;
            }
            function DoAttributeDataDelete(WebSiteID, AttributeDataID, CallingPage)
            {
                if (confirm("Are you sure you want to delete this item?"))
                {
                    window.location = "/cwis438/contribute/AttributeData_Edit.php" +
                            "?AttributeDataID=" + AttributeDataID +
                            "&TakeAction=Delete" +
                            "&CallingPage=" + CallingPage +
                            "&WebSiteID=" + WebSiteID;
                }
            }

        </SCRIPT>
        <?php
    }

    public static function GetTitleFromSet($Database, $AttributeDataSet, $CallingPage = null, $CallingLabel = null, $CanEditData = FALSE) {
        $AttributeDataID = $AttributeDataSet->Field("ID");
        $AttributeTypeID = $AttributeDataSet->Field("AttributeTypeID");

        $UnitID = -1;

        $UnitID = $AttributeDataSet->Field("UnitID");

        $FormEntryID = $AttributeDataSet->Field("FormEntryID");
        //DebugWriteln("FormEntryID$FormEntryID and AttributeTypeID=$AttributeTypeID");

        if ($FormEntryID > 0) {
            $FormEntrySet = TBL_FormEntries::GetSetFromID($Database, $FormEntryID);
            $OptionalLabel = $FormEntrySet->Field("Name");
            if ($UnitID < 0) {
                $UnitID = $FormEntrySet->Field("UnitID");
            } else {  // bulk uploaded with no UnitID
                //$UnitID="dumb";
            }
        } else {
            $OptionalLabel = "";
        }



        //DebugWriteln("Standard=$Standard UnitID=$UnitID"); <------------------------ Useful debug

        $AttributeTypeSet = LKU_AttributeTypes::GetSetFromID($Database, $AttributeDataSet->Field("AttributeTypeID"));

        $ValueType = $AttributeTypeSet->Field("ValueType");
        $IsSelect = false;
        //DebugWriteln("ValueType====$ValueType");

        switch ($ValueType) {
            case Constants::ATTRIBUTE_TYPE_VALUETYPE_LOOKUP: {
                    $IsSelect = true;
                    $AttributeValueID = $AttributeDataSet->Field("AttributeValueID");
                }
                break;
            case Constants::ATTRIBUTE_TYPE_VALUETYPE_FLOAT: // 2
                {
                    $AttributeValueID = null;
                    //DebugWriteln("ValueType------------------------------>$ValueType");
                    $AttributeValue = $AttributeDataSet->Field("FloatValue");
                    $NumDecimals = $AttributeDataSet->Field("NumDecimals");

                    if ($AttributeValue == null) {
                        $AttributeValue = $AttributeDataSet->Field("IntValue");
                    }

                    // if ($Standard==false) // if this was entered in non-standradd units, convert back to non-standard
                    {
                        //$AttributeValue=LKU_Units::ConvertFromStandard($Database, $UnitID, $AttributeValue);
                    }

                    if ($NumDecimals != null) {
                        $AttributeValue = round($AttributeValue, $NumDecimals);
                    } else {
                        $AttributeValue = round($AttributeValue, 6);
                    }
                }
                break;
            case Constants::ATTRIBUTE_TYPE_VALUETYPE_INTEGER: {
                    $AttributeValueID = null;

                    $AttributeValue = $AttributeDataSet->Field("IntValue");

                    if ($AttributeValue == null) {
                        $AttributeValue = $AttributeDataSet->Field("FloatValue");
                    }

                    //if ($Standard==false) // if this was entered in non-standradd units, convert back to non-standard
                    {
                        //$AttributeValue=LKU_Units::ConvertFromStandard($Database, $UnitID, $AttributeValue);
                    }
                }
                break;
            case ATTRIBUTE_TYPE_VALUETYPE_STRING: {
                    $AttributeValueID = null;

                    $AttributeValue = $AttributeDataSet->Field("StringValue");
                }
                break;
            case ATTRIBUTE_TYPE_VALUETYPE_DATETIME: {
                    $AttributeValueID = null;
                    $AttributeValue = $AttributeDataSet->Field("DateTime");
                    $AttributeValue = date("F j, Y g:i A", strtotime($AttributeValue));
                }
                break;
        }

        //$AttributeValueID=$AttributeDataSet->Field("AttributeValueID");
        //DebugWriteln("AttributeValueID=$AttributeValueID ; AttributeValue=$AttributeValue");
        // create the title

        if (($OptionalLabel != "") || ($OptionalLabel != null)) {
            $TitleName = "$OptionalLabel: ";
        } else {
            $TitleName = $AttributeTypeSet->Field("Name") . ": ";
        }

        //DebugWriteln("AttributeValueID===$AttributeValueID");
        //DebugWriteln("AttributeValue---------------------------=$AttributeValue");

        $Title = "";

        if ($AttributeValueID == null) {
            //DebugWriteln("aaa AttributeDataID=$AttributeDataID");
            //$Title.=TBL_AttributeData::GetValueFromID($Database,$AttributeDataID);
            //$AttributeValue=rtrim(sprintf('%.20F',$AttributeValue),'0');

            $Title.=$AttributeValue;
        } else {
            //DebugWriteln("sss");
            $Title.=LKU_AttributeValues::GetNameForID($Database, $AttributeValueID);
        }

        // convert number stored in $Title to what was specified by form entry id
        //$Title=GetLink("/cwis438/Browse/Project/AttributeData_Info.php",$Title,
        //	"AttributeDataID=$AttributeDataID&CallingPage=$CallingPage&CallingLabel=$CallingLabel");
        if ($IsSelect) {
            $DataType = "data-type='select'";
        } else {
            $DataType = "data-type='text'";
        }
        if ($CanEditData) {
            $Title = "<a href='#' class='visit_attribute' $DataType data-pk='$AttributeDataID'>$Title</a>";
            //DebugWriteln("zzzzzz AttributeValueID=$AttributeValueID");
        }
        if ($AttributeValueID == null) {
            $UnitName = LKU_Units::GetNameFromID($Database, $UnitID);
            if ($UnitName != "Unknown") {
                $Title.=" " . LKU_Units::GetNameFromID($Database, $UnitID) . "";
            }
        }
        //DebugWriteln("Title=$Title");
        // add a subplot if there is one

        $SubplotTypeID = $AttributeDataSet->Field("SubplotID");
//		DebugWriteln("SubplotTypeID=$SubplotTypeID");

        if ($SubplotTypeID > 0) {
            $Title.=" (Subplot: " . LKU_SubplotTypes::GetTypeNameFromID($Database, $SubplotTypeID) . ")";
        }

        return($TitleName . $Title);
    }

    public static function WriteHeadlineRowFromSet($Database, $TheTable, $AttributeDataSet, $CanEditData, $CallingPage) {

        $WebsiteID = GetWebsiteID();

        $AttributeDataID = $AttributeDataSet->Field("ID");

        $Title = TBL_AttributeData::GetTitleFromSet($Database, $AttributeDataSet, null, null, $CanEditData);

        $PhotoLink = "&nbsp;";

        $Details = $AttributeDataSet->Field("Comments");

        // setup the options

        $Options = null;
        if ($CanEditData) {
//			$Options="<input type='button' value='Edit' onClick='DoAttributeDataEdit(".GetWebSiteID().",$AttributeDataID,\"$CallingPage)\")'/>\n";
            $Options.="<input type='button' class='btn btn-default' value='Delete' onClick='DoAttributeDataDelete(" . GetWebSiteID() . ",$AttributeDataID,\"$CallingPage\")'/>\n";
        }

        // write the row

        if ($WebsiteID == 7) {
            echo("<div style='width:700px; border-top:1px solid #8B9A84; border-top-length:50px;'>");
            $PhotoLink = null;
            $Content = null;
            $Content.=$Title;
            echo("$Content");
            echo("<div style='float:right;'>$Options</div>");
            echo("<div style='margin-left:50px;'>$Details</div>");
            echo("</div></br>");
        } else {
            $TheTable->HeadlineRow(null, $Title, $Details, $Options);  //HeadlineRow($PhotoLink,$Title,$Details="&nbsp;",$Options="",$LineBreakAfterTitle=true,$Width=160)
        }
    }

}
?>
