<?php

namespace Classes\DBTable;

//**************************************************************************************
// FileName: Area.php
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
//require_once("C:/Inetpub/wwwroot/cwis438/utilities/ResultUtil.php");
//**************************************************************************************
// Definitions
//**************************************************************************************
use Classes\DBTable\TBLSpatialLayerData;
use Classes\Utilities\SQL;
use Classes\TBLDBTables;
use API\Classes\Constants;

define("AREA_SUBTYPE_COUNTIES", 4);

$SensitiveStrings = array("Not Sensitive", "7.5 minute (>12km)");

//$SensitiveStrings=array("Not Sensitive","7.5 minute (>12km)","15 minute (>25km)",
//	"30 minute (>50km)","1 degree (>100km)");

$SenstiveFactors = array(0, 8.0, 4.0, 2.0, 1.0);


//**************************************************************************************
// Class Definition
//**************************************************************************************

class TBLAreas {

    //******************************************************************************
    // Private functions
    //******************************************************************************

    public static function AddSearchWhereClause($Database, &$SelectString, $SearchString = null, $FirstLetter = null, $ProjectID = null, $AreaSubtypeID = null, $OrganismInfoID = null, $NationID = null, $StateID = null, $RefX = null, $RefY = null, $RefWidth = null, $RefHeight = null) {
        //
        // Adds the appropriate search criteria to the provided search string.
        // This public static function will only return select strings for non-survey areas.//

        if ($FirstLetter != null)
            TBL_DBTables::AddWhereClause($SelectString, "AreaName LIKE '$FirstLetter%'");

        if ($SearchString != null) {
            TBL_DBTables::AddWhereClause($SelectString, "AreaName LIKE '%$SearchString%'");
        }

        if ($ProjectID > 0) {
            $SelectString1 = "SELECT DISTINCT AreaID " .
                    "FROM REL_ProjectToArea " .
                    "WHERE ProjectID=$ProjectID";

            TBL_DBTables::AddWhereClause($SelectString, "ID IN ($SelectString1)");
        }

        if ($AreaSubtypeID > 0)
            TBL_DBTables::AddWhereClause($SelectString, "AreaSubtypeID=$AreaSubtypeID");

        if ($OrganismInfoID > 0) {
            // create a query to find the survey areas that have the organism

            $SelectSurveysWithOrganismDataForOrganismInfo = "SELECT TBL_Areas.ID AS ID " . // select area ids that have associated organism data with the OrganismInfoID
                    "FROM TBL_Areas " .
                    "INNER JOIN LKU_AreaSubtypes ON LKU_AreaSubtypes.ID=TBL_Areas.AreaSubtypeID " .
                    "INNER JOIN TBL_Visits ON TBL_Visits.AreaID=TBL_Areas.ID " .
                    "INNER JOIN TBL_OrganismData ON TBL_OrganismData.VisitID=TBL_Visits.ID " .
                    "WHERE  LKU_AreaSubtypes.Survey=1 " .
                    "AND TBL_OrganismData.OrganismInfoID=$OrganismInfoID";

            // create a query that looks for non-survey areas that contain survey areas with the organism

            $SelectLevel1 = // areas that directly contain the species
                    "SELECT DISTINCT Area1ID AS ID " .
                    "FROM REL_AreaToArea " .
                    "WHERE Area2ID IN ($SelectSurveysWithOrganismDataForOrganismInfo) ";

            $SelectLevel2 = // areas that contain an area that contains an area with the species (i.e. a county with a national park with a plot)
                    "SELECT DISTINCT Area1ID AS ID " .
                    "FROM REL_AreaToArea " .
                    "WHERE Area2ID IN ($SelectLevel1) ";

            $SelectLevel3 = // areas that are parents that contain an area that contains an area with the species
                    // (i.e. a state that contains a county with a national park with a plot)
                    "SELECT DISTINCT ParentID " .
                    "FROM TBL_Areas " .
                    "WHERE ID IN ($SelectLevel2) ";

            $SelectLevel4 = // areas that are parents of areas that are parents of areas that contain an area that contains an area with the species
                    // (i.e. a nation that contains a state that contains a county with a national park with a plot)
                    "SELECT DISTINCT ParentID " .
                    "FROM TBL_Areas " .
                    "WHERE ID IN ($SelectLevel3) ";

            TBL_DBTables::AddWhereClause($SelectString, "(ID IN ($SelectLevel1) OR ID IN ($SelectLevel2) OR ID IN ($SelectLevel3) OR ID IN ($SelectLevel4))");
        }

        if ($StateID > 0) { // get the set of areaids that have a nation as their parent at some level?
            $SelectCounties = "SELECT TBL_Areas.ID AS ID " .
                    "FROM TBL_Areas " .
                    "WHERE ParentID=$StateID ";

            $SelectOtherNonSurveyAreas = "SELECT TBL_Areas.ID AS ID " .
                    "FROM TBL_Areas " .
                    "INNER JOIN LKU_AreaSubtypes ON LKU_AreaSubtypes.ID=TBL_Areas.AreaSubtypeID " .
                    "INNER JOIN REL_AreaToArea ON REL_AreaToArea.Area2ID=TBL_Areas.ID " .
                    "WHERE LKU_AreaSubtypes.Survey=0 " . // non-survey areas
                    "AND Area1ID IN ($SelectCounties) ";

            $WhereClause = "(ID IN ($SelectCounties) OR ID IN ($SelectOtherNonSurveyAreas))";

            TBL_DBTables::AddWhereClause($SelectString, $WhereClause);
        } else if ($NationID > 0) { // get the set of areaids that have a nation as their parent at some level?
            $SelectStates = "SELECT TBL_Areas.ID AS ID " .
                    "FROM TBL_Areas " .
                    "WHERE ParentID=$NationID ";

            $SelectCounties = "SELECT TBL_Areas.ID AS ID " .
                    "FROM TBL_Areas " .
                    "WHERE ParentID IN ($SelectStates) ";

            $SelectOtherNonSurveyAreas = "SELECT TBL_Areas.ID AS ID " .
                    "FROM TBL_Areas " .
                    "INNER JOIN LKU_AreaSubtypes ON LKU_AreaSubtypes.ID=TBL_Areas.AreaSubtypeID " .
                    "INNER JOIN REL_AreaToArea ON REL_AreaToArea.Area2ID=TBL_Areas.ID " .
                    "WHERE LKU_AreaSubtypes.Survey=0 " . // non-survey areas
                    "AND Area1ID IN ($SelectCounties) ";

            $WhereClause = "(ID IN ($SelectStates) OR ID IN ($SelectCounties) OR ID IN ($SelectOtherNonSurveyAreas))";

            TBL_DBTables::AddWhereClause($SelectString, $WhereClause);
        } else if ($RefX != 0) { // get the set of areaids that have a nation as their parent at some level?
            $GeometryString = BlueSpray::GetWKTBoudingPolygon($RefX, $RefY, $RefWidth, $RefHeight);

            $SelectThing = "SELECT DISTINCT AreaID " .
                    "FROM TBL_SpatialLayerData " .
                    "WHERE " .
                    "(GeometryData is not NULL) " .
                    "AND (GeometryData.STIsValid()=1) " .
                    "AND (GeometryData.STWithin(geometry::STGeomFromText('" . $GeometryString . "', 0))=1) "; // jjg - SQLServerSpatialCha//  //				"WHERE (GeometryData.STWithin(geometry::STGeomFromText('".$GeometryString."', 0))=1) ". // jjg - SQLServerSpatialChange

            $WhereClause = "(TBL_Areas.ID IN ($SelectThing))";

            TBL_DBTables::AddWhereClause($SelectString, $WhereClause);

//			DebugWriteln("SelectString=$SelectString");
        }
    }

