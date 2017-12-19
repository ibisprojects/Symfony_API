<?php

namespace Classes\DBTable;

//**************************************************************************************
// FileName: TBL_OrganismData.php
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

use Classes\DBTable\LKUAreaSubtypes;
use Classes\DBTable\LKUCoordinateSystems;
use Classes\DBTable\TBLAreas;

//require_once("C:\inetpub\wwwroot\src\Classes\DBTable\LKUCoordinateSystems.php");
//require_once("C:\inetpub\wwwroot\src\Classes\DBTable\LKUAreaSubtypes.php");

//**************************************************************************************
// Definitions
//**************************************************************************************
// Defines taxonomic identification confidence; null is no reported uncertainty

$ConfidenceStrings = array("No reported uncertainty", "Certain", "Uncertain of variety", "Uncertain of subspecies",
    "Uncertain of species", "Uncertain of genus", "Uncertain of family");

$OrganismDataStatusStrings = array("Not Reviewed", "Approved", "Rejected"); // 0,1,2 (GJN used by GLEDN) 1=APPROVED! 1 is VERIFIED!

define("TBL_ORGANISMDATA_STATUS_NOT_REVIEWED", 0);
define("TBL_ORGANISMDATA_STATUS_APPROVED", 1);

//**************************************************************************************
// Class Definition
//**************************************************************************************
//require_once("C:/Inetpub/wwwroot/cwis438/Classes/DBTable/TBL_TaxonUnits.php");

class TBLOrganismData {

    //**********************************************************************************
    // TBL_DBTables functions
    //**********************************************************************************
    public static function GetFieldValue($Database, $FieldName, $ID, $Default = 0) {
        $Result = TBL_DBTables::GetFieldValue($Database, "TBL_OrganismData", $FieldName, $ID, $Default);

        return($Result);
    }

    public static function SetFieldValue($Database, $FieldName, $ID, $Value) {
        TBLDBTables::SetFieldValue($Database, "TBL_OrganismData", $FieldName, $ID, $Value);
    }

    //******************************************************************************
    // Basic database functions
    //******************************************************************************

    public static function GetSet($dbConn, $VisitID = null, $SubplotID = null, $OrganismInfoID = null) {
        $SelectString = "SELECT * " .
                "FROM TBL_OrganismData ";

        if ($VisitID !== null)
            TBLDBTables::AddWhereClause($SelectString, "VisitID=$VisitID");
        if ($SubplotID !== null)
            TBLDBTables::AddWhereClause($SelectString, "SubplotID=$SubplotID");
        if ($OrganismInfoID !== null)
            TBLDBTables::AddWhereClause($SelectString, "OrganismInfoID=$OrganismInfoID");
        $stmt = $dbConn->prepare($SelectString);
        $stmt->execute();
        $orgData = $stmt->fetch();
        if (!$orgData) {
            return false;
        }
        return $orgData;
    }

    public static function GetSetFromID($dbConn, $OrganismDataID) {
        $SelectString = "SELECT * " .
                "FROM TBL_OrganismData " .
                "WHERE ID='" . $OrganismDataID . "'";

        $stmt = $dbConn->prepare($SelectString);
        $stmt->execute();
        $orgData = $stmt->fetch();
        if (!$orgData) {
            return false;
        }
        return $orgData;
    }

    public static function GetSciNameFromOrgInfoName($Database, $OrganismInfoName) {
        $SelectString = "SELECT TBL_TaxonUnits.UnitName1, TBL_TaxonUnits.UnitName2, TBL_TaxonUnits.UnitName3, TBL_OrganismInfos.Name
                       FROM TBL_OrganismInfos INNER JOIN
                       REL_OrganismInfoToTSN ON TBL_OrganismInfos.ID = REL_OrganismInfoToTSN.OrganismInfoID INNER JOIN
                       TBL_TaxonUnits ON REL_OrganismInfoToTSN.TSN = TBL_TaxonUnits.TSN
                       WHERE (TBL_OrganismInfos.Name = '$OrganismInfoName')";

        $Set = $Database->Execute($SelectString);

        $UnitName1 = $Set->Field("UnitName1");
        $UnitName2 = $Set->Field("UnitName2");
        $UnitName3 = $Set->Field("UnitName3");

        $SciName = "$UnitName1 $UnitName2 $UnitName3 ($OrganismInfoName)";

        return ($SciName);
    }

