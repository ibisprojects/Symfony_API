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

use API\Classes\Constants;
use Classes\TBLDBTables;
use Classes\Utilities\SQL;

//require_once("C:\inetpub\wwwroot\src\Classes\DBTable\LKUCoordinateSystems.php");
//require_once("C:\inetpub\wwwroot\src\Classes\DBTable\LKUAreaSubtypes.php");

//**************************************************************************************
// Definitions
//**************************************************************************************
// Defines taxonomic identification confidence; null is no reported uncertainty

$ConfidenceStrings = array("No reported uncertainty", "Certain", "Uncertain of variety", "Uncertain of subspecies",
    "Uncertain of species", "Uncertain of genus", "Uncertain of family");

$OrganismDataStatusStrings = array("Not Reviewed", "Approved", "Rejected"); // 0,1,2 (GJN used by GLEDN) 1=APPROVED! 1 is VERIFIED!

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

    public static function SetFieldValue($dbConn, $FieldName, $ID, $Value) {
        TBLDBTables::SetFieldValue($dbConn, "TBL_OrganismData", $FieldName, $ID, $Value);
    }

    //******************************************************************************
    // Basic database functions
    //******************************************************************************

    public static function GetSet($dbConn, $VisitID = null, $SubplotID = null, $OrganismInfoID = null) {
        $SelectString = "SELECT * ".
                "FROM \"TBL_OrganismData\" ";

        if ($VisitID !== null)
            TBLDBTables::AddWhereClause($SelectString, "\"VisitID\"=$VisitID");
        if ($SubplotID !== null)
            TBLDBTables::AddWhereClause($SelectString, "\"SubplotID\"=$SubplotID");
        if ($OrganismInfoID !== null)
            TBLDBTables::AddWhereClause($SelectString, "\"OrganismInfoID\"=$OrganismInfoID");

        $stmt = $dbConn->prepare($SelectString);
        $stmt->execute();

        $orgData = $stmt->fetch();

        if (!$orgData) {
            return false;
        }

        return $orgData;
    }

    public static function GetSetFromID($dbConn, $OrganismDataID)
    {
        $SelectString="SELECT * ".
            "FROM \"TBL_OrganismData\" ".
            "WHERE \"ID\"='".SQL::SafeInt($OrganismDataID)."'";

        $stmt = $dbConn->prepare($SelectString);
        $stmt->execute();

        $OrganismDataSet = $stmt->fetch();

        if (!$OrganismDataSet) {
            return false;
        }

        return $OrganismDataSet;
    }

    public static function Delete($dbConn, $OrganismDataID)
    {
        TBLDBTables::Delete($dbConn, "TBL_OrganismData", $OrganismDataID);
    }

    public static function AddPoint(
        $dbConn,
        $PersonID,
        $ProjectID,
        $RefX,
        $RefY,
        $CoordinateSystemID,
        $AreaName = "New Sighting",
        $VisitDate = null,
        $Present = null,
        $SubplotID = null,
        $OrganismInfoID = 0,
        $Accuracy = null,
        $UpdateExisingOrganismData = true,
        $AreaSubTypeID = null,
        $GeometryString = null,
        $AreaComments = null,
        $VisitComments = null,
        $InsertLogID = null,
        $Status = null,
        $FormID = null,
        $SelectedAreaID = null
    )
        //
        // Adds a single species occurrence to a project.  Adds a visit and area if
        // existing ones that match the coordinates/date are not found.
        //
        // 	$dbConn -
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
    {
        $ErrorString = null;
        $OrganismDataID = 0;

        TBLInsertLogs::SetFieldValue($dbConn, "UploaderID", $InsertLogID, $PersonID); // specified as current user for web pages but can be specified otherwise with PDA and Map Web Service

        // see if the point already exists

        $AreaID = $SelectedAreaID;

        if ($SelectedAreaID == null) { // see if the point exists (jjg - can make this check for geometries that match in the future)
            $AreaID = TBLAreas::GetIDFromCoordinate(
                $dbConn,
                $RefX,
                $RefY,
                Constants::COORDINATE_SYSTEM_WGS84_GEOGRAPHIC,
                1,
                $ProjectID
            ); // $CoordinateSystemID was hard coded to was STPROJECTION_GEOGRAPHIC
        }

        if ($AreaSubTypeID === null) {
            $AreaSubTypeID = Constants::AREA_SUBTYPE_POINT; // default to point?
        }

        if ($AreaID <= 0) { // add a new point area
            if ($GeometryString == null) {
                $AreaID = TBLAreas::InsertPoint(
                    $dbConn,
                    $ProjectID,
                    $InsertLogID,
                    $AreaName,
                    $SubplotID,
                    $RefX,
                    $RefY,
                    $CoordinateSystemID,
                    $Accuracy,
                    $AreaSubTypeID,
                    "",
                    $AreaComments
                ); // we need to modify AddPoint to be able to add survey types for plot types other than point
            } else {
                $AreaID = TBLAreas::InsertShape(
                    $dbConn,
                    $ProjectID,
                    $InsertLogID,
                    $AreaName,
                    $SubplotID,
                    $GeometryString,
                    $CoordinateSystemID,
                    $Accuracy,
                    $AreaSubTypeID
                );
            }
        }

        // continue with the visit

        if ($VisitDate == null) { // not specified
            $VisitDate = new Date; // set to today timestamps
        }

        $VisitDateString = $VisitDate->GetSQLString();

        // query for an existing visit?

        $Set = TBLVisits::GetSet($dbConn, null, "VisitDate", null, $AreaID, $VisitDateString);

        $VisitID = 0;

        if ($Set) {
            $VisitID = $Set["ID"]; // get the ID for the existing visit
        }

        if ($VisitID == 0) { // insert a new visit
            $VisitID = TBLVisits::Insert($dbConn, $ProjectID, $AreaID, $VisitDateString, $InsertLogID, 1, $VisitComments, $PersonID);
        }

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

        if ($Present !== null)
        {
            if ($Present)
                $PresenceAbsence = Constants::ATTRIBUTE_VALUE_PRESENT; // per LKU table; should be same on all servers
            else
                $PresenceAbsence = Constants::ATTRIBUTE_VALUE_ABSENT; // per LKU table; should be same on all servers

            $Set = TBLAttributeData::GetSet($dbConn, null, $OrganismDataID, null, null, Constants::ATTRIBUTE_PRESENCE);

            if (!$Set)
            {
                TBLAttributeData::Insert(
                    $dbConn,
                    null,
                    $OrganismDataID,
                    null,
                    Constants::ATTRIBUTE_PRESENCE,
                    $PresenceAbsence,
                    null
                ); // null is subplotid and single sightings do not have a subplot - they are all points!
            }
        }

        return($OrganismDataID);
    }

    public static function Insert($dbConn, $VisitID, $SubplotID = null, $OrganismInfoID = null, $OriginalOrganismInfoID = null, $Status = null)
    {
        if ($SubplotID == "")
            $SubplotID = 0;

        $ExecString = "INSERT INTO \"TBL_OrganismData\" (\"VisitID\") VALUES ($VisitID)";

        $stmt = $dbConn->prepare($ExecString);
        $stmt->execute();

        $OrganismDataID = $dbConn->lastInsertId('TBL_OrganismData_ID_seq');
        $stmt = null;

        $UpdateString = "UPDATE \"TBL_OrganismData\" ";

        if ($SubplotID !== null)
            TBLDBTables::AddIntUpdate($UpdateString, "SubplotID", $SubplotID);

        if ($OrganismInfoID !== null)
            TBLDBTables::AddIntUpdate($UpdateString, "OrganismInfoID", $OrganismInfoID);

        if ($OriginalOrganismInfoID !== null)
            TBLDBTables::AddIntUpdate($UpdateString, "OriginalOrganismInfoID", $OriginalOrganismInfoID);

        // if project associated with this visit is related to GLEDN and is not the GLEDN project itself, then make the Status = 1 (0 – not reviewed; 1 – Approved; 2 – Rejected)...

        if ($Status !== null) { // else if not null set the Status to whatever it was...
            TBLDBTables::AddIntUpdate($UpdateString, "Status", $Status);
        }

        $UpdateString .= " WHERE \"ID\"=$OrganismDataID";

        $stmt = $dbConn->prepare($UpdateString);
        $stmt->execute();

        $stmt = null;

        return($OrganismDataID);
    }
}
?>