    //**********************************************************************************
    // TBL_DBTables functions
    //**********************************************************************************
    public static function GetFieldValue($Database, $FieldName, $ID, $Default = 0) {
        $Result = TBL_DBTables::GetFieldValue($Database, "TBL_Areas", $FieldName, $ID, $Default);

        return($Result);
    }

    public static function SetFieldValue($Database, $FieldName, $ID, $Value) {
        TBL_DBTables::SetFieldValue($Database, "TBL_Areas", $FieldName, $ID, $Value);
    }

    //**********************************************************************************
    // Basic database functions
    //**********************************************************************************

    public static function GetSet($Database, $ParentID = null, $AreaSubtypeID = null) {
        $SelectString = "SELECT * " .
                "FROM TBL_Areas ";

        if ($ParentID != null)
            TBL_DBTables::AddWhereClause($SelectString, "ParentID=$ParentID");
        if ($AreaSubtypeID != null)
            TBL_DBTables::AddWhereClause($SelectString, "AreaSubtypeID=$AreaSubtypeID");

        $SelectString.=" ORDER BY AreaName";

        $Set = $Database->Execute($SelectString);

        return($Set);
    }

    public static function GetSetFromID($dbConn, $ID) {
        $SelectString = "SELECT * " .
                "FROM \"TBL_Areas\" " .
                "WHERE \"ID\"='$ID'";

        $stmt = $dbConn->prepare($SelectString);
        $stmt->execute();

        $set = $stmt->fetch();

        if (!$set) {
            return false;
        }

        return $set;
    }