    public static function Insert($dbConn, $VisitID, $SubplotID = null, $OrganismInfoID = null, $OriginalOrganismInfoID = null, $Status = null) {
        $InsertLogID = TBLVisits::GetFieldValue($dbConn, "InsertLogID", $VisitID);

        if ($SubplotID == "")
            $SubplotID = 0;

        $ExecString = "EXEC insert_TBL_OrganismData $VisitID";
        $stmt = $dbConn->prepare($ExecString);
        $stmt->execute();
        $OrganismDataID = $dbConn->lastInsertId();
        $UpdateString = "UPDATE TBL_OrganismData ";

        if ($SubplotID !== null)
            TBLDBTables::AddIntUpdate($UpdateString, "SubplotID", $SubplotID);
        if ($OrganismInfoID !== null)
            TBLDBTables::AddIntUpdate($UpdateString, "OrganismInfoID", $OrganismInfoID);
        if ($OriginalOrganismInfoID !== null)
            TBLDBTables::AddIntUpdate($UpdateString, "OriginalOrganismInfoID", $OriginalOrganismInfoID);
        if ($Status !== null)
            TBLDBTables::AddIntUpdate($UpdateString, "Status", $Status);

        $UpdateString.=" WHERE ID=$OrganismDataID";
        $stmt = $dbConn->prepare($UpdateString);
        $stmt->execute();
        $ProjectID = TBLVisits::GetFieldValue($dbConn, "ProjectID", $VisitID);
        if ($Status < 1) { // send verifiers notification to go verify these new unverified data records; a null or a 0 are both situations where we want to send emails to verifiers
            $SelectString = "SELECT ID, ProjectID, WebsiteID
		        FROM REL_WebsiteToProject
		        WHERE (ProjectID = $ProjectID) AND (WebsiteID = 17)";

            $stmt = $dbConn->prepare($SelectString);
            $stmt->execute();
            $IsGLEDNSet = $stmt->fetch();
            if ($IsGLEDNSet) { // GLEDN
                $WebSiteID = 17;
                // send verification notices to experts about new, not approved, organism data records to have them go verify it (GLEDN only)
                // check if there are already visits that have been added for this insertlog, if so, ...
                // if this addition is a Type 9 then do not send alerts at all; only send for guaranteed single point additions...
                $InsertLogType = TBLInsertLogs::GetFieldValue($dbConn, "Type", $InsertLogID);
                if ($InsertLogType <> 9) {
                    //TBL_Alerts::SendAlerts($Database, ALERT_TYPE_VERIFICATION_ALERT, $OrganismDataID, $WebSiteID);
                }
            }
        } else if ($Status == 1) {
            $WebSiteID = GetWebSiteID();

            if ($WebSiteID == 17) { // GLEDN
                // send alerts to users who have set up their settings to receive them (GLEDN only)
                $InsertLogType = TBLInsertLogs::GetFieldValue($Database, "Type", $InsertLogID);

                if ($InsertLogType <> 9) {
                    //TBL_Alerts::SendAlerts($Database, ALERT_TYPE_ALERT, $OrganismDataID);
                }
            }
        }
        //RELSpatialGriddedToOrganismInfo::AddSpatialGridRelationship($Database, $OrganismDataID);
        return($OrganismDataID);
    }

