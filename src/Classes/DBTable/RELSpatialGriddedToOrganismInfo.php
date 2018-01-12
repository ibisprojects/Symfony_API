<?php

namespace Classes\DBTable;

//**************************************************************************************
// FileName: REL_SpatialGriddedToArea.php
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


define('DURATIONS', false);

//**************************************************************************************
// Class Definition
//**************************************************************************************


class RELSpatialGriddedToOrganismInfo {

    // jjg - had to put this here temporarily until we integerate the code (was in TBL_Areas)

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
    // Basic database functions
    //******************************************************************************

    private static function GetSet($Database_SpatialData, $OrganismInfoID, $ZoomLevel) {
        $REL_SpatialGriddedToOrganismInfo = "REL_SpatialGriddedToOrganismInfo_" . $ZoomLevel;

        $SelectString = "SELECT * " .
                "FROM $REL_SpatialGriddedToOrganismInfo " .
                "WHERE OrganismInfoID=$OrganismInfoID ";

        $Set = $Database_SpatialData->Execute($SelectString);

        return($Set);
    }

    private static function GetSetFromID($Database_SpatialData, $ID, $ZoomLevel) {
        $REL_SpatialGriddedToOrganismInfo = "REL_SpatialGriddedToOrganismInfo_" . $ZoomLevel;

        $ID = SQL::SafeInt($ID);

        $SelectString = "SELECT * " .
                "FROM $REL_SpatialGriddedToOrganismInfo " .
                "WHERE ID='" . $ID . "'";

        $Set = $Database_SpatialData->Execute($SelectString);

        return($Set);
    }

    private static function Insert($Database_SpatialData, $SpatialGriddedID, $OrganismInfoID, $ZoomLevel) {
        $REL_SpatialGriddedToOrganismInfo = "REL_SpatialGriddedToOrganismInfo_" . $ZoomLevel;

        $InsertString = "INSERT INTO $REL_SpatialGriddedToOrganismInfo " .
                "(SpatialGriddedID,OrganismInfoID,NumOrganismData,NumPresent,NumAbsent) " .
                "VALUES ($SpatialGriddedID,$OrganismInfoID,1,0,0) ";

        //DebugWriteln("----------- InsertString=$InsertString");

        $Database_SpatialData->Execute($InsertString);

        // delete files related to an organism info

        TBL_SpatialGridded::DeleteTiles($Database_SpatialData, $ZoomLevel, $SpatialGriddedID, $OrganismInfoID, 0, 0);

//		return($ID);
    }

    public static function Delete($Database_SpatialData, $ID, $ZoomLevel) {
        $REL_SpatialGriddedToOrganismInfo = "REL_SpatialGriddedToOrganismInfo_" . $ZoomLevel;

        $Set = REL_SpatialGriddedToOrganismInfo::GetSetFromID($Database_SpatialData, $ID, $ZoomLevel);

//		DebugWriteln("REL_SpatialGriddedToOrganismInfo::Delete()");

        $SpatialGriddedID = $Set->Field("SpatialGriddedID");

        TBL_SpatialGridded::DeleteTiles($Database_SpatialData, $ZoomLevel, $SpatialGriddedID, $Set->Field("OrganismInfoID"), 0, 0);

        //

        $DeleteString = "DELETE FROM $REL_SpatialGriddedToOrganismInfo WHERE ID=$ID";

        $Database_SpatialData->Execute($DeleteString);
    }