    public static function GetTotalRows($Database, $SearchString = null, $FirstNameLetter = null, $ProjectID = null, $AreaSubtypeID = null, $OrganismInfoID = null, $NationID = null, $StateID = null, $RefX = null, $RefY = null, $RefWidth = null, $RefHeight = null) {
        //
        // Returns thenumber of rows in the desired query
        //
	// Parameters for all classes:
        //	SearchString - a string to search in text associted with the project
        //
        // Class specific fields:
        //	SearchIn - definition for which fields to search in (see definitions at top of the file)
        //	OrganizationID - just return this organizations projects
        //	$OrganismInfoID - just return projects containing visits with this taxon//
        // get the query for $CurrentRow+$NumRows rows in reversed order

        $SelectString = "SELECT COUNT(*) " .
                "FROM TBL_Areas " .
                "WHERE AreaSubtypeID IN " .
                "(SELECT ID " .
                "FROM LKU_AreaSubtypes " .
                "WHERE Survey=0 " .
                "AND (AreaTypeID IN (SELECT ID FROM LKU_AreaTypes WHERE AreaBrowse=1)))";

        TBL_Areas::AddSearchWhereClause($Database, $SelectString, $SearchString, $FirstNameLetter, $ProjectID, $AreaSubtypeID, $OrganismInfoID, $NationID, $StateID, $RefX, $RefY, $RefWidth, $RefHeight);

        //DebugWriteln("SelectString=$SelectString");

        $Set = $Database->Execute($SelectString);

        return($Set->Field(1));
    }

    public static function GetRows($Database, &$CurrentRow, $NumRows, $TotalRows, $OrderByField, $DescendingFlag, $Fields = null, $SearchString = null, $FirstNameLetter = null, $ProjectID = null, $AreaSubtypeID = null, $OrganismInfoID = null, $NationID = null, $StateID = null, $RefX = null, $RefY = null, $RefWidth = null, $RefHeight = null) {
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
        //	SearchString - a string to search in text associted with the project
        //
	// Class specific search fields:
        //	SearchIn - definition for which fields to search in (see definitions at top of the file)
        //	MatchTo -
        //	OrganizationID - just return this organizations projects
        //	$OrganismInfoID - just return projects containing visits with this taxon//

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
                "FROM TBL_Areas " .
                "WHERE AreaSubtypeID IN " .
                "(SELECT ID " .
                "FROM LKU_AreaSubtypes " .
                "WHERE Survey=0 " .
                "AND (AreaTypeID IN (SELECT ID FROM LKU_AreaTypes WHERE AreaBrowse=1)))";

        TBL_Areas::AddSearchWhereClause($Database, $SelectString1, $SearchString, $FirstNameLetter, $ProjectID, $AreaSubtypeID, $OrganismInfoID, $NationID, $StateID, $RefX, $RefY, $RefWidth, $RefHeight);

        TBL_DBTables::AddOrderByClause($SelectString1, $OrderByField, !$DescendingFlag); // query the rows in the opposite order of what the user wants
        // create the query that gets the top $NumRows and reverses the order to make it the way the user wants

        $SelectString = TBL_DBTables::GetSelectClause(0, $NumRows, $Fields);

        $SelectString.="FROM TBL_Areas " .
                "WHERE ID IN ($SelectString1) ";
//			"ORDER BY $OrderByField ";

        TBL_DBTables::AddOrderByClause($SelectString, $OrderByField, $DescendingFlag); // query the rows in the opposite order of what the user wants
//		if ($DescendingFlag)  $SelectString.="DESC "; // can't use order by function, finds previous order by
//		DebugWriteln("SelectString=$SelectString");

        $Set = $Database->Execute($SelectString);

        return($Set);
    }

    public static function Insert($dbConn, $AreaSubtypeID, $AreaName = "", $AreaCode = "", $UniqueToVisit = 0, $ProjectID = 0, $InsertLogID = 0, $ParentID = 0, $Accuracy = null, $Comments = null) {
        $data = array(
            'AreaSubtypeID' => SQL::SafeInt($AreaSubtypeID),
            'AreaName' => "'".SQL::SafeString($AreaName)."'",
            'Code' => "'".SQL::SafeString($AreaCode)."'"
        );

        if (!empty($UniqueToVisit))
            $data['UniqueToVisit'] = 'true';
        if ($ProjectID != 0)
            $data['ProjectID'] = $ProjectID;
        if ($InsertLogID != 0)
            $data['InsertLogID'] = $InsertLogID;
        if ($ParentID != 0)
            $data['ParentID'] = $ParentID;
        if ($Accuracy !== null)
            $data['Uncertainty'] = $Accuracy;
        if ($Comments !== null)
            $data['Comments'] = "'".SQL::SafeString($Comments)."'";

        $columns = '';
        $columnsData = '';

        foreach ($data as $column => $columnData)
        {
            $column = trim($column);

            if (empty($column))
                continue;

            $columns .= '"'.$column.'",';
            $columnsData .= $columnData.',';
        }

        $columns = rtrim($columns, ',');
        $columnsData = rtrim($columnsData, ',');

        $stmt = $dbConn->prepare("INSERT INTO \"TBL_Areas\" ($columns) VALUES ($columnsData)");
        $stmt->execute();
        $ID = $dbConn->lastInsertId('"TBL_Areas_ID_seq"');
        trigger_error(print_r($ID, 1));
        return($ID);
    }