    public static function SmartUpdate($Database, $OrganismDataID, $VisitID, $SubplotID, $Comments, $Confidence, $Present, $Status = NOT_SPECIFIED) {
        // check if we need to insert the record

        if ($OrganismDataID <= 0) {
            //		DebugWriteln("VisitID=$VisitID");
            $OrganismDataID = TBL_OrganismData::Insert($Database, $VisitID);
            //		DebugWriteln("OrganismDataID 2=$OrganismDataID");
        }

        // do the update

        $UpdateString = "UPDATE TBL_OrganismData " .
                "SET SubplotID='$SubplotID', " .
                "Comments='$Comments', " .
                "Confidence='$Confidence' ";

        if ($Status !== NOT_SPECIFIED) {
            $UpdateString.=", Status='$Status' ";
        }

        $UpdateString.="WHERE ID=$OrganismDataID";

        //DebugWriteln("UpdateString=$UpdateString");

        $Database->Execute($UpdateString);

        // set the presence attribute

        $AttributeTypeID = ATTRIBUTE_PRESENCE;

        $Presence = ATTRIBUTE_VALUE_ABSENT; // per LKU table; should be same on all servers
        if ($Present)
            $Presence = ATTRIBUTE_VALUE_PRESENT;

        //DebugWriteln("Subplot= $SubplotID Presence=$Presence OrganismDataID=$OrganismDataID ");
        // check to make sure a presence attribute already exists, if so, do NOT add another and instead update existing one

        $ExistingAttributeSet = TBL_AttributeData::GetSet($Database, null, $OrganismDataID, null, null, ATTRIBUTE_PRESENCE);

        //DebugWriteln("22");

        if ($ExistingAttributeSet->FetchRow()) { // update existing one
            //DebugWriteln("1");
            TBL_AttributeData::Update($Database, $ExistingAttributeSet->Field("ID"), $Presence, ATTRIBUTE_PRESENCE, $SubplotID, NOT_SPECIFIED); // uncertainty may not make sense for P/A
            //DebugWriteln("2");
        } else { // insert a new one
            TBL_AttributeData::Insert($Database, null, $OrganismDataID, null, ATTRIBUTE_PRESENCE, ATTRIBUTE_VALUE_PRESENT, $SubplotID);

            //DebugWriteln("3");
        }

        // Add recorder and authority based on logged in user - gjn

        $ExistingRecorderID = TBL_Visits::GetFieldValue($Database, "RecorderID", $VisitID);

        //DebugWriteln("ExistingRecorderID=$ExistingRecorderID");
        if (($ExistingRecorderID < 0) || ($ExistingRecorderID == null) | ($ExistingRecorderID == "")) { // if not previous set, set it with logged in user
            //DebugWriteln("ExistingRecorderID==============$ExistingRecorderID");
            $UserID = GetUserID();

            TBL_Visits::SetFieldValue($Database, "RecorderID", $VisitID, $UserID);
        }

        // TBL_Visits::SetFieldValue($Database,"AuthorityID",$VisitID,$UserID);
        // check for any user specified alerts that would need to be sent if we are setting the status to approved
        // ***************************************************************************************************************************************
        // for records recently inserted/updated, if status is APPROVED, send alerts to users wanting them where record meets their alert criteria
        // ***************************************************************************************************************************************

        if ($Status == TBL_ORGANISMDATA_STATUS_APPROVED) { // 1 = Approved
            TBL_Alerts::SendAlerts($Database, ALERT_TYPE_ALERT, $OrganismDataID);
        }

        // ***************************************************************************************************************************************
        // if status is NOT APPROVED, send alerts to experts for verification
        // ***************************************************************************************************************************************

        if ($Status == TBL_ORGANISMDATA_STATUS_NOT_REVIEWED) { // 0 = not reviewed
            TBL_Alerts::SendAlerts($Database, ALERT_TYPE_VERIFICATION_ALERT, $OrganismDataID);
        }

        return($OrganismDataID);
    }

    public static function Delete($Database, $OrganismDataID) {
//		DebugWriteln("TBL_OrganismData calling REL_SpatialGriddedToOrganismInfo::RemoveSpatialGridRelationship");
        REL_SpatialGriddedToOrganismInfo::RemoveSpatialGridRelationship($Database, $OrganismDataID);

        TBL_DBTables::Delete($Database, "TBL_OrganismData", $OrganismDataID);
    }