    //******************************************************************************
    // Additional public functions
    //******************************************************************************
    public static function AddSpatialGridRelationship($Database, $OrganismDataID) {
    //
    // Add the grid relationships for a specific organismdata
    //
    // Called by TBL_OrganismData
        $Database_SpatialData = new DB_Connection();
        $Database_SpatialData->Connect("SpatialData_GoogleMaps", "sa", "cheatgrass");

        // get the OrganismInfo ID

        $OrganismDataSet = $Database->Execute("SELECT * FROM TBL_OrganismData WHERE ID=$OrganismDataID");
        //   	$OrganismDataSet=TBL_OrganismData::GetSetFromID($Database,$OrganismDataID);

        $OrganismInfoID = $OrganismDataSet->Field("OrganismInfoID");
//		DebugWriteln("OrganismInfoID=$OrganismInfoID");
        // get the assocaited areaID

        $AreaID = REL_SpatialGriddedToOrganismInfo::GetAreaIDFromOrganismDataID($Database, $OrganismDataID);
//    	$AreaID=TBL_Areas::GetAreaIDFromOrganismDataID($Database,$OrganismDataID);
//		DebugWriteln("AreaID=$AreaID");
        // add or increment the relationships at each zoom level

        if ($OrganismInfoID != null) { // have an organism
            for ($ZoomLevel = ZOOM_LEVEL_MIN; $ZoomLevel <= ZOOM_LEVEL_MAX; $ZoomLevel++) {
                $TBL_SpatialGridded = "TBL_SpatialGridded_" . $ZoomLevel;
                $REL_SpatialGriddedToOrganismInfo = "REL_SpatialGriddedToOrganismInfo_" . $ZoomLevel;
                $REL_SpatialGriddedToArea = "REL_SpatialGriddedToArea_" . $ZoomLevel;

//				DebugWriteln("ZoomLevel=$ZoomLevel");
                // get the set of relationships for this species, area, and this zoom level.
//		    	$SpatialGriddedToOrganismInfoSet=REL_SpatialGriddedToOrganismInfo::GetSet($Database_SpatialData,
//		    		$OrganismInfoID,$ZoomLevel);
//		    	$SpatialGriddedToOrganismInfoSet=REL_SpatialGriddedToOrganismInfo::GetSetFromAreaID($Database_SpatialData,
//		    		$AreaID,$OrganismInfoID,$ZoomLevel);
                // get the set of cells associated with the areaID that has the organism data in an associated visit

                $SelectString = "SELECT SpatialGriddedID " .
                        "FROM $REL_SpatialGriddedToArea " .
                        "WHERE AreaID=$AreaID ";

                $SpatialGriddedSet = $Database_SpatialData->Execute($SelectString);

                while ($SpatialGriddedSet->FetchRow()) {
                    $SpatialGriddedID = $SpatialGriddedSet->Field(1); // Cell we are interested in
                    // get the existing OrganismInfo relationship if any

                    $SelectString = "SELECT ID " .
                            "FROM $REL_SpatialGriddedToOrganismInfo " .
                            "WHERE OrganismInfoID=$OrganismInfoID " .
                            "AND SpatialGriddedID=$SpatialGriddedID";

                    $SpatialGriddedToOrganismInfoSet = $Database_SpatialData->Execute($SelectString);

                    if ($SpatialGriddedToOrganismInfoSet->FetchRow()) { // update an existing record
                        // get the ID of the existing relationship and update it

                        $SpatialGriddedToOrganismInfoID = $SpatialGriddedToOrganismInfoSet->Field(1);

                        $UpdateString = "UPDATE $REL_SpatialGriddedToOrganismInfo " .
                                "SET NumOrganismData=NumOrganismData+1 " .
                                "WHERE ID=$SpatialGriddedToOrganismInfoID";

                        $Database_SpatialData->Execute($UpdateString);
                    } else { // insert a new record
                        REL_SpatialGriddedToOrganismInfo::Insert($Database_SpatialData, $SpatialGriddedID, $OrganismInfoID, $ZoomLevel);
                    }
                }
                /* 		    	if ($SpatialGriddedToOrganismInfoSet->FetchRow()) // relationships exist (already an organism data with the same oragnisminfo at this location)
                  {
                  $StartTime=GetMicroTime();

                  do // increment all existing relationships (there should only be one)
                  {
                  $SpatialGriddedToOrganismInfoID=$SpatialGriddedToOrganismInfoSet->Field("ID");
                  //DebugWriteln("SpatialGriddedToOrganismInfoID 2=$SpatialGriddedToOrganismInfoID");

                  $UpdateString="UPDATE $REL_SpatialGriddedToOrganismInfo ".
                  "SET NumOrganismData=NumOrganismData+1 ".
                  "WHERE ID=$SpatialGriddedToOrganismInfoID";
                  //				DebugWriteln("UpdateString=$UpdateString");

                  $Database_SpatialData->Execute($UpdateString);
                  }
                  while ($SpatialGriddedToOrganismInfoSet->FetchRow());

                  //		    		DebugWriteln("REL_SpatialGriddedToOrganismInfo While 1 duration=".(GetMicroTime()-$StartTime));
                  }
                  else // add new relationship
                  {
                  // get all the spatialgridded data for the organism's area (polygons will have more than one)

                  $SelectString="SELECT $TBL_SpatialGridded.ID ".
                  "FROM $TBL_SpatialGridded ".
                  "INNER JOIN $REL_SpatialGriddedToArea ".
                  "ON $REL_SpatialGriddedToArea.SpatialGriddedID=$TBL_SpatialGridded.ID ".
                  "WHERE $REL_SpatialGriddedToArea.AreaID=$AreaID ";
                  //			    			"AND $TBL_SpatialGridded.ZoomLevel=$ZoomLevel";

                  $Set=$Database_SpatialData->Execute($SelectString);

                  // get the existing area relationship

                  //		    		$SpatialGriddedID=TBL_SpatialGridded::GetIDFromAreaID($Database,$AreaID,$ZoomLevel);

                  $StartTime=GetMicroTime();
                  while ($Set->FetchRow())
                  {
                  $SpatialGriddedID=$Set->Field("ID");
                  //DebugWriteln("Inserting: SpatialGriddedID=$SpatialGriddedID, OrganismInfoID=$OrganismInfoID, ZoomLevel=$ZoomLevel");

                  REL_SpatialGriddedToOrganismInfo::Insert($Database_SpatialData,$SpatialGriddedID,$OrganismInfoID,$ZoomLevel);
                  }
                  //		    		DebugWriteln("REL_SpatialGriddedToOrganismInfo While 2 duration=".(GetMicroTime()-$StartTime));
                  }
                 */
            }
        }
//DebugWriteln("Returning from AddSpatialGridRelationship");
    }