    //**********************************************************************************
    public static function Update($Database, $AreaID, $Name, $Code, $Sensitive, $Hectares) {
        $UpdateString = "UPDATE TBL_Areas " .
                "SET AreaName='" . $Name . "', " .
                "Code='$Code', " .
                "Sensitive='$Sensitive', " .
                "Hectares='$Hectares' " .
                "WHERE ID=" . $AreaID;

//		 DebugWriteln("UpdateString=$UpdateString");

        $Database->Execute($UpdateString);

        // update the googlemaps data

        TBL_SpatialGridded::DeleteFromAreaID($Database, $AreaID);
        TBL_SpatialGridded::InsertForAreaID($Database, $AreaID);
    }

    //**********************************************************************************
    public static function Delete($dbConn, $AreaID) {
        //
        //	Deletes an area and its associated records:
        //		TBL_SpatialLayerData
        //		REL_AreaToArea.Area1ID or Area2ID
        //		REL_OrganismToArea
        //		Does not delete TBL_Visits.AreaID as these should be deleted after the area//

        // delete the assocaited spatial data

        TBLSpatialLayerData::DeleteFromAreaID($dbConn, $AreaID); // is this needed? should be called through relationships

        // delete the area record

        TBLDBTables::Delete($dbConn, "TBL_Areas", $AreaID);
    }

    //******************************************************************************
    // Additional functions
    //******************************************************************************
    public static function GetNameForID($Database, $ID, $Default = "Untitled") {
        $Name = $Default;

        $Set = TBL_Areas::GetSetFromID($Database, $ID);

        if ($Set->FetchRow())
            $Name = $Set->Field("AreaName");

        return($Name);
    }

    public static function GetIDFromCoordinate($dbConn, $X, $Y, $CoordinateSystemID = 1, $GeometryType = 1, $ProjectID = null) {
        //
        // Queries for an existing area for a given project.
        //
	// For typicaly GODM situations the only coordinate system ID supported is
        // Geographic, WGS84.  Works only for point areas.
        //
	// $Database
        // $X - Typically Longitude of the area
        // $Y - Typically Latitude of the area
        // $CoordinateSystemID - Typically Geographic, WGS 84
        // $GeometryType - points only (jjg - then why do we let the user pass this in?)
        // $ProjectID - ID of the project the area is in//

        $AreaID = 0;

        // make sure the coorindate is in geographic

        if ($CoordinateSystemID != Constants::COORDINATE_SYSTEM_WGS84_GEOGRAPHIC) {
            $CoordinateSystemID = Constants::COORDINATE_SYSTEM_WGS84_GEOGRAPHIC;
        }

        $SelectString = "SELECT \"TBL_Areas\".\"ID\" " .
            "FROM \"TBL_Areas\" " .
            "INNER JOIN \"TBL_SpatialLayerData\" ON \"TBL_SpatialLayerData\".\"AreaID\"=\"TBL_Areas\".\"ID\" " .
            "INNER JOIN \"LKU_AreaSubtypes\" ON \"LKU_AreaSubtypes\".\"ID\"=\"TBL_Areas\".\"AreaSubtypeID\" " .
            "WHERE \"CoordinateSystemID\"=$CoordinateSystemID " .
            "AND \"GeometryType\"=$GeometryType " .
            "AND \"RefX\"=$X " .
            "AND \"RefY\"=$Y ";

        if ($ProjectID != null)
            $SelectString.="AND \"TBL_Areas\".\"ProjectID\"=$ProjectID ";

        $stmt = $dbConn->prepare($SelectString);
        $stmt->execute();
        $area = $stmt->fetch();

        if ($area)
            $AreaID = $area["ID"];

        return($AreaID);
    }

    public static function GetParentIDFromAreaID($Database, $AreaID) {
        $ParentID = 0;

        $SelectString = "SELECT ParentID " .
                "FROM TBL_Areas " .
                "WHERE ID=$AreaID";

        $AreaSet = $Database->Execute($SelectString);

        if ($AreaSet->FetchRow("ParentID"))
            $ParentID = $AreaSet->Field("ParentID");

        return($ParentID);
    }