    //*******************************************************************
    // Functions to add sightings
    //*******************************************************************
    public static function AddPoint($dbConn, $PersonID, $ProjectID, $RefX, $RefY, $CoordinateSystemID, $AreaName = "New Sighting", $VisitDate = null, $Present = null, $SubplotID = null, $OrganismInfoID = 0, $Accuracy = null, $UpdateExisingOrganismData = true, $AreaSubTypeID = null, $GeometryString = null, $AreaComments = null, $VisitComments = null, $InsertLogID = null, $Status = null, $FormID = null, $SelectedAreaID = null) {
        //
        // Adds a single speices occurence to a project.  Adds a visit and area if 
        // existing ones that match the coordinates/date rae not found.
        //
	// 	$Database -
        // 	$PersonID - the uploader of the data
        // 	$ProjectID - project to add the data to
        //	$RefX - Longitude or Easting
        //	$RefY - Latitude or Northing
        //	$CoordinateSystemID - only Geographic and UTM are supported!
        //	$AreaName - name of a new area if one is added
        //	$VisitDate - Date object, if missing, will insert the current date
        //	$Present - true to add presence value, false to add absence
        //  $SubplotID - optional SubplotID 
        //  $SelectedAreaID - user selected predefined location from picklist

        $ErrorString = null;
        $OrganismDataID = 0;
        $AreaID = 0;
        if ($SelectedAreaID != null) {  // if user selected predefined location, use it as AreaID
            $AreaID = $SelectedAreaID;
        } else if ($GeometryString == null) { // see if the point exists (jjg - can make this check for geometries that match in the future)
            $AreaID = TBLAreas::GetIDFromCoordinate($dbConn, $RefX, $RefY, 1, 1, $ProjectID); // $CoordinateSystemID was hard coded to was STPROJECTION_GEOGRAPHIC
        }

        if ($AreaSubTypeID === null) {
            $AreaSubTypeID = 11; // default to point?
        }
        if ($AreaID <= 0) { // add a new point area
            if ($GeometryString == null) {
                $AreaID = TBLAreas::InsertPoint($dbConn, $ProjectID, $InsertLogID, $AreaName, $SubplotID, $RefX, $RefY, $CoordinateSystemID, $Accuracy, $AreaSubTypeID, "", $AreaComments); // we need to modify AddPoint to be able to add survey types for plot types other than point                
            } else {
                $AreaID = TBLAreas::InsertShape($dbConn, $ProjectID, $InsertLogID, $AreaName, $SubplotID, $GeometryString, $CoordinateSystemID, $Accuracy, $AreaSubTypeID); // we need to modify AddPoint to be able to add survey types for plot types other than point
            }
        }

        if ($ErrorString != NULL)
            print_r("<b>Error=$ErrorString</b>");
        else {
            if ($VisitDate == null) { // not specified)
                $VisitDate = date("m-d-Y"); // set to today timestamps
            }
            $VisitDateString = $VisitDate;
            $Set = TBLVisits::GetSet($dbConn, null, "VisitDate", null, $AreaID, $VisitDateString); // $VisitDate->GetSQLString()

            $VisitID = 0;
            if ($Set) { // if existing visit...
                $VisitID = $Set["ID"]; // get the ID for the existing visit
            }

            if ($VisitID == 0) { // insert a new visit
                $VisitID = TBLVisits::Insert($dbConn, $ProjectID, $AreaID, $VisitDateString, $InsertLogID, 1, $VisitComments, $PersonID); // $VisitDate->GetSQLString()
            }

            // update the InsertLogID.Date field accordingly
            //TBL_InsertLogs::SetFieldValue($Database,"DateUploaded",$InsertLogID,$VisitDateString);
            // insert the organism

            if ($UpdateExisingOrganismData) {
                $Set = TBLOrganismData::GetSet($dbConn, $VisitID, $SubplotID, $OrganismInfoID);

                $OrganismDataID = 0;

                if ($Set) {
                    $OrganismDataID = $Set["ID"]; // get the ID for the existing organismdata
                }
            }

            if ($OrganismDataID == 0) { // insert a new organismdata                
                $OrganismDataID = TBLOrganismData::Insert($dbConn, $VisitID, $SubplotID, $OrganismInfoID, null, $Status);
            }

            // update the attribute

            if ($Present !== null) {
//				DebugWriteln("Presnt!=null");
                if ($Present)
                    $PresenceAbsence = ATTRIBUTE_VALUE_PRESENT; // per LKU table; should be same on all servers
                else
                    $PresenceAbsence = ATTRIBUTE_VALUE_ABSENT; // per LKU table; should be same on all servers

                $Set = TBLAttributeData::GetSet($dbConn, null, $OrganismDataID, null, null, ATTRIBUTE_PRESENCE);

                if ($Set) {
                    
                } else { // insert a new organismdata
//					DebugWriteln("TBL_AttributeData::Insert, PresenceAbsence=$PresenceAbsence");
                    TBLAttributeData::Insert($dbConn, null, $OrganismDataID, null, ATTRIBUTE_PRESENCE, $PresenceAbsence, null); // GJN - null is subplotid and single sightings do not have a subplot - they are all points!
                }
            }
        }
        //DebugWriteln("TBL_OrganismData::InsertLog ----> $InsertLogID");
        return($OrganismDataID);
    }

