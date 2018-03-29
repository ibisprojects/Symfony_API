<?php

namespace Classes\DBTable;

//**************************************************************************************
// FileName: TBL_Visits.php
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
use Classes\DBTable\Date;
use Classes\Utilities\SQL;
use API\Classes\Constants;

//*********************************************************************************
//	Definitions
//*********************************************************************************

define("VISITSTATUS_NONE", 0); // Unknown?
define("VISITSTATUS_NEW", 1);
define("VISITSTATUS_UNDER_REVIEW", 2);
define("VISITSTATUS_APPROVED", 3);
define("VISITSTATUS_DISALLOWED", 4);

$VisitStatusStrings = array("--- Select A Status ---", "New", "Under Review", "Approved", "Disallowed"); // first entry Unknown? None?
//**************************************************************************************
// Class Definition
//**************************************************************************************
class TBLVisits {

    //******************************************************************************
    // Private functions
    //******************************************************************************
    public static function AddSearchWhereClause($Database, &$SelectString, $ProjectID = Constants::NOT_SPECIFIED, $AreaID = Constants::NOT_SPECIFIED, $OrganismInfoID = Constants::NOT_SPECIFIED, $InsertLogID = Constants::NOT_SPECIFIED, $RefX = Constants::NOT_SPECIFIED, $RefY = Constants::NOT_SPECIFIED, $RefWidth = Constants::NOT_SPECIFIED, $RefHeight = Constants::NOT_SPECIFIED, $NumPresent = Constants::NOT_SPECIFIED, $NumAbsent = Constants::NOT_SPECIFIED) {
    //
    // Adds the appropriate search criteria to the provided search string.
    // This public static function will only return select strings for non-survey areas.//

//		DebugWriteln("NumPresent=$NumPresent");
//		DebugWriteln("NumAbsent=$NumAbsent");

        if (($OrganismInfoID != Constants::NOT_SPECIFIED) && ($OrganismInfoID > 0)) {
            if ((($NumPresent > 0) && ($NumAbsent == 0)) ||
                    (($NumPresent == 0) && ($NumAbsent > 0))) { // if they are both 1's or both 0's we do not care about the attributes
                $TempString = "SELECT TBL_OrganismData.VisitID " .
                        "FROM TBL_OrganismData " .
                        "INNER JOIN TBL_AttributeData ON TBL_AttributeData.OrganismDataID=TBL_OrganismData.ID " .
                        "WHERE OrganismInfoID=$OrganismInfoID " .
                        "AND AttributeTypeID=" . ATTRIBUTE_PRESENCE . " "; // can only do

                if ($NumPresent > 0) {
                    $TempString.="AND AttributeValueID=" . ATTRIBUTE_VALUE_PRESENT . " ";
                } else if ($NumAbsent > 0) {
                    $TempString.="AND AttributeValueID=" . ATTRIBUTE_VALUE_ABSENT . " ";
                }
//				DebugWriteln("TempString=$TempString");
            } else { // just do the organism info
                $TempString = "SELECT TBL_OrganismData.VisitID " .
                        "FROM TBL_OrganismData " .
                        "WHERE OrganismInfoID=$OrganismInfoID";
            }
            TBL_DBTables::AddWhereClause($SelectString, "TBL_Visits.ID IN ($TempString)");
        }

        if (($AreaID != Constants::NOT_SPECIFIED) && ($AreaID > 0)) {
            TBL_DBTables::AddWhereClause($SelectString, "TBL_Visits.AreaID=$AreaID");
        }

        if (($InsertLogID != Constants::NOT_SPECIFIED) && ($InsertLogID > 0)) {
            TBL_DBTables::AddWhereClause($SelectString, "TBL_Visits.InsertLogID=$InsertLogID");
        }

        if (($ProjectID != Constants::NOT_SPECIFIED) && ($ProjectID > 0)) {
            TBL_DBTables::AddWhereClause($SelectString, "TBL_Visits.ProjectID=$ProjectID");
        }

        if (($RefX != Constants::NOT_SPECIFIED) && ($RefX != 0)) {
            $RefRight = $RefX + $RefWidth;
            $RefBottom = $RefY + $RefHeight;

            /* 			$TempString="SELECT DISTINCT(AreaID) ".
              "FROM TBL_SpatialLayerData ".
              "WHERE ($RefX<(RefX+RefWidth)) AND ($RefRight>RefX) ".
              "AND ($RefY>(RefY+RefHeight)) AND ($RefBottom<RefY)";
             */
            $GeometryString = BlueSpray::GetWKTBoudingPolygon($RefX, $RefY, $RefRight - $RefX, $RefBottom - $RefY);

            $TempString2 = "SELECT DISTINCT(AreaID) " .
                    "FROM TBL_SpatialLayerData " .
                    "WHERE (GeometryData is not NULL) " .
                    "AND (GeometryData.STIsValid()=1) " .
                    "AND (GeometryData.STWithin(geometry::STGeomFromText('" . $GeometryString . "', 0))=1) ";

//			DebugWriteln("$TempString2");

            TBL_DBTables::AddWhereClause($SelectString, "TBL_Visits.AreaID IN ($TempString2)");

//			DebugWriteln("SelectString=$SelectString");
        }
    }