    public static function GetSetFromAreaSubTypeID($Database, $AreaSubtypeID) {
        $SelectString = "SELECT ID,AreaName,Code " .
                "FROM TBL_Areas " .
                "WHERE (AreaSubtypeID=$AreaSubtypeID) " .
                "ORDER BY AreaName";

//		DebugWriteln("SelectString=$SelectString");

        $Set = $Database->Execute($SelectString);

        return($Set);
    }

    public static function GetSetWithParentFromAreaSubTypeID($Database, $AreaSubtypeID) {
        $SelectString = "SELECT TBL_Areas.ID,TBL_Areas.AreaName,TBL_Areas.Code,TBL_Areas_1.AreaName as ParentName " .
                "FROM TBL_Areas INNER JOIN " .
                "TBL_Areas TBL_Areas_1 ON TBL_Areas.ParentID = TBL_Areas_1.ID " .
                "WHERE (TBL_Areas.AreaSubtypeID = $AreaSubtypeID) " .
                "ORDER BY TBL_Areas.AreaName";

        //DebugWriteln("SelectString=$SelectString");

        $Set = $Database->Execute($SelectString);

        return($Set);
    }

    public static function GetSetFromParentID($Database, $ParentID, $AreaSubtypeID = null) {
        //
        // Added by Greg 7/24/06 to get set for a given parent of type $AreaSubTypeID//

        $SelectString = "SELECT ID,AreaName,Code " .
                "FROM TBL_Areas " .
                "WHERE (ParentID=$ParentID) ";

        If ($AreaSubtypeID != null)
            $SelectString.="AND (AreaSubtypeID=$AreaSubtypeID) ";

        $SelectString.="ORDER BY AreaName";

//		DebugWriteln("SelectString=$SelectString");

        $Set = $Database->Execute($SelectString);

        return($Set);
    }

    public static function GetAreaAndSpatialDataSetFromID($Database, $ID) {
        $SelectString = "SELECT *, " .
                "TBL_SpatialLayerData.RefX AS AreaRefX, " .
                "TBL_SpatialLayerData.RefY AS AreaRefY," .
                "TBL_SpatialLayerData.RefWidth AS AreaRefWidth, " .
                "TBL_SpatialLayerData.RefHeight AS AreaRefHeight, " .
                "TBL_Areas.AreaName AS AreaName " .
                "FROM TBL_SpatialLayerData INNER JOIN " .
                "TBL_Areas ON TBL_SpatialLayerData.AreaID = TBL_Areas.ID " .
                "WHERE (TBL_SpatialLayerData.AreaID = " . SQL::SafeInt($ID) . ") AND (TBL_SpatialLayerData.CoordinateSystemID = 1)";

        //DebugWriteln("SelectString="+SelectString);

        $AreaSet = $Database->Execute($SelectString);

        return($AreaSet);
    }

    public static function GetAreaName($dbConn, $ID) {
        $SelectString = "SELECT \"AreaName\" " .
            "FROM \"TBL_Areas\" " .
            "WHERE \"ID\"=:ID";

        $stmt = $dbConn->prepare($SelectString);
        $stmt->bindValue("ID", SQL::SafeInt($ID));
        $stmt->execute();

        return $stmt->fetchColumn(); // area name
    }

    public static function GetAreaIDFromCodes($Database, $AreaSubtypeID, $AreaCode = 0, $ParentTypeID = 0, $ParentCode = null, $ParentID = 0, $ProjectID = 0) {
        $ID = 0;
        if ($ParentID == null)
            $ParentID = 0;
//		DebugWriteln("Parent code is null=".is_null($ParentCode));
        $RecrodSet = TBL_Areas::GetAreaSetFromCodes($Database, $AreaSubtypeID, $AreaCode, $ParentTypeID, $ParentCode, $ParentID, $ProjectID);

        if ($RecrodSet->FetchRow()) {
            $ID = $RecrodSet->Field("ID");
//			DebugWriteln("************ * AreaID=".$ID);
        }

        return($ID);
    }