    public static function UpdatePoint($Database, $OrganismDataID, $ProjectID, $RefX, $RefY, $CoordinateSystemID, $AreaName = "New Sighting", $VisitDate = null, $Present = null, $SubplotID = null, $OrganismInfoID = 0, $Accuracy = null) {
        //DebugWriteln("UpdatePoint: OrganismDataID=$OrganismDataID");

        $OrganismDataSet = TBL_OrganismData::GetSetFromID($Database, $OrganismDataID);

        // get visit info

        $VisitID = $OrganismDataSet->Field("VisitID");

        $VisitSet = TBL_Visits::GetSetFromID($Database, $VisitID);

        $InsertLogID = $VisitSet->Field("InsertLogID");

        $AreaID = $VisitSet->Field("AreaID");

        // update the area

        $UpdateString = "UPDATE TBL_Areas " .
                "SET AreaName='$AreaName' " .
                "WHERE ID=$AreaID";

        //DebugWriteln("UpdateString=$UpdateString");

        $Database->Execute($UpdateString);

        // delete the existing spatiallayerdata

        TBL_Areas::UpdatePoint($Database, $AreaID, $RefX, $RefY, $CoordinateSystemID);

        // update the visit

        $UpdateString = "UPDATE TBL_Visits " .
                "SET ProjectID=$ProjectID ";

        if ($VisitDate !== null)
            $UpdateString.=",VisitDate='" . $VisitDate->GetSQLString() . "' ";

        $UpdateString.="WHERE ID=$VisitID";

//		DebugWriteln("UpdateString=$UpdateString");

        $Database->Execute($UpdateString);

        // update the organismdata ID record

        $UpdateString = "UPDATE TBL_OrganismData " .
                "SET OrganismInfoID=$OrganismInfoID, " .
                "SubplotID=" . SQL::GetInt($SubplotID) . " " .
                "WHERE ID=$OrganismDataID";

        //DebugWriteln("UpdateString=$UpdateString");

        $Database->Execute($UpdateString);

        // update the attribute

        $Set = TBL_AttributeData::GetSet($Database, null, $OrganismDataID, null, $SubplotID, ATTRIBUTE_PRESENCE);

        if ($Set->FetchRow()) {
            $AttbributeID = $Set->Field("ID");

            $Value = ATTRIBUTE_VALUE_ABSENT;
            if ($Present)
                $Value = ATTRIBUTE_VALUE_PRESENT;

            $UpdateString = "UPDATE TBL_AttributeData " .
                    "SET AttributeValueID=$Value " .
                    "WHERE ID=$AttbributeID";

            //DebugWriteln("UpdateString=$UpdateString");

            $Database->Execute($UpdateString);
        }
    }