    //**********************************************************************************
    // TBL_DBTables functions
    //**********************************************************************************
    public static function GetFieldValue($dbConn, $FieldName, $ID, $Default = 0) {
        $Result = TBLDBTables::GetFieldValue($dbConn, "TBL_Visits", $FieldName, $ID, $Default);

        return($Result);
    }

    public static function SetFieldValue($dbConn, $FieldName, $ID, $Value) {
        TBLDBTables::SetFieldValue($dbConn, "TBL_Visits", $FieldName, $ID, $Value);
    }

    //******************************************************************************
    // Basic database functions
    //******************************************************************************

    public static function GetSet($dbConn, $ProjectID = null, $OrderBy = null, $Desending = null, $AreaID = null, $VisitDate = null) {
        $SelectString = "SELECT * " .
                "FROM \"TBL_Visits\" ";

        if ($ProjectID !== null)
            TBLDBTables::AddWhereClause($SelectString, "\"ProjectID\"=$ProjectID");
        if ($AreaID !== null)
            TBLDBTables::AddWhereClause($SelectString, "\"AreaID\"=$AreaID");
        if ($VisitDate !== null)
            TBLDBTables::AddWhereClause($SelectString, "\"VisitDate\"=CAST('$VisitDate' AS date)");

        if ($OrderBy !== null) {
            $SelectString.=" ORDER BY ";
            $SelectString.="\"$OrderBy\" ";
            if ($Desending)
                $SelectString.="DESC ";
        }

        $stmt = $dbConn->prepare($SelectString);
        $stmt->execute();

        $visit = $stmt->fetch();

        if (!$visit) {
            return false;
        }

        return $visit;
    }

    public static function GetSetFromID($dbConn, $VisitID) {
        $VisitID = SQL::SafeInt($VisitID);

        $SelectString = "SELECT * ".
                "FROM \"TBL_Visits\" ".
                "WHERE \"ID\"=" . $VisitID;

        $stmt = $dbConn->prepare($SelectString);
        $stmt->execute();

        return $stmt->fetch();
    }

    public static function GetTotalRows($Database, $ProjectID = Constants::NOT_SPECIFIED, $AreaID = Constants::NOT_SPECIFIED, $OrganismInfoID = Constants::NOT_SPECIFIED, $InsertLogID = Constants::NOT_SPECIFIED, $RefX = Constants::NOT_SPECIFIED, $RefY = Constants::NOT_SPECIFIED, $RefWidth = Constants::NOT_SPECIFIED, $RefHeight = Constants::NOT_SPECIFIED, $NumPresent = Constants::NOT_SPECIFIED, $NumAbsent = Constants::NOT_SPECIFIED) {
    //
    // Returns thenumber of rows in the desired query
    //
	// Parameters for all classes
    // 	Visits do not have a search string
    //
	// Class specific fields:
    //	$OrganismInfoID - just return projects containing visits with this taxon//

        // get the query for $CurrentRow+$NumRows rows in reversed order

        $SelectString = "SELECT COUNT(*) " .
                "FROM TBL_Visits ";

        TBL_Visits::AddSearchWhereClause($Database, $SelectString, $ProjectID, $AreaID, $OrganismInfoID, $InsertLogID, $RefX, $RefY, $RefWidth, $RefHeight, $NumPresent, $NumAbsent);

//		DebugWriteln("SelectString=$SelectString");

        $Set = $Database->Execute($SelectString);

        return($Set->Field(1));
    }