    public static function GetQueryFromCodes($AreaSubtypeID, $AreaCode = 0, $ParentTypeID = 0, $ParentCode = null, $ParentID = 0, $ProjectID = 0) {
        //
        // Return a query to return a list of areas based on codes and other settings//

        if ($ParentCode !== 0)
            $AreaCode = SQL::SafeString($AreaCode);
        if ($ParentCode !== null)
            $ParentCode = SQL::SafeString($ParentCode);

//		DebugWriteln("AreaCode=$AreaCode");
//		DebugWriteln("Parent code is null=".is_null($ParentCode));

        $SelectString = "SELECT ID,AreaName,AreaSubtypeID,Code " .
                "FROM TBL_Areas " .
                "WHERE AreaSubtypeID=$AreaSubtypeID ";

        if ($AreaCode !== 0)
            $SelectString = $SelectString . "AND Code='$AreaCode' ";

        if ($ProjectID !== 0)
            $SelectString = $SelectString . "AND ProjectID=$ProjectID ";

        if (($ParentTypeID !== 0) || ($ParentCode !== null) || ($ParentID !== 0)) {
            $SelectString = $SelectString . "AND ParentID IN (SELECT ID " .
                    "FROM TBL_AREAS " .
                    "WHERE ";

            $NumConditions = 0;

            if ($ParentTypeID !== 0) {
                $SelectString = $SelectString . "AreaSubtypeID=$ParentTypeID ";
                $NumConditions++;
            }
            if ($ParentCode !== null) {
                if ($NumConditions !== 0)
                    $SelectString = $SelectString . "AND ";

                $SelectString = $SelectString . "Code='$ParentCode' ";
                $NumConditions++;
            }
            if ($ParentID !== 0) {
                if ($NumConditions !== 0)
                    $SelectString = $SelectString . "AND ";

                $SelectString = $SelectString . "ID=$ParentID ";
                $NumConditions++;
            }
            $SelectString = $SelectString . ") ";
        }
        return($SelectString);
    }

    public static function GetAreaSetFromCodes($Database, $AreaSubtypeID, $AreaCode = 0, $ParentTypeID = 0, $ParentCode = null, $ParentID = 0, $ProjectID = 0) {
        //
        //	Creates a record set of areas based on an AreaType and a number of optional parameters//

        $SelectString = TBL_Areas::GetQueryFromCodes($AreaSubtypeID, $AreaCode, $ParentTypeID, $ParentCode, $ParentID, $ProjectID);

        $SelectString.="ORDER BY AreaName";

        $RecordSet = $Database->Execute($SelectString);

        return($RecordSet);
    }

    public static function GetRelatedAreaSet($Database, $StateID, $AreaSubtypeID) {
        $SelectString = "SELECT TBL_Areas.ID,AreaName " .
                "FROM TBL_Areas,REL_AreaToArea " .
                "WHERE AreaSubtypeID=$AreaSubtypeID " .
                "AND Area1ID=$StateID " .
                "AND Area2ID=TBL_Areas.ID " .
                "ORDER BY AreaName";

        //DebugWriteln("SelectString="+SelectString);

        $ParkSet = $Database->Execute($SelectString);

        return($ParkSet);
    }

    public static function GetSubPlotTypeSet($Database, $AreaSubtypeID) {
        // Function created by Greg Newman
        // Returns a list of subplots for specified AreaType GJN
        // Must receive an AreaSubtypeID
        if ($AreaSubtypeID == 0)
            $AreaSubtypeID = 0; // converts null to 0

        $SelectString = "SELECT *, LKU_SubPlotTypes.Name AS Name, " .
                "LKU_SubPlotTypes.ID AS SubPlotTypeID, " .
                "LKU_SubPlotTypes.Area AS SpatialArea " .
                "FROM LKU_SubPlotTypes INNER JOIN " .
                "LKU_AreaSubtypes ON LKU_SubPlotTypes.AreaSubtypeID = LKU_AreaSubtypes.ID " .
                "WHERE (LKU_SubPlotTypes.AreaSubtypeID = " . $AreaSubtypeID . ")";

        $SubPlotTypeSet = $Database->Execute($SelectString);

        return($SubPlotTypeSet);
    }

    public static function GetExtent($Database, $AreaID, &$RefX, &$RefY, &$RefWidth, &$RefHeight) {
        global $SenstiveFactors;

        // make sure we return valid values

        $RefX = 0;
        $RefY = 0;
        $RefWidth = 0;
        $RefHeight = 0;

        $ErrorString = null;

        $SelectString = "SELECT RefX,RefY,RefWidth,RefHeight,ProjectID,Sensitive " .
                "FROM TBL_SpatialLayerData,TBL_Areas " .
                "WHERE CoordinateSystemID=1 " .
                "AND TBL_Areas.ID=TBL_SpatialLayerData.AreaID " .
                "AND TBL_Areas.ID=" . $AreaID;

//		DebugWriteln("$SelectString");

        $Set = $Database->Execute($SelectString);

        if ($Set->FetchRow()) {
            $RefX = $Set->Field("RefX");
            $RefY = $Set->Field("RefY");
            $RefWidth = $Set->Field("RefWidth");
            $RefHeight = $Set->Field("RefHeight");

            $ProjectID = $Set->Field("ProjectID");
            $Sensitive = $Set->Field("Sensitive");

            if (($ProjectID > 0) && ($Sensitive > 0)) {
                // if the user is not on the project, must blur the coordinates

                if (REL_PersonToProject::HasRole($Database, $ProjectID, Constants::PROJECT_CONTRIBUTOR)) {
                    $Factor = $SenstiveFactors[$Sensitive];

                    $RefX = (int) ($RefX * $Factor) / $Factor;
                    $RefY = (int) ($RefY * $Factor) / $Factor;
                    $RefWidth = (int) ($RefWidth * $Factor) / $Factor;
                    $RefHeight = (int) ($RefHeight * $Factor) / $Factor;
                }
            }
        } else
            $ErrorString = "Sorry, cannot find the specified area";

        return($ErrorString);
    }