    public static function RemoveSpatialGridRelationship($Database, $OrganismDataID) {
    //
    // Called by TBL_OrganismData//

        $Database_SpatialData = new DB_Connection();
        $Database_SpatialData->Connect("SpatialData_GoogleMaps", "sa", "cheatgrass");

//		DebugWriteln("REL_SpatialGriddedToOrganismInfo::RemoveSpatialGridRelationship()");
        // get the OrganismInfo ID

        $OrganismDataSet = $Database->Execute("SELECT * FROM TBL_OrganismData WHERE ID=$OrganismDataID");
//    	$OrganismDataSet=TBL_OrganismData::GetSetFromID($Database,$OrganismDataID);

        $OrganismInfoID = $OrganismDataSet->Field("OrganismInfoID");

        // get the assocaited areaID

        $AreaID = REL_SpatialGriddedToOrganismInfo::GetAreaIDFromOrganismDataID($Database, $OrganismDataID);
//    	$AreaID=TBL_Areas::GetAreaIDFromOrganismDataID($Database,$OrganismDataID);
//		DebugWriteln("REL_SpatialGriddedToOrganismInfo::RemoveSpatialGridRelationship() AreaID=$AreaID");
        // get the set of relationships

        for ($ZoomLevel = ZOOM_LEVEL_MIN; $ZoomLevel <= ZOOM_LEVEL_MAX; $ZoomLevel++) {
            $TBL_SpatialGridded = "TBL_SpatialGridded_" . $ZoomLevel;
            $REL_SpatialGriddedToOrganismInfo = "REL_SpatialGriddedToOrganismInfo_" . $ZoomLevel;
            $REL_SpatialGriddedToArea = "REL_SpatialGriddedToArea_" . $ZoomLevel;

            // get the set of cells associated with the areaID that has the organism data in an associated visit

            $SelectString = "SELECT SpatialGriddedID " .
                    "FROM $REL_SpatialGriddedToArea " .
                    "WHERE AreaID=$AreaID ";

            $SpatialGriddedSet = $Database_SpatialData->Execute($SelectString);

            while ($SpatialGriddedSet->FetchRow()) {
                $SpatialGriddedID = $SpatialGriddedSet->Field(1); // Cell we are interested in
                // get the existing OrganismInfo relationship if any

                $SelectString = "SELECT ID,NumOrganismData " .
                        "FROM $REL_SpatialGriddedToOrganismInfo " .
                        "WHERE OrganismInfoID=$OrganismInfoID " .
                        "AND SpatialGriddedID=$SpatialGriddedID";

                $SpatialGriddedToOrganismInfoSet = $Database_SpatialData->Execute($SelectString);

                if ($SpatialGriddedToOrganismInfoSet->FetchRow()) { // update an existing record
//DebugWriteln("3");
                    $NumOrganismData = $SpatialGriddedToOrganismInfoSet->Field("NumOrganismData");
                    $SpatialGriddedToOrganismInfoID = $SpatialGriddedToOrganismInfoSet->Field("ID");
//DebugWriteln("NumOrganismData=$NumOrganismData");

                    if ($NumOrganismData <= 1) { // delete the relationship
                        REL_SpatialGriddedToOrganismInfo::Delete($Database_SpatialData, $SpatialGriddedToOrganismInfoID, $ZoomLevel);
                    } else { // decrement the count
                        $UpdateString = "UPDATE $REL_SpatialGriddedToOrganismInfo " .
                                "SET NumOrganismData=NumOrganismData-1 " .
                                "WHERE ID=$SpatialGriddedToOrganismInfoID";

                        $Database_SpatialData->Execute($UpdateString);
                    }
//DebugWriteln("4");
                } else { //
                    Writeln("*********** Error: Missing REL_SpatialGriddedToOrganismInfo: AreaID=$AreaID, OrganismInfoID=$OrganismInfoID, ZoomLevel=$ZoomLevel");
                }
            }
        }
    }