    public static function GetRows($Database, &$CurrentRow, $NumRows, $TotalRows, $OrderByField, $DescendingFlag, $Fields = null, $ProjectID = Constants::NOT_SPECIFIED, $AreaID = Constants::NOT_SPECIFIED, $OrganismInfoID = Constants::NOT_SPECIFIED, $InsertLogID = Constants::NOT_SPECIFIED, $RefX = Constants::NOT_SPECIFIED, $RefY = Constants::NOT_SPECIFIED, $RefWidth = Constants::NOT_SPECIFIED, $RefHeight = Constants::NOT_SPECIFIED, $NumPresent = Constants::NOT_SPECIFIED, $NumAbsent = Constants::NOT_SPECIFIED) {
    //
    // Returns a record set that matches the desired query
    //
	// Parameters for all classes
    // 	CurrentRow - the index to the first row to return (0 for the top row, 20 for the 20th row in the recordset, etc.
    //	NumRows - number of rows to return in the record set (number of rows in the table displaying the result)
    //	TotalRows - total number of rows in the query (value returned from GetTotalRows())
    //	OrderByField - Name of the field to order by if any
    // 	DescendingFlag - true to order descending, false for ascending
    //	Fields - an array of fields to return
    //
	// Class specific search fields:
    //	$OrganismInfoID - just return projects containing visits with this taxon
    //	$ProjectID
    //	$AreaID
    //	$InsertLogID//

        $NumRows = (int) $NumRows;
        $TotalRows = (int) $TotalRows;
        $CurrentRow = (int) $CurrentRow;

        if ($CurrentRow >= $TotalRows) {
            $LastPage = (int) ((($TotalRows + $NumRows - 1) / $NumRows) - 1); // from PageSettings

            $CurrentRow = $LastPage * $NumRows; // go to the last page
        }

        if ($CurrentRow < 0)
            $CurrentRow = 0;
//    	DebugWriteln("CurrentRow3=$CurrentRow");
        // get the query for $CurrentRow+$NumRows rows in reversed order

        $SelectString1 = "SELECT TOP " . ($TotalRows - $CurrentRow) . " ID " .
                "FROM TBL_Visits ";

        TBL_Visits::AddSearchWhereClause($Database, $SelectString1, $ProjectID, $AreaID, $OrganismInfoID, $InsertLogID, $RefX, $RefY, $RefWidth, $RefHeight, $NumPresent, $NumAbsent);

        TBL_DBTables::AddOrderByClause($SelectString1, $OrderByField, !$DescendingFlag); // query the rows in the opposite order of what the user wants
        // create the query that gets the top $NumRows and reverses the order to make it the way the user wants

        $SelectString = TBL_DBTables::GetSelectClause(0, $NumRows, $Fields);

        $SelectString.="FROM TBL_Visits " .
                "WHERE ID IN ($SelectString1) ";

        TBL_DBTables::AddOrderByClause($SelectString, $OrderByField, $DescendingFlag); // query the rows in the opposite order of what the user wants
//		DebugWriteln("SelectString=$SelectString");

        $Set = $Database->Execute($SelectString);

        return($Set);
    }

    public static function Insert($dbConn, $ProjectID, $AreaID = 0, $VisitDate = null, $InsertLogID = 0, $VolunteerCollected = 0, $VisitComments = null, $RecorderID = 0) {
        $ProjectID = SQL::SafeInt($ProjectID);
        $AreaID = SQL::SafeInt($AreaID);
        $InsertLogID = SQL::SafeInt($InsertLogID);
        $VolunteerCollected = SQL::SafeInt($VolunteerCollected);

        if ($VisitDate == null) {
            $Date = new Date();

            $VisitDate = $Date->GetSQLString();
        } else {
            $VisitDate = SQL::SafeDate($VisitDate);
        }

        // insert the visit

        $ExecString = "INSERT INTO \"TBL_Visits\" (\"ProjectID\") VALUES ($ProjectID)";

        $stmt = $dbConn->prepare($ExecString);
        $stmt->execute();

        $VisitID = $dbConn->lastInsertId('TBL_Visits_ID_seq');
        $stmt = null;

        // update the rest of the fields

        $String = "UPDATE \"TBL_Visits\" " .
                "SET \"AreaID\"=$AreaID, " .
                "\"Status\"='" . VISITSTATUS_NEW . "' "; // gjn - always make a new visit have a status of new, this can be edited by reviewers later

        if ($VisitDate != null)
            $String = $String . ",\"VisitDate\"=CAST('$VisitDate' AS date) ";

        if ($InsertLogID != 0)
            $String = $String . ",\"InsertLogID\"='$InsertLogID' ";

        if ($RecorderID != 0)
            $String = $String . ",\"RecorderID\"='$RecorderID' ";

        $String = $String . ",\"VolunteerCollected\"=$VolunteerCollected ";

        $String = $String . ",\"Comments\"='$VisitComments' ";

        $String = $String . "WHERE \"ID\"=$VisitID";

        $stmt = $dbConn->prepare($String);
        $stmt->execute();

        return($VisitID);
    }