    public static function GetPoint($Database, $OrganismDataID, $CoordinateSystemID, &$ProjectID, &$RefX, &$RefY, &$AreaName, &$VisitDate, &$Present, &$SubplotID, &$OrganismInfoID, &$Accuracy) {
        //
        // Purpose:
        //	Returns the current values for a point given an OrganismDataID
        // Inputs:
        //	$Database
        //	$OrganismDataID
        // Returns:
        //	$ProjectID
        //	$RefX, $RefX
        //	$CoordinateSystemID
        //	$AreaName
        //	$VisitDate
        //	$Present
        //	$SubplotID
        //	$OrganismInfoID
        //	$Accuracy//
        // get the organism data

        $OrganismDataSet = TBL_OrganismData::GetSetFromID($Database, $OrganismDataID);

        $SubPlotID = $OrganismDataSet->Field("SubPlotID");
        $OrganismInfoID = $OrganismDataSet->Field("OrganismInfoID");

        // get visit info

        $VisitID = $OrganismDataSet->Field("VisitID");

        $VisitSet = TBL_Visits::GetSetFromID($Database, $VisitID);

        $ProjectID = $VisitSet->Field("ProjectID");

        $VisitDate = new Date();
        $VisitDate->SetDateFromSQLString($VisitSet->Field("VisitDate"));

        // get the area info

        $AreaID = $VisitSet->Field("AreaID");

        $AreaSet = TBL_Areas::GetSetFromID($Database, $AreaID);

        $AreaName = $AreaSet->Field("AreaName");
        $Accuracy = $AreaSet->Field("Uncertainty");

        // get the spatial data

        $SpatialDataSet = TBL_SpatialLayerData::GetSetFromAreaID($Database, $AreaID, $CoordinateSystemID);

        $RefX = $SpatialDataSet->Field("RefX");
        $RefY = $SpatialDataSet->Field("RefY");

        // get the present attribute

        $Present = false;
        $AttributeSet = TBL_AttributeData::GetSet($Database, null, $OrganismDataID, null, null, ATTRIBUTE_PRESENCE);

        if ($AttributeSet->FetchRow()) {
            if ($AttributeSet->Field("AttributeValueID") == ATTRIBUTE_VALUE_PRESENT) {
                $Present = true;
            }
        }
    }

    //***************************************************************************************
    // Table Functions
    //***************************************************************************************
    public static function WriteFunctions() {
        ?>
        <SCRIPT LANGUAGE="JavaScript">
            function DoOrganismDataEdit(WebSiteID, VisitID, OrganismDataID, CallingPage)
            {
                window.location = "/cwis438/contribute/OrganismData_Edit.php" +
                        "?OrganismDataID=" + OrganismDataID +
                        "&TakeAction=Edit" +
                        "&CallingPage=" + CallingPage +
                        "&WebSiteID=" + WebSiteID;
            }
            function DoOrganismDataDelete(WebSiteID, VisitID, OrganismDataID, CallingPage)
            {
                if (confirm("Are you sure you want to delete this sighting?"))
                {
                    window.location = "/cwis438/contribute/OrganismData_Edit.php" +
                            "?OrganismDataID=" + OrganismDataID +
                            "&TakeAction=Delete" +
                            "&CallingPage=" + CallingPage +
                            "&WebSiteID=" + WebSiteID;
                }
            }

        </SCRIPT>
        <?php
    }