    public static function UpdateAttributeData($dbConn, $Database_SpatialData = null, $AttributeID, $InsertFlag = true) {
        if ($Database_SpatialData == null) {
            $Database_SpatialData=new DB_Connection();
            $Database_SpatialData->Connect("SpatialData_GoogleMaps","sa","cheatgrass");
        }

        // get the OrganismDataID

        $StartTime = GetMicrotime();
        $AttributeDataSet = $Database->Execute("SELECT * FROM TBL_AttributeData WHERE ID=$AttributeID");

        $AttributeTypeID = $AttributeDataSet->Field("AttributeTypeID");

        if ($AttributeTypeID == ATTRIBUTE_PRESENCE) { // only worry about presence values
            $AttributeValueID = $AttributeDataSet->Field("AttributeValueID");
            $OrganismDataID = $AttributeDataSet->Field("OrganismDataID");
            if (DURATIONS)
                DebugWriteln("UpdateAttributeData::GetOrganismDataID Duration=" . (GetMicrotime() - $StartTime));

            // get the OrganismInfoID
            $OrganismDataSet = $Database->Execute("SELECT * FROM TBL_OrganismData WHERE ID=$OrganismDataID");
            $OrganismInfoID = $OrganismDataSet->Field("OrganismInfoID");

            // get the assocaited areaID

            $AreaID = REL_SpatialGriddedToOrganismInfo::GetAreaIDFromOrganismDataID($Database, $OrganismDataID);
            // get the set of relationships

            if ($OrganismInfoID != null) {
                for ($ZoomLevel = ZOOM_LEVEL_MIN; $ZoomLevel <= ZOOM_LEVEL_MAX; $ZoomLevel++) {

                    $TBL_SpatialGridded = "TBL_SpatialGridded_" . $ZoomLevel;
                    $REL_SpatialGriddedToOrganismInfo = "REL_SpatialGriddedToOrganismInfo_" . $ZoomLevel;
                    $REL_SpatialGriddedToArea = "REL_SpatialGriddedToArea_" . $ZoomLevel;

                    // get the set of cells associated with the areaID that has the organism data in an associated visit

                    $StartTime = GetMicrotime();
                    $SelectString = "SELECT SpatialGriddedID " .
                            "FROM $REL_SpatialGriddedToArea " .
                            "WHERE AreaID=$AreaID ";

                    $SpatialGriddedSet = $Database_SpatialData->Execute($SelectString);
                    while ($SpatialGriddedSet->FetchRow()) {
                        $SpatialGriddedID = $SpatialGriddedSet->Field(1); // Cell we are interested in
                        $StartTime = GetMicrotime();
                        $SelectString = "SELECT ID " .
                                "FROM $REL_SpatialGriddedToOrganismInfo " .
                                "WHERE OrganismInfoID=$OrganismInfoID " .
                                "AND SpatialGriddedID=$SpatialGriddedID";

                        $SpatialGriddedToOrganismInfoSet = $Database_SpatialData->Execute($SelectString);
                        if (DURATIONS)
                            DebugWriteln("UpdateAttributeData::GetSpatialGriddedToOrganismInfoSet Duration=" . (GetMicrotime() - $StartTime));

                        if ($SpatialGriddedToOrganismInfoSet->FetchRow()) { // update an existing record
                            $StartTime = GetMicrotime();
                            $SpatialGriddedToOrganismInfoID = $SpatialGriddedToOrganismInfoSet->Field("ID");
//						DebugWriteln("-- SpatialGriddedToOrganismInfoID=$SpatialGriddedToOrganismInfoID");
                            // get the appropriate SQL string to increment or decrement

                            if ($InsertFlag) { // inserting
                                if ($AttributeValueID == ATTRIBUTE_VALUE_PRESENT) {
                                    $UpdateString = "NumPresent=NumPresent+1 ";
                                } else { // ATTRIBUTE_VALUE_ABSENT
                                    $UpdateString = "NumAbsent=NumAbsent+1 ";
                                }
                            } else { // deleting
                                if ($AttributeValueID == ATTRIBUTE_VALUE_PRESENT) {
                                    $UpdateString = "NumPresent=NumPresent-1 ";
                                } else { // ATTRIBUTE_VALUE_ABSENT
                                    $UpdateString = "NumAbsent=NumAbsent-1 ";
                                }
                            }
                            if (DURATIONS)
                                DebugWriteln("UpdateAttributeData::InsertFlag Duration=" . (GetMicrotime() - $StartTime));

                            // do the update

                            $StartTime = GetMicrotime();
                            $UpdateString = "UPDATE $REL_SpatialGriddedToOrganismInfo " .
                                    "SET $UpdateString " .
                                    "WHERE ID=$SpatialGriddedToOrganismInfoID";

//		DebugWriteln("----------- UpdateString=$UpdateString");

                            $Database_SpatialData->Execute($UpdateString);
                            if (DURATIONS)
                                DebugWriteln("UpdateAttributeData::UPDATE Duration=" . (GetMicrotime() - $StartTime));

                            // get the SpatialGriddedID and delete any associated tiles

                            $StartTime = GetMicrotime();
                            TBL_SpatialGridded::DeleteTiles($Database_SpatialData, $ZoomLevel, $SpatialGriddedID, $OrganismInfoID, 0, 0);
                            if (DURATIONS)
                                DebugWriteln("UpdateAttributeData::DeleteTiles Duration=" . (GetMicrotime() - $StartTime));
                        }
                        //			    	else //
                        //			    	{
                        //		    			Writeln("*********** Error: Missing REL_SpatialGriddedToArea: AreaID=$AreaID, ZoomLevel=$ZoomLevel");
                        //		    		}
                    }
                }
            }
        }
    }

}

?>