    public static function Delete($dbConn, $VisitID) {
        // save the AreaID before we delete the visit

        $VisitSet = TBLVisits::GetSetFromID($dbConn, $VisitID);

        if (!$VisitSet)
            return;

        $AreaID = $VisitSet["AreaID"];

        // delete the visit
        TBLDBTables::Delete($dbConn, "TBL_Visits", $VisitID);

        if ($AreaID != 0) {
            // delete the area that was associated with the visit if it only has one visit

            $AreaSet = TBLAreas::GetSetFromID($dbConn, $AreaID);

            if ($AreaSet["UniqueToVisit"] == 1) { // delete the area
                $VisitSet = TBLVisits::GetSet($dbConn, null, null, null, $AreaID);

                if (!$VisitSet) { // no more visits attached to this area
                    TBLAreas::Delete($dbConn, $AreaID);
                }
            }
        }
    }

    public static function InsertVisitOnly($dbConn, $UserID, $VisitDate, $X, $Y, $ProjectID, $AreaName, $SubplotID, $CoordinateSystemID, $Accuracy, $FormID = Constants::NOT_SPECIFIED, $InsertLogType = INSERT_LOG_FORM, $VisitComments = null, $InsertLogID = Constants::NOT_SPECIFIED, $SelectedAreaID = Constants::NOT_SPECIFIED) {
        // add an area and a visit because we do not have any organism form entries

        TBLInsertLogs::SetFieldValue($dbConn, "UploaderID", $InsertLogID, $UserID); // specified as current user for web pages but can be specified otherwise with PDA and Map Web Service
        // see if the point already exists

        if ($SelectedAreaID != Constants::NOT_SPECIFIED)  // if user selected predefined location, use it as AreaID (R.S. 8/4/12)
        {
            $AreaID=$SelectedAreaID;
        }
        else
        {
            $AreaID = TBLAreas::GetIDFromCoordinate($dbConn, $X, $Y, COORDINATE_SYSTEM_WGS84_GEOGRAPHIC, 1, $ProjectID); // $CoordinateSystemID was hard coded to was STPROJECTION_GEOGRAPHIC
        }

        if ($AreaID <= 0) { // add a new point area
            $AreaID = TBLAreas::InsertPoint($dbConn, $ProjectID, $InsertLogID, $AreaName, $SubplotID, $X, $Y, $CoordinateSystemID, $Accuracy, AREA_SUBTYPE_POINT); // we need to modify AddPoint to be able to add survey types for plot types other than point
        }

        if ($VisitDate == null) { // not specified)
            $VisitDate = new Date; // set to today
        }

        $VisitDateString = $VisitDate->GetSQLString();

        $Set = TBLVisits::GetSet($$dbConn, null, "VisitDate", null, $AreaID, $VisitDateString); // $VisitDate->GetSQLString()

        $VisitID = 0;

        if ($Set) {
            $VisitID = $Set["ID"]; // get the ID for the existing visit
        }

        if ($VisitID == 0) { // insert a new visit
            $VisitID = TBLVisits::Insert($dbConn, $ProjectID, $AreaID, $VisitDateString, $InsertLogID, 1, $VisitComments, $UserID);
        }

        return $VisitID;
    }

    //*******************************************************************
    // Additional functions
    //*******************************************************************
    public static function GetVisitQueryForBounds($SelectString, $RefX, $RefY, $RefWidth, $RefHeight, $CoordinateSystemID = 1) {
        $RefRight = $RefX + $RefWidth;
        $RefBottom = $RefY + $RefHeight;

        $SelectString.="FROM TBL_Visits " .
                "INNER JOIN TBL_Areas ON TBL_Areas.ID=TBL_Visits.AreaID " .
                "INNER JOIN TBL_SpatialLayerData ON TBL_SpatialLayerData.AreaID=TBL_Areas.ID " .
                "WHERE 	CoordinateSystemID=$CoordinateSystemID " .
                "AND RefX<$RefRight " .
                "AND (RefX+RefWidth)>$RefX " .
                "AND RefY>$RefBottom " .
                "AND (RefY+RefHeight)<$RefY ";

        return($SelectString);
    }

    public static function GetVisitSetForInsertLogID($Database, $InsertLogID) {
        $SelectString = "SELECT * " .
                "FROM TBL_Visits " .
                "WHERE InsertLogID=" . $InsertLogID . " " .
                "ORDER BY VisitDate";

        $VisitSet = $Database->Execute($SelectString);

        return($VisitSet);
    }