    public static function GetNearbySet($Database, $AreaSubtypeID, $ParentID, $RefX, $RefY, $Tolerance = 0, $AreaName = null) {
//		$Result=RESULT_OKAY;

        $SelectString = "SELECT TBL_Areas.ID " .
                "FROM TBL_Areas " .
                "INNER JOIN TBL_SpatialLayerData ON AreaID=TBL_Areas.ID " .
                "WHERE CoordinateSystemID=1 " .
                "AND TBL_Areas.ID=TBL_SpatialLayerData.AreaID " .
                "AND AreaSubtypeID=$AreaSubtypeID " .
                "AND ParentID=$ParentID " .
                "AND abs($RefX-RefX)<$Tolerance " .
                "AND abs($RefY-RefY)<$Tolerance ";

        if ($AreaName !== null)
            $SelectString.="AND AreaName='$AreaName'";

        $Set = $Database->Execute($SelectString);

        return($Set);
    }

    public static function GetIDFromNameType($Database, $AreaName, $AreaSubtypeID, $ParentID = 0) {
        $ID = 0;

        $SelectString = "SELECT ID " .
                "FROM TBL_Areas " .
                "WHERE AreaName='$AreaName' " .
                "AND AreaSubtypeID='$AreaSubtypeID' ";
        if ($ParentID !== 0)
            $SelectString.="AND ParentID='$ParentID'";

        $RecrodSet = $Database->Execute($SelectString);

        if ($RecrodSet->FetchRow()) {
            $ID = $RecrodSet->Field("ID");
        }

        return($ID);
    }

    public static function GetNumProjectAreas($Database, $ProjectID) {
        $SelectString = "SELECT COUNT(*) AS NumAreas
	        FROM TBL_Areas
	        WHERE (ProjectID = $ProjectID)";

        $NumAreasSet = $Database->Execute($SelectString);

        $NumAreas = $NumAreasSet->Field("NumAreas");

        return($NumAreas);
    }

    //******************************************************************************
    // Additional functions
    //******************************************************************************
    public static function GetAreaTypeIDFromAreaID($Database, $AreaID) {
        $SelectString = "SELECT AreaSubtypeID " .
                "FROM TBL_Areas " .
                "WHERE ID=$AreaID";

        $AreaSet = $Database->Execute($SelectString);

        return($AreaSet->Field("AreaSubtypeID"));
    }

    public static function SetSenstive($Database, $AreaID, $Sensitive) {
        $Sensitive = (int) $Sensitive;

        $SelectString = "UPDATE TBL_Areas " .
                "SET Sensitive=$Sensitive " .
                "WHERE ID=$AreaID";

        $Database->Execute($SelectString);
    }

    public static function GetAreaIDFromOrganismDataID($Database, $OrganismDataID) {
        $AreaID = null;

        $SelectString = "SELECT TBL_Areas.ID " .
                "FROM TBL_Areas " .
                "INNER JOIN TBL_Visits ON TBL_Visits.AreaID=TBL_Areas.ID " .
                "INNER JOIN TBL_OrganismData ON TBL_OrganismData.VisitID=TBL_Visits.ID " .
                "WHERE TBL_OrganismData.ID=$OrganismDataID";

        $AreaSet = $Database->Execute($SelectString);

        if ($AreaSet->FetchRow()) {
            $AreaID = $AreaSet->Field(1);
        }
        return($AreaID);
    }

    //******************************************************************************
    // Additional functions
    //******************************************************************************
    public static function GetSetFromAreaName($dbConn, $AreaName) {
        $SelectString = "SELECT * " .
                "FROM \"TBL_Areas\" " .
                "WHERE LOWER(\"AreaName\") ='" . strtolower($AreaName) . "'";

       $stmt = $dbConn->prepare($SelectString);
       $stmt->execute();

        $AreaSet = $stmt->fetch();

        if (!$AreaSet) {
            return false;
        }

        return($AreaSet);
    }