    public static function WriteHeadlineRowFromSet($Database, $TheTable, $OrganismDataSet, $CanEditData, $CallingPage, $CallingLabel = null) {
        global $Duration3;

        $WebsiteID = GetWebsiteID();

        $StartTime3 = GetMicrotime();
        $OrganismDataID = $OrganismDataSet->Field("ID");
        $SubplotTypeID = $OrganismDataSet->Field("SubplotID");
        $VisitID = $OrganismDataSet->Field("VisitID");
        $OrganismInfoID = $OrganismDataSet->Field("OrganismInfoID");
        //DebugWriteln("orginfoid=$OrganismInfoID");
        $Label = TBL_OrganismInfos::GetName($Database, $OrganismInfoID);

        if ($SubplotTypeID > 0) {
            $Label.=" (Subplot: " . LKU_SubplotTypes::GetTypeNameFromID($Database, $SubplotTypeID) . ")";
        }

        //$Title=GetLink("/cwis438/Browse/Project/OrganismData_Info.php",$Label,
        //	"OrganismDataID=$OrganismDataID&CallingPage=$CallingPage&CallingLabel=$CallingLabel");	
        // get the photo for the org data record

        $Title = $Label;

        $MediaSet = TBL_Media::GetSetFromOrganismDataID($Database, $OrganismDataID);

        $PhotoLink = "&nbsp;";
        if ($MediaSet->FetchRow()) {
            //$PhotoLink=TBL_Media::GetImgTagFromSet($Database,$MediaSet,null,0,0,TBL_MEDIA_VERSION_THUMBNAIL);

            $FilePath = TBL_Media::GetFilePathFromSet($MediaSet, "_thumbnails", false, true); // last true says this is an organism photo for app purposes

            $PhotoLink = GetLink("/cwis438/Browse/Media_Info.php", "<img src='$FilePath' border='0' width='160'>", "MediaID=" . $MediaSet->Field("ID"));
            $PhotoLink = "<div class='ImageThumbnail'>$PhotoLink</div>";
        } else {
            $PhotoLink = null;
        }
        // ----- we could use the default orginfoid photo for the organism info instead if no org data photo exists. ------ gjn
        //else
        //{
        //$MediaSet=TBL_Media::GetSetFromOrganismInfoID($Database,$OrganismInfoID);
        //if ($MediaSet->FetchRow()) $PhotoLink=TBL_Media::GetImgTagFromSet($Database,$MediaSet,null,0,0,TBL_MEDIA_VERSION_THUMBNAIL);			
        //}
        // setup the details including the attributes

        $Details = $OrganismDataSet->Field("Comments");

        $AttributeSet = TBL_AttributeData::GetSet($Database, null, $OrganismDataID);

        while ($AttributeSet->FetchRow()) {
            $Details.="<br>" . TBL_AttributeData::GetTitleFromSet($Database, $AttributeSet, $CallingPage, $CallingLabel, $CanEditData); // // &nbsp;&nbsp;&nbsp;&nbsp;
        }
        // setup the options

        $Options = null;
        if ($CanEditData) {
            $Options.="<input type='button' class='btn btn-default' value='Delete' onClick='DoOrganismDataDelete(" . GetWebSiteID() . ",$VisitID,$OrganismDataID,\"$CallingPage\")'/>\n";
        }

        // write the row

        if ($WebsiteID == 7) {
            echo("<div style='width:700px; border-top:1px solid #8B9A84; border-top-length:50px;'>");
            //$PhotoLink=null;
            $Content = null;
            $Content.="<div style='float:left;'>" . $PhotoLink . "</div>";
            $Content.=$Title;
            echo("$Content");
            echo("<div style='float:right;'>$Options</div>");
            echo("<div style='margin-left:50px;'>$Details</div>");
            echo("</div></br>");
        } else {
            $TheTable->HeadlineRow($PhotoLink, $Title, $Details, $Options);  //HeadlineRow($PhotoLink,$Title,$Details="&nbsp;",$Options="",$LineBreakAfterTitle=true,$Width=160)
        }


        $Duration3+=GetMicrotime() - $StartTime3;
//		DebugWriteln("Duration3=$Duration3");
    }

}
?>