    public static function WriteHeadlineRowFromVisitID($Database, $TheTable, $VisitID, $TabID = null) {
        $UserID = GetUserID();

        // write out the id

        $SelectString = "SELECT TBL_Visits.ID AS VisitID,VisitDate,TBL_Visits.Comments AS VisitComments," .
                "ProjName,TBL_Projects.ID AS ProjectID, " .
                "AreaName,TBL_Areas.ID AS AreaID " .
                "FROM TBL_Visits " .
                "INNER JOIN TBL_Projects ON TBL_Projects.ID=TBL_Visits.ProjectID " .
                "INNER JOIN TBL_Areas ON TBL_Areas.ID=TBL_Visits.AreaID " .
                "WHERE TBL_Visits.ID=$VisitID";

        $Set = $Database->Execute($SelectString);

        $ProjectID = $Set->Field("ProjectID");

        // make a calender
        //DebugWriteln("ProjectID=$ProjectID");
        // get the photo

        $MediaSet = TBL_Media::GetSetFromVisitID($Database, $VisitID);

        $PhotoLink = "&nbsp";
        if ($MediaSet->FetchRow()) {
            $FilePath = TBL_Media::GetFilePathFromSet($MediaSet, "_thumbnails", true, false);

            $PhotoLink = GetLink("/cwis438/Browse/Media_Info.php", "<img src='$FilePath' border='0' width='160'>", "MediaID=" . $MediaSet->Field("ID"));
            $PhotoLink = "<div class='ImageThumbnail'>$PhotoLink</div>";
        }

        // check editing permissions for each visit

        $CanEditData = TBL_Projects::CanEditData($Database, $UserID, $ProjectID, $VisitID);

        //DebugWriteln("CanEditData=$CanEditData");

        $Title = "&nbsp;";

        $AreaName = $Set->Field("AreaName");
        if (StringIsEmpty($AreaName))
            $AreaName = "Untitled";

        $Title.="$AreaName";

        // add the date

        $Date = new Date;
        $Date->SetDateFromSQLString($Set->Field("VisitDate"));
//		$Date=GetPrintDateFromSQLServerDate($Set->Field("VisitDate"));
//		$Title="$Date at ".$Title;
        // create the link
        $MyCallingPage = "/cwis438/Browse/Project/Project_Info.php" .
                "?ProjectID=$ProjectID&CurrentTab=1" .
                "&TakeAction=Returned";
        $MyCallingPage = urlencode($MyCallingPage);

        $Title = GetLink("/cwis438/Browse/Project/Visit_Info.php", $Title, "VisitID=$VisitID&ProjectID=$ProjectID&MyCallingPage=$MyCallingPage");

        // add any options

        $Options = "";

        if ($CanEditData) {
            if ($TabID != null) {
                $Options = "<input type='button' value='Delete' onClick='DoDelete($VisitID,$TabID)'/>";
            } else {
                $Options = "<input type='button' value='Delete' onClick='DoDelete($VisitID)'/>";
            }
        }

        // add the comment
        $Details = "";
        //$Details=$Set->Field("VisitComments");
        // write out the row

        TBL_Visits::HeadlineRow($TheTable, $Date, $PhotoLink, $Title, $Details, $Options);
    }

    public static function HeadlineRow($TheTable, $Date, $PhotoLink, $Title, $Details = "&nbsp;", $Options = "") {

        // calcender

        $Calender = $Date->GetCalender();

        //

        $Content = "<div style='width:100%; text-align:left;'>";
        //$Content=$Title;
        //$Content.="<div style='float:right;'>$Options'</div>";

        $Content = "<div style='float:right;padding:4px;'>$Options</div>";
        $Content.=$Title;

        $Content.="<br>";

        $DetailsTemp = WordLimiter($Details, 50);

        $Content.=$DetailsTemp . "<br>";
        $Content.="</div>";

        //

        $TheTable->TableRowStart();

        $Old = $TheTable->Columns[0]->Width;
        $TheTable->Columns[0]->Width = "10%";
        $TheTable->Columns[0]->VAlign = "Top";

        $TheTable->TableCell(0, $Calender);

        $TheTable->Columns[0]->Width = $Old;

        $TheTable->Columns[0]->Width = "80%";
        $TheTable->Columns[0]->Align = "Left";
        $TheTable->TableCell(0, $Content);

        $TheTable->Columns[0]->Width = "10%";
        $TheTable->Columns[0]->Align = "Center";

        $TheTable->TableCell(0, $PhotoLink);

        $TheTable->TableRowEnd();
    }

    public function GetVisitCountForPerson($Database, $PersonID, $ProjectID) {

        $SelectString = "SELECT COUNT(1) as Count " .
                "FROM TBL_Visits " .
                "WHERE RecorderID=" . $PersonID . " AND ProjectID=" . $ProjectID;

        $VisitSet = $Database->Execute($SelectString);

        return($VisitSet);
    }
}

?>