    //******************************************************************************
    public static function GetNonUniqueSetFromAreaName($Database, $AreaName, $ProjectID) {

        $SelectString = "SELECT * " .
                "FROM TBL_Areas "
                . " WHERE ProjectID= '" . $ProjectID . "'"
                . " AND  LOWER(AreaName) ='" . strtolower($AreaName) . "'";
        $AreaSet = $Database->Execute($SelectString);
        return($AreaSet);
    }

    public static function InsertPoint($dbConn, $ProjectID, $InsertLogID, $AreaName, $SubplotID, $RefX, $RefY, $CoordinateSystemID, $Accuracy, $AreaSubTypeID = null, $AreaCode = "", $Comments = null) {
        if ($AreaSubTypeID === null) {
            $AreaSubTypeID = Constants::AREA_SUBTYPE_POINT; // default to point?
        }

        if ($SubplotID !== null) {
            $SubplotTypeSet = LKUSubplotTypes::GetSetFromID($dbConn, $SubplotID);
            $AreaSubTypeID = $SubplotTypeSet["AreaSubtypeID"];
        }

        $AreaID = TBLAreas::Insert($dbConn, $AreaSubTypeID, $AreaName, $AreaCode, 1, $ProjectID, $InsertLogID, 0, $Accuracy, $Comments);
trigger_error(print_r($AreaID, 1));
        // add all the spatial data projection layers; do we need to also do a check for existing area with same x,y coords?
        TBLSpatialLayerData::Insert($dbConn, $AreaID, $RefX, $RefY, 0, 0, $CoordinateSystemID);

        RELAreaToArea::UpdateRelationships($dbConn, $AreaID, AREA_SUBTYPE_COUNTIES);

        return($AreaID);
    }

    public static function UpdatePoint($Database, $AreaID, $RefX, $RefY, $CoordinateSystemID) {
        // delete existing spatial data

        TBL_SpatialLayerData::DeleteFromAreaID($Database, $AreaID);

        TBL_SpatialGridded::DeleteFromAreaID($Database, $AreaID);

        // add new spatial layer data

        $ErrorString = TBL_SpatialLayerData::Insert($Database, $AreaID, $RefX, $RefY, 0, 0, $CoordinateSystemID); // projection; 1 for geographic, 2 for utm

        REL_AreaToArea::UpdateSurveyRelationships($Database, $AreaID, "C:/Inetpub/wwwroot/cwis438/temp/LogFile_" . TBL_UserSessions::GetID($Database, GetUserID()) . ".txt");

        TBL_SpatialGridded::InsertForAreaID($Database, $AreaID);
    }

    public static function InsertShape($dbConn, $ProjectID, $InsertLogID, $AreaName, $SubplotID, $GeometryString, $CoordinateSystemID, $Accuracy, $AreaSubTypeID = null, $AreaCode = "") {
        if ($AreaSubTypeID === null) {
            $AreaSubTypeID = Constants::AREA_SUBTYPE_POINT; // default to point?
        }

        if (($SubplotID !== null) && ($SubplotID != 0)) {
            $SubplotTypeSet = LKUSubplotTypes::GetSetFromID($dbConn, $SubplotID); //was $AreaSubTypeID
            $AreaSubTypeID = $SubplotTypeSet["AreaSubtypeID"];
        }

        $AreaSet = TBLAreas::GetSetFromAreaName($dbConn, $AreaName);
        $i = 0;

        while ($AreaSet) {
            $i++;
            $AreaSet = TBLAreas::GetSetFromAreaName($dbConn, $AreaName.'_'.$i);
        }

        if ($i > 0) {
            $AreaName .= '_'.$i;
        }

        $AreaID = TBLAreas::Insert($dbConn, $AreaSubTypeID, $AreaName, $AreaCode, 1, $ProjectID, $InsertLogID, 0, $Accuracy);

        // add all the spatial data projection layers; do we need to also do a check for existing area with same x,y coords?

        TBLSpatialLayerData::Insert($dbConn, $AreaID, 0, 0, 0, 0, $CoordinateSystemID, 0, $GeometryString);
        RELAreaToArea::UpdateRelationships($dbConn, $AreaID, AREA_SUBTYPE_COUNTIES);

        return $AreaID;
    }

    public static function UpdateShape($Database, $AreaID, $GeometryString) {
        // delete existing spatial data

        TBL_SpatialLayerData::DeleteFromAreaID($Database, $AreaID);
        TBL_SpatialGridded::DeleteFromAreaID($Database, $AreaID);
        TBL_SpatialLayerData::Insert($Database, $AreaID, 0, 0, 0, 0, 1, 0, $GeometryString);
        REL_AreaToArea::UpdateSurveyRelationships($Database, $AreaID);
        TBL_SpatialGridded::InsertForAreaID($Database, $AreaID);
    }
}

?>
