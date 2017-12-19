<?php

namespace Classes\DBTable;

//**************************************************************************************
// FileName: TBL_Projects.php
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

//******************************************************************************
// Definitions
//******************************************************************************
// "Search For" options
use Classes\TBLDBTables;

define("TBL_PROJECTS_SEARCH_IN_NAME_ONLY", 1);
define("TBL_PROJECTS_MATCH_START", 1);

//******************************************************************************
// Class 
//******************************************************************************

class TBLProjects  {

    //**********************************************************************************
    // TBL_DBTables functions
    //**********************************************************************************

    public static function GetFieldValue($Database, $FieldName, $ID, $Default = 0) {
        $Result = TBL_DBTables::GetFieldValue($Database, "TBL_Projects", $FieldName, $ID, $Default);

        return($Result);
    }

    public static function SetFieldValue($Database, $FieldName, $ID, $Value) {
        TBL_DBTables::SetFieldValue($Database, "TBL_Projects", $FieldName, $ID, $Value);
    }

    //******************************************************************************
    // Private functions 
    //******************************************************************************

    public static function AddSearchWhereClause($Database, $SelectString, $SearchString = null, $SearchIn = null, $MatchTo = null, $FirstLetter = null, $OrganizationID = null, $OrganismInfoID = null, $WebsiteID = null, $Active = null, $ProjectMemberID = null) {
//     	DebugWriteln("FirstLetter=$FirstLetter");
        if ($FirstLetter != null)
            TBL_DBTables::AddWhereClause($SelectString, "ProjName LIKE '$FirstLetter%'");
        if ($OrganizationID != null)
            TBL_DBTables::AddWhereClause($SelectString, "OrganizationID=$OrganizationID");
        if ($Active !== null)
            TBL_DBTables::AddWhereClause($SelectString, "Active=$Active");

        if (($SearchString !== null) && ($SearchString != "")) {
            $SearchCriteria = "%$SearchString%";

            switch ($MatchTo) {
                case TBL_PROJECTS_MATCH_START:
                    $SearchCriteria = "$SearchString%";
                    break;
            }

            switch ($SearchIn) {
                case TBL_PROJECTS_SEARCH_IN_NAME_ONLY:
                    TBL_DBTables::AddWhereClause($SelectString, "(ProjName LIKE '$SearchCriteria') ");
                    break;
                default:
                    TBL_DBTables::AddWhereClause($SelectString, "(ProjName LIKE '$SearchCriteria' " .
                            "OR Description LIKE '$SearchCriteria')");
                    break;
            }
        }

        if ($OrganismInfoID != 0) {
            TBL_DBTables::AddWhereClause($SelectString, "ID IN (" .
                    "SELECT TBL_Projects.ID " .
                    "FROM TBL_OrganismData INNER JOIN " .
                    "TBL_Visits ON TBL_OrganismData.VisitID = TBL_Visits.ID INNER JOIN " .
                    "TBL_Projects ON TBL_Visits.ProjectID = TBL_Projects.ID " .
                    "WHERE (TBL_OrganismData.OrganismInfoID = $OrganismInfoID))");
        }

        if ($ProjectMemberID != 0) {
            TBL_DBTables::AddWhereClause($SelectString, "ID IN (" .
                    "SELECT DISTINCT ProjectID " .
                    "FROM REL_PersonToProject " .
                    "WHERE (PersonID=$ProjectMemberID) AND (Role IS NOT NULL))");
        }

//		DebugWriteln("WebsiteID=$WebsiteID");
        if ($WebsiteID != null) { //
            $Set = TBL_Websites::GetSetFromID($Database, $WebsiteID);

            if ($Set->Field("LimitProjects") == 1) {
                TBL_DBTables::AddWhereClause($SelectString, "ID IN (" .
                        "SELECT ProjectID " .
                        "FROM REL_WebsiteToProject " .
                        "WHERE (WebsiteID=$WebsiteID))");
            }
        }
//		DebugWriteln("SelectString=$SelectString");
    }

    //******************************************************************************
    // Basic database functions
    //******************************************************************************

    public static function GetSet($Database, $Code = NOT_SPECIFIED) {
        $SelectString = "SELECT * " .
                "FROM TBL_Projects ";

        if ($Code != NOT_SPECIFIED)
            TBL_DBTables::AddWhereClause($SelectString, "Code='$Code'");

        $SelectString.=" ORDER BY ProjName";

//		DebugWriteln("SelectString=$SelectString");

        $ProjectInfoSet = $Database->Execute($SelectString);

        return($ProjectInfoSet);
    }

    public static function GetSetFromID($dbConn, $ProjectID) {
        $ProjectID = (int)($ProjectID);

        $SelectString = "SELECT * " .
                "FROM TBL_Projects " .
                "WHERE ID= :ProjectID";

        $stmt = $dbConn->prepare($SelectString);
        $stmt->bindValue("ProjectID", $ProjectID);       
        $stmt->execute();
        $project = $stmt->fetch();
        if (!$project) {
            return false;
        }
        return $project;
    }

    public static function GetTotalRows($Database, $SearchString = null, $SearchIn = null, $MatchTo = null, $FirstLetter = null, $OrganizationID = null, $OrganismInfoID = null, $WebsiteID = null, $Active = null, $ProjectMemberID = null) {
    //
    // Returns thenumber of rows in the desired query
    //
	// Parameters for all classes:
    //	SearchString - a string to search in text associted with the project
    //	
    // Class specific fields:
    //	SearchIn - definition for which fields to search in (see definitions at top of the file)
    //	OrganizationID - just return this organizations projects
    // 	$WebsiteID - filter projects for the specified web site
    //	Active - just return projects with the specified active value//
     
        //    	DebugWriteln("WebsiteID=$WebsiteID");
        // get the query for $CurrentRow+$NumRows rows in reversed order

        $SelectString = "SELECT COUNT(*) " .
                "FROM TBL_Projects ";

        TBL_Projects::AddSearchWhereClause($Database, $SelectString, $SearchString, $SearchIn, $MatchTo, $FirstLetter, $OrganizationID, $OrganismInfoID, $WebsiteID, $Active, $ProjectMemberID);

//		DebugWriteln("SelectString=$SelectString");

        $Set = $Database->Execute($SelectString);

        return($Set->Field(1));
    }

    public static function GetRows($Database, $CurrentRow, $NumRows = 1000000, $TotalRows = 1000000, $OrderByField = null, $DescendingFlag = null, $Fields = null, $SearchString = null, $SearchIn = null, $MatchTo = null, $FirstLetter = null, $OrganizationID = null, $OrganismInfoID = null, $WebsiteID = null, $Active = null, $ProjectMemberID = null) {
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
    // 	$WebsiteID - just return Citizen Science projects
    //	Active - just return projects with the specified active value//
    
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
                "FROM TBL_Projects ";

        TBL_Projects::AddSearchWhereClause($Database, $SelectString1, $SearchString, $SearchIn, $MatchTo, $FirstLetter, $OrganizationID, $OrganismInfoID, $WebsiteID, $Active, $ProjectMemberID);

        if ($OrderByField != null) {
            TBL_DBTables::AddOrderByClause($SelectString1, $OrderByField, !$DescendingFlag); // query the rows in the opposite order of what the user wants
        }

        // create the query that gets the top $NumRows and reverses the order to make it the way the user wants

        $SelectString = TBL_DBTables::GetSelectClause(0, $NumRows, $Fields);

        $SelectString.="FROM TBL_Projects " .
                "WHERE ID IN ($SelectString1) ";

        if ($OrderByField != null) {
            $SelectString.="ORDER BY $OrderByField ";

            if ($DescendingFlag)
                $SelectString.="DESC "; // can't use order by function, finds previous order by
        }
//		DebugWriteln("SelectString=$SelectString");

        $Set = $Database->Execute($SelectString);

        return($Set);
    }

    public static function Insert($Database, $Name = "Untitled", $InstigatorID = 0) {
        $Name = SafeString($Name);

        $ProjectID = -1;

        $ExecString = "EXEC insert_TBL_Projects '$Name'";

        $ProjectID = $Database->DoInsert($ExecString);

        $UpdateString = "UPDATE TBL_Projects " .
                "SET InstigatorID=$InstigatorID " .
                "WHERE ID=$ProjectID";

        $Database->Execute($UpdateString);

        return($ProjectID);
    }

    public static function AddData($Database, $UploadedFilePath, $ProjectID, $PersonID, $InsertLogType, $JobID = NOT_SPECIFIED, $InsertLogID = NOT_SPECIFIED) {
        if (file_exists($UploadedFilePath)) {
            //DebugWriteln("InsertLOgID=$InsertLogID");
            $UserSessionID = null;

            //$PersonID=GetUserID();

            if ($UserSessionID === null)
                $UserSessionID = TBL_UserSessions::GetID($Database, $PersonID);

            // Create insertlog in TBL_InsertLogs

            $Name = GetFileNameFromFilePath($UploadedFilePath);

            if ($InsertLogID == NOT_SPECIFIED) {
                $InsertLogID = TBL_InsertLogs::Insert($Database, $InsertLogType, $UserSessionID, $Name, $UploadedFilePath, NOT_SPECIFIED, $PersonID, NOT_SPECIFIED, $ProjectID);
            }

            if ($JobID != NOT_SPECIFIED) {
                TBL_Jobs::SetFieldValue($Database, "InsertLogID", $JobID, $InsertLogID);
            }

            TBL_JobOutputs::Log($Database, $JobID, "InsertLogID=$InsertLogID");

            $NumRecordsInserted = 0;

            $CoordinateSystemID = 1;  // WGS84

            $Lines = file($UploadedFilePath);

            $RecordCount = count($Lines) - 1; // -1 for the header

            TBL_JobOutputs::Log($Database, $JobID, "RecordCount=$RecordCount");

            $FirstLine = $Lines[0];  // first line has the header
            // process first line to get concept names (trim and explode)

            $IBISConceptNames = array_map('trim', explode("\t", $FirstLine));

            //print_r($IBISConceptNames);

            function AlterArrayElement($val) {
                $val = preg_replace('/\s+/', '', $val);
            }

            array_walk($IBISConceptNames, "AlterArrayElement");

            // print_r($IBISConceptNames); 
            // get the conceptname indexes

            $ScientificNameIndex = null;  // not required, if not in file, added as visit only
            $RefYIndex = null;  // longitude required
            $RefXIndex = null;  // latitude required
            $VisitDateIndex = null;  // required
            $AreaNameIndex = null;  // location name not required
            $AreaSubTypeNameIndex = null;     //not required
            $SubplotTypeNameIndex = null;  //not required
            $PresenceIndex = null;  //not required
            $VisitCommentsIndex = null;  //not required

            $URLIndex = null; //not required
            $RightsIndex = null; //not required
            $OrganizationIndex = null; //not required  
            $GUIDIndex = null; //not required 

            $ScientificNameIndex = array_search('ScientificName', $IBISConceptNames);
            $RefYIndex = array_search('Latitude', $IBISConceptNames);
            $RefXIndex = array_search('Longitude', $IBISConceptNames);
            $VisitDateIndex = array_search('Date', $IBISConceptNames);
            $AreaNameIndex = array_search('LocationName', $IBISConceptNames);
            $AreaSubTypeNameIndex = array_search('PlotName', $IBISConceptNames);
            $SubplotTypeNameIndex = array_search('SubplotName', $IBISConceptNames);
            $PresenceIndex = array_search('Presence', $IBISConceptNames);
            $VisitCommentsIndex = array_search('Comments', $IBISConceptNames);

            $URLIndex = array_search('URL', $IBISConceptNames);
            $RightsIndex = array_search('Rights', $IBISConceptNames);
            $OrganizationIndex = array_search('Organization', $IBISConceptNames);
            $GUIDIndex = array_search('GUID', $IBISConceptNames);

            //$AreaCommentsIndex=array_search('AreaComments',$IBISConceptNames);
            //$OrganismCommentsIndex=array_search('OrganismComments',$IBISConceptNames);
            // loop through database for all existing AttributeTypes (e.g., LKU_AttributeTypes)
            // for each Attribute Type... search for matching column name in the file
            // Establish the appropriate (dynamically named variable names) index

            $AttributeTypeSet = LKU_AttributeTypes::GetSet($Database);

            //print_r($IBISConceptNames[0]);

            while ($AttributeTypeSet->FetchRow()) {
                // create variable names for each one
                $AttributeTypeName = $AttributeTypeSet->Field("Name");
                $AttributeTypeNameTrimmed = trim($AttributeTypeName); // remove beginning and trailing whitespace
                $AttributeTypeNameTrimmed = preg_replace('/\s+/', '', $AttributeTypeNameTrimmed); // remove middle whitespace
                // TBL_JobOutputs::Log($Database,$JobID,"$AttributeTypeNameTrimmed");

                $AttributeTypeIndex = $AttributeTypeNameTrimmed . "Index";

                $$AttributeTypeIndex = null;
                $$AttributeTypeIndex = array_search($AttributeTypeNameTrimmed, $IBISConceptNames);

                if ($$AttributeTypeIndex !== false) { // ""
                    // TBL_JobOutputs::Log($Database,$JobID,"AttributeName=$AttributeTypeNameTrimmed");
                    //TBL_JobOutputs::Log($Database,$JobID,"Index=$$AttributeTypeIndex");
                    //DebugWriteln("AttributeTypeName=$AttributeTypeName AttributeTypeNameTrimmed=$AttributeTypeNameTrimmed and AttributeTypeIndexName=".$$AttributeTypeIndex);
                }
            }

            // process the remaining lines

            for ($i = 0; $i < $RecordCount; $i++) { // for each record
                $Line = $Lines[$i + 1];

                $IBISValues = explode("\t", $Line);

                if (count($IBISValues) > 0) {
                    $RefY = $IBISValues[$RefYIndex];
                    $RefX = $IBISValues[$RefXIndex];

                    // If columns are not in file, set up to not add via AddPoint
                    // AreaSubTypeID

                    $AreaSubTypeID = null;
                    $AreaSubTypeName = null;

                    if ($AreaSubTypeNameIndex === false) { // we do not find a column heading in the file entitled 'PlotName'
                        $AreaSubTypeID = null; // -1 (AddPoint seems to expect null for these if not needed)
                    } else {
                        $AreaSubTypeName = trim($IBISValues[$AreaSubTypeNameIndex]);
                        //DebugWriteln("AreaSubTypeName=$AreaSubTypeName");
                        $AreaSubTypeID = LKU_AreaSubTypes::GetIDFromName($Database, $AreaSubTypeName, $AreaSubTypeID);
                        //DebugWriteln("AreaSubTypeID=$AreaSubTypeID");
                        //die();
                    }

                    // SubplotID

                    $SubplotName = null;
                    $SubplotID = null;

                    if ($SubplotTypeNameIndex === false) { // we do not find a column heading in the file entitled 'SubplotName'
                        $SubplotID = null; // NOT_SPECIFIED -1 (AddPoint seems to expect null for these if not needed)
                    } else {
                        $SubplotName = trim($IBISValues[$SubplotTypeNameIndex]);
                        //DebugWriteln("SubplotName=$SubplotName");
                        //DebugWriteln("AreaSubTypeID=$AreaSubTypeID");

                        $SubplotID = LKU_SubplotTypes::GetIDFromTypeName($Database, $SubplotName, $AreaSubTypeID);
                        //DebugWriteln("SubplotID=$SubplotID");
                        //die();
                    }

                    // AreaName

                    if ($AreaNameIndex === false) {
                        $AreaName = 'Not Specified';
                    } else {
                        $AreaName = trim($IBISValues[$AreaNameIndex]);
                    }

                    if ($PresenceIndex !== false)
                        $Presence = $IBISValues[$PresenceIndex];
                    else
                        $Presence = -1;

                    $Presence = trim($Presence);

                    if ($Presence === "Present") {
                        $Presence = true;
                    } else if ($Presence === "Absent") {
                        $Presence = false;
                    } else {
                        $Presence = -1;
                    }

                    // die();

                    $VisitDate = new Date();


                    $VisitDateString = $IBISValues[$VisitDateIndex];

                    //DebugWriteln("VisitDateString=$VisitDateString");

                    $VisitDate->SetDateFromString($VisitDateString);

                    if ($VisitCommentsIndex === false) { // if we did not find a visitcomments column
                        $VisitComments = null;
                    } else {
                        $VisitComments = trim($IBISValues[$VisitCommentsIndex]);
                    }

                    // URL

                    if ($URLIndex === false) {
                        $URL = null;
                    } else {
                        $URL = trim($IBISValues[$URLIndex]);
                    }

                    if ($GUIDIndex === false) {
                        $GUID = null;
                    } else {
                        $GUID = trim($IBISValues[$GUIDIndex]);
                    }

                    // Rights

                    if ($RightsIndex === false) {
                        $Rights = null;
                    } else {
                        $Rights = trim($IBISValues[$RightsIndex]);
                    }

                    // Organization

                    if ($OrganizationIndex === false) {
                        $OrganizationName = null;
                    } else {
                        $OrganizationName = trim($IBISValues[$OrganizationIndex]);
                    }

                    if ($ScientificNameIndex === false) { // if we did not find a scientificname column
                        // add areaid and visitid via InsertVisitOnly function (TBL_Visits::InsertVisitOnly)

                        $VisitID = TBL_Visits::InsertVisitOnly($Database, $PersonID, $VisitDate, $RefX, $RefY, $ProjectID, $AreaName, $SubplotID, $CoordinateSystemID, null, NOT_SPECIFIED, $InsertLogType, $VisitComments, $InsertLogID); // accuracy is null
                        //InsertVisitOnly($Database,$UserID,$Date,$X,$Y,$ProjectID,
                        //$AreaName,$SubplotID,$CoordinateSystemID,$Accuracy,$FormID=NOT_SPECIFIED,$InsertLogType=INSERT_LOG_FORM,$VisitComments=null,$InsertLogID=NOT_SPECIFIED)

                        $NumRecordsInserted++;

                        if ($JobID != NOT_SPECIFIED) {
                            TBL_Jobs::UpdateProgress($Database, $JobID, $NumRecordsInserted);
                        }
                    } else { // use AddPoint to add AreaID, VisitID, and asasociated OrganismdataID
                        // match sciname to OrganismInfoID in invasive database

                        $ScientificName = $IBISValues[$ScientificNameIndex];

                        $ScientificName = trim($ScientificName); // remove beginning and trailing whitespace
                        $ScientificName = str_replace(".", "", $ScientificName);  // remove '.'
                        $ScientificName = preg_replace('/\s\s+/', ' ', $ScientificName); // remove middle whitespace
                        $ScientificName = preg_replace('/\bx\b/i', '', $ScientificName); // removes exact words only of case x for species crosses
                        //$ScientificName=str_replace("ssp","",$ScientificName);
                        //$ScientificName=str_replace("sp","",$ScientificName);
                        // $ScientificName=str_replace("spp","",$ScientificName);
                        //$ScientificName=str_replace("n","",$ScientificName);

                        $ScientificName = preg_replace('/\s\s+/', ' ', $ScientificName); // remove middle whitespace

                        $SciNamePiecesArray = explode(" ", $ScientificName);

                        $Count = 1;

                        $UnitName1 = "";
                        $UnitName2 = "";
                        $UnitName3 = "";
                        $UnitName4 = "";

                        if (isset($SciNamePiecesArray[0]))
                            $UnitName1 = $SciNamePiecesArray[0];
                        if (isset($SciNamePiecesArray[1]))
                            $UnitName2 = $SciNamePiecesArray[1];
                        if (isset($SciNamePiecesArray[2]))
                            $UnitName3 = $SciNamePiecesArray[2];
                        if (isset($SciNamePiecesArray[3]))
                            $UnitName4 = $SciNamePiecesArray[3];

                        $TSN = TBL_TaxonUnits::FindTSNForScientificNamePieces($Database, $Count, KINGDOM_ANY, $UnitName1, $UnitName2, $UnitName3, $UnitName4, $ExcludeSynonyms = false, $RequiredParentTSN = null);

                        //DebugWriteln("AreaName=$AreaName, ScientificName=$ScientificName, Presence=$Presence");
                        //DebugWriteln("UnitName1=$UnitName1, UnitName2=$UnitName2, UnitName3=$UnitName3, TSN=$TSN");

                        if ($TSN != 0) {
                            // get OrganimsInfoID's for the found TSN

                            $TempSciName = TBL_TaxonUnits::GetScientificNameFromTSN($Database, $TSN, FALSE);

                            $Set = TBL_OrganismInfos::GetSetFromTSN($Database, $TSN, null); // null is for GroupFlag

                            $OrganismInfoID = $Set->Field("ID");

                            if ($OrganismInfoID == null) {
                                $OrganismInfoID = TBL_OrganismInfos::Insert($Database, $TSN, $TempSciName);
                                //DebugWriteln("Add new OrgInfoID");
                            }
                            // Set STATUS 

                            $Status = 0;

                            $ProjectVerified = TBL_Projects::GetFieldValue($Database, "Verified", $ProjectID);

                            if ($ProjectVerified == true) {
                                $Status = 1;
                            }

                            // ADD POINT
                            //DebugWriteln("OrganismInfoID=$OrganismInfoID");

                            if (($OrganismInfoID > 0) && ($AreaName !== -1)) { //($Presence!==-1) &&
                                //AddPoint($Database,$PersonID,$ProjectID,$RefX,$RefY,$CoordinateSystemID,
                                //      $AreaName="New Sighting",$VisitDate=null,$Present=null,$SubplotID=null,
                                //  $OrganismInfoID=0,$Accuracy=null,$UpdateExisingOrganismData=true,$AreaSubTypeID=null,
                                //    $GeometryString=null,$AreaComments=null,$VisitComments=null,$InsertLogID=null,$Status=null)

                                $OrganismDataID = 0;

                                $OrganismDataID = TBL_OrganismData::AddPoint($Database, $PersonID, $ProjectID, $RefX, $RefY, $CoordinateSystemID, $AreaName, $VisitDate, $Presence, $SubplotID, // SubPlotID
                                                $OrganismInfoID, null, false, $AreaSubTypeID, null, null, $VisitComments, $InsertLogID, $Status);

                                //DebugWriteln("OrganismDataID1=$OrganismDataID");
                                /////////////////////////////////////////////////////////////////////////
                                // METADATA....Insert OrganismData metadata: URL, GUID, Rights, Organization  (NOTE this metadata is not added for site characteristic data yet
                                /////////////////////////////////////////////////////////////////////////

                                if ($OrganismDataID > 0) {  // if an organism was added, insert metadata
                                    if ($URL != null) {
                                        TBL_OrganismData::SetFieldValue($Database, "URL", $OrganismDataID, $URL);
                                    }

                                    if ($Rights != null) {
                                        TBL_OrganismData::SetFieldValue($Database, "Rights", $OrganismDataID, $Rights);
                                    }

                                    if ($GUID != null) {
                                        TBL_OrganismData::SetFieldValue($Database, "GUID", $OrganismDataID, $GUID);
                                    }

                                    if ($OrganizationName != null) {
                                        // Insert Organization name into TBL_Organizations if doesn't exist, and relate the OrganizationID to the OrganismDataID

                                        $OrganizationSet = TBL_Organizations::GetSetFromName($Database, $OrganizationName);

                                        if ($OrganizationSet->FetchRow()) {  //if the organization name already exists
                                            $OrganizationSet = TBL_Organizations::GetSetFromName($Database, $OrganizationName);  //get set again
                                            $OrganizationID = $OrganizationSet->Field("ID");

                                            TBL_OrganismData::SetFieldValue($Database, "Organization", $OrganismDataID, $OrganizationID);
                                        } else {
                                            $OrganizationID = TBL_Organizations::Insert($Database, $OrganizationName);

                                            TBL_OrganismData::SetFieldValue($Database, "Organization", $OrganismDataID, $OrganizationID);
                                        }
                                    }
                                }  // end inserting metadata  

                                $NumRecordsInserted++;

                                if ($JobID != NOT_SPECIFIED) {
                                    TBL_Jobs::UpdateProgress($Database, $JobID, $NumRecordsInserted);
                                }
                            }
                        } else {
                            $OrganismDataID = null;
                        }
                    }
                    //////////////////////////////////////////////////////////////////////////////////////////////
                    // ATTRIBUTES (either organismdata attributes or viti attributes (e.g., site characteristics)//
                    // ////////////////////////////////////////////////////////////////////////////////////////////
                    // if we have an Index varibale name for any given AttriubuteType in the system...
                    // then we have data in the file in a column based on that given index...
                    // so go get the value in that column for each row...

                    $AttributeTypeSet = LKU_AttributeTypes::GetSet($Database);

                    while ($AttributeTypeSet->FetchRow()) {
                        $AttributeTypeID = $AttributeTypeSet->Field("ID");

                        if ($AttributeTypeID != ATTRIBUTE_PRESENCE) { // If NOT presence, add attribute data (15 is presnece on all servers)
                            // create variable names for each one

                            $AttributeTypeName = $AttributeTypeSet->Field("Name");
                            $ValueType = $AttributeTypeSet->Field("ValueType");
                            $AttributeCategoryID = $AttributeTypeSet->Field("AttributeCategoryID");

                            $AttributeTypeNameTrimmed = trim($AttributeTypeName); // remove beginning and trailing whitespace
                            $AttributeTypeNameTrimmed = preg_replace('/\s+/', '', $AttributeTypeNameTrimmed); // remove middle whitespace

                            $AttributeTypeIndex = $AttributeTypeNameTrimmed . "Index";

                            if ($$AttributeTypeIndex !== false) {  // if ($$AttributeTypeIndex)  !!!! Before this edit, attribute in first column is not matched index of [0] 
                                // set variable value to the value in the nth column for this row
                                $Value = trim($IBISValues[$$AttributeTypeIndex]);

                                //DebugWriteln("SciNameIndex****************=$ScientificNameIndex");


                                if ($ScientificNameIndex === false) { // if we did not find a scientificname column
                                    $OrganismDataID = null;

                                    $AreaID = TBL_Visits::GetFieldValue($Database, "AreaID", $VisitID);
                                } else { // go get them from the organismdataid that was added via AddPoint
                                    // get visitid from organismdataid
                                    // DebugWriteln("VisitID=$VisitID,AreaID=$AreaID,OrganismDataID=$OrganismDataID");

                                    if ($OrganismDataID > 0) {
                                        $VisitID = TBL_OrganismData::GetFieldValue($Database, "VisitID", $OrganismDataID);

                                        $AreaID = TBL_Visits::GetFieldValue($Database, "AreaID", $VisitID);
                                    } else {  // Have a SciName value, couldn't get the TSN (ex. Unknown forb) so OrganismDataID=null
                                        // interesting problem here: if we have an organism only file, and a row cannot get the TSN ("unknown forb") the visit gets entered, with no organism info...What to do?

                                        $VisitID = TBL_Visits::InsertVisitOnly($Database, $PersonID, $VisitDate, $RefX, $RefY, $ProjectID, $AreaName, $SubplotID, $CoordinateSystemID, null, NOT_SPECIFIED, INSERT_LOG_FORM, $VisitComments);
                                        //InsertVisitOnly($Database,$UserID,$Date,$X,$Y,$ProjectID,$AreaName,$SubplotID,$CoordinateSystemID,$Accuracy,$FormID=NOT_SPECIFIED,$InsertLogType=INSERT_LOG_FORM,$VisitComments=null)
                                    }
                                }

                                switch ($ValueType) {
                                    case 1: // Lookup
                                        {
                                            // check value in file against associated allowed values in LKU_AttributeValues
                                            $AttributeValueSet = LKU_AttributeValues::GetSetFromType($Database, $AttributeTypeID);

                                            while ($AttributeValueSet->FetchRow()) {
                                                $AttributeValueID = $AttributeValueSet->Field("ID");
                                                $ValueName = $AttributeValueSet->Field("Name");

                                                if ($Value == $ValueName) {
                                                    // add the data to the database

                                                    switch ($AttributeCategoryID) {
                                                        case 1: // Organism Data Attributes
                                                            TBL_AttributeData::Insert($Database, null, $OrganismDataID, null, $AttributeTypeID, $AttributeValueID, $SubplotID, null, null);
                                                            break;
                                                        // Location Attributes
                                                        case 2: // Abiotic (was considered a Visit Attribute)
                                                        case 3: // Soil Chemistry (was considered a Visit Attribute)
                                                        case 4: // Soil Type (was considered a Visit Attribute)
                                                        case 5: // Biotic (was considered a Visit Attribute)
                                                            TBL_AttributeData::Insert($Database, $VisitID, null, null, $AttributeTypeID, $AttributeValueID, $SubplotID, null, null);
                                                            break;
                                                        case 6: // Area Attributes
                                                            TBL_AttributeData::Insert($Database, null, null, null, $AttributeTypeID, $AttributeValueID, $SubplotID, null, $AreaID);
                                                            break;
                                                        default:
                                                            break;
                                                    }
                                                }
                                            }

                                            break;
                                        }

                                    case 2: // float
                                    case 3: // int
                                        {
                                            switch ($AttributeCategoryID) {
                                                case 1: // Organism Data Attributes
                                                    if (($Value !== "") || ($Value !== null)) {
                                                        TBL_AttributeData::Insert($Database, null, $OrganismDataID, null, $AttributeTypeID, $Value, $SubplotID, null, null);
                                                    }
                                                    break;
                                                // Location Attributes (site characteristics)
                                                case 2: // Abiotic (was considered a Visit Attribute)
                                                case 3: // Soil Chemistry (was considered a Visit Attribute)
                                                case 4: // Soil Type (was considered a Visit Attribute)
                                                case 5: // Biotic (was considered a Visit Attribute)
                                                    //DebugWriteln("Value=$Value");
                                                    if (($Value != "") || ($Value != null)) {
                                                        //DebugWriteln("Value==========$Value");
                                                        TBL_AttributeData::Insert($Database, $VisitID, null, null, $AttributeTypeID, $Value, $SubplotID, null, null);
                                                    }
                                                    break;
                                                case 6: // Area Attributes
                                                    if (($Value !== "") || ($Value !== null)) {
                                                        TBL_AttributeData::Insert($Database, null, null, null, $AttributeTypeID, $Value, $SubplotID, null, $AreaID);
                                                    }
                                                    break;
                                                default:
                                                    break;
                                            }
                                            break;
                                        }
                                }
                            }
                        } else {
                            // change code to NEVER add presence attributes twice b/c they were already added abbove via AddPoint();
                        }
                    }
                }
            }
        }

        //TBL_Jobs::SetFieldValue($Database,"CurrentStatus",$JobID,JOBSTATUS_COMPLETED);

        $array = array($NumRecordsInserted, $InsertLogID);
        Return $array;
    }

    public static function Delete($Database, $ProjectID, $DeleteProjectRecord = false) {
    //
    // DeleteProjectRecord is true to delete the projects entry in TBL_Projects
    // as well as all the data, false indicates just deleting the data associated
    // with the project.
    //
	//	$DeleteProjectRecord - true, deletes the entire project record
    //		false, only deletes the visit data associated with the project//
	
        TBL_DBTables::Delete($Database, "TBL_Projects", $ProjectID);

        // we may also need to email all people who had their CurrentProjectID set to the project being deleted to notify them that the project they were working on has been deleted. - gjn
    }

    //******************************************************************************
    // Additional functions
    //******************************************************************************

    public static function GetProjectMembersSetFromID($Database, $ProjectID) {
    //
    //	returns a record set containing the users that have at least one
    //	role on the specified project.//
	
        /*
          $SelectString="SELECT DISTINCT REL_PersonToProject.PersonID, REL_PersonToProject.Role, TBL_People.FirstName, TBL_People.LastName ".
          "FROM TBL_Projects INNER JOIN ".
          "REL_PersonToProject ON TBL_Projects.ID = REL_PersonToProject.ProjectID INNER JOIN ".
          " TBL_People ON REL_PersonToProject.PersonID = TBL_People.ID ".
          "WHERE (TBL_Projects.ID = $ProjectID) AND (REL_PersonToProject.Role IS NOT NULL)";
         */

        $SelectString = "SELECT DISTINCT REL_PersonToProject_1.PersonID, TBL_People.FirstName, TBL_People.LastName, MIN(REL_PersonToProject_1.Role) AS MinimumRole
            FROM TBL_Projects INNER JOIN
            REL_PersonToProject AS REL_PersonToProject_1 ON TBL_Projects.ID = REL_PersonToProject_1.ProjectID INNER JOIN
            TBL_People ON REL_PersonToProject_1.PersonID = TBL_People.ID
            GROUP BY REL_PersonToProject_1.PersonID, TBL_People.FirstName, TBL_People.LastName, TBL_Projects.ID
            HAVING (TBL_Projects.ID = $ProjectID) AND (MIN(REL_PersonToProject_1.Role) IS NOT NULL)
		    ORDER BY TBL_People.FirstName";

        //DebugWriteln("SelectString=$SelectString");

        $ProjectMembersSet = $Database->Execute($SelectString);

        return($ProjectMembersSet);
    }

    public static function GetProjectPersonnelSetFromID($Database, $ProjectID, $Role) {
    //
    // Returns a record set with the project members with a speciific role//
	
        $ProjectID = (int) SafeInt($ProjectID);

        $SelectString = "SELECT *, TBL_Projects.ProjName AS ProjectName, TBL_Projects.ID AS ProjectID, " .
                "REL_PersonToProject.PersonID AS PeopleID, TBL_People.FirstName AS FirstName, " .
                "TBL_People.LastName AS LastName, TBL_People.Email AS Email " .
                "FROM REL_PersonToProject INNER JOIN " .
                "TBL_Projects ON REL_PersonToProject.ProjectID = TBL_Projects.ID INNER JOIN " .
                "TBL_People ON REL_PersonToProject.PersonID = TBL_People.ID " .
                "WHERE (REL_PersonToProject.ProjectID = " . $ProjectID . ") AND " .
                "(REL_PersonToProject.Role=$Role) " .
                "ORDER BY TBL_People.FirstName";

        //DebugWriteln("SelectString=$SelectString");

        $ProjectManagerSet = $Database->Execute($SelectString);

        return($ProjectManagerSet);
    }

    // these functions need to be checked if they are being used and if they should be being used jjg

    public static function GetNameForID($Database, $ProjectID) {
        $ProjectID = SafeInt($ProjectID);

        $ProjectSet = TBL_Projects::GetSetFromID($Database, $ProjectID);

        return($ProjectSet->Field("ProjName"));
    }

    public static function GetSetForPersonID($dbConn, $PersonID, $ProjectID = null, $Role = null) {
    //
    // Return the set of projects this person has the specified role for or is a member of//
	
        $SelectString = "SELECT DISTINCT ProjName,TBL_Projects.ID as ProjectID,TBL_Projects.Status,TBL_Projects.Description,TBL_Projects.PinLatitude,TBL_Projects.PinLongitude " .
                "FROM TBL_Projects ".
                "INNER JOIN REL_PersonToProject ".
                "ON TBL_Projects.ID = REL_PersonToProject.ProjectID ".
                "INNER JOIN REL_WebsiteToProject ".
                "ON TBL_Projects.ID = REL_WebsiteToProject.ProjectID ".
                "WHERE (REL_PersonToProject.PersonID= :PersonID ".
                "AND REL_PersonToProject.ProjectID=TBL_Projects.ID)";
		
	/*	SELECT DISTINCT ProjName,TBL_Projects.ID as ProjectID,TBL_Projects.Status,TBL_Projects.Description,TBL_Projects.PinLatitude,TBL_Projects.PinLongitude 
                FROM TBL_Projects 
                INNER JOIN REL_PersonToProject 
                ON TBL_Projects.ID = REL_PersonToProject.ProjectID 
                INNER JOIN REL_WebsiteToProject 
               ON TBL_Projects.ID = REL_WebsiteToProject.ProjectID 
                WHERE (REL_PersonToProject.PersonID=  8387
                AND REL_PersonToProject.ProjectID=TBL_Projects.ID) OR TBL_Projects.OpenAccess=1*/
        
        if ($ProjectID != null && is_numeric($ProjectID))
            $SelectString.="AND TBL_Projects.ID =:ProjectID ";
        
        if ($Role != null)
            $SelectString.="AND Role=:Role ";
        else
            $SelectString.="AND Role IS NOT NULL ";
        
        $SelectString.="AND REL_WebsiteToProject.WebsiteID =7 ";
        $SelectString.="ORDER BY ProjName";
        //print_r($SelectString);
         $stmt = $dbConn->prepare($SelectString);
        $stmt->bindValue("PersonID", $PersonID);
        if ($Role != null){
            $stmt->bindValue("Role", $Role);
        }
         if ($ProjectID != null && is_numeric($ProjectID)){
             $stmt->bindValue("ProjectID", $ProjectID);
         }
        $stmt->execute();
        $projects = $stmt->fetchAll();
        if (!$projects) {
            return $projects;
        }
        return $projects;

    }
    

    public static function CanEditData($Database, $PersonID, $ProjectID = -1, $VisitID = -1, $ErrorString = null) {
    // 
    //	Returns true if the user can edit the specified Visit or visits within the specified project
    //
	// This follows the following logic:
    //	If the project is not active, it cannot be edited
    //	If the user is an authority, they can edit all visits in the project so it returns true
    //	Otherwise, if the user uploaded the data then they can edit it.
    //
	// Inputs:
    //	Database 
    //	PersonID - person wishing to edit project data
    //	ProjectID - optional (either this or a VisitID must be specified) project to edit data in
    //	VisitID - optional specific visit to try and edit//
	
        //DebugWriteln("PersonID=$PersonID");
        //DebugWriteln("ProjectID=$ProjectID");
        //DebugWriteln("VisitID=$VisitID");
        $ErrorString = null;
        $Result = false;

        if ($PersonID > 0) { // ensure guests not able to edit data
            if ($ProjectID == -1) {
                $Set = TBL_Visits::GetSetFromID($Database, $VisitID);

                $ProjectID = $Set->Field("ProjectID");
            }

            // we now have a project id whether they passed in one or not
            // make sure the project is an active project - GJN

            $ProjectSet = TBL_Projects::GetSetFromID($Database, $ProjectID); // GJN

            $ProjectStatus = $ProjectSet->Field("Active"); // GJN

            if ($ProjectStatus >= 1) { // this is an active project - GJN

                //DebugWriteln("ProjectID=$ProjectID");
                //DebugWriteln("PersonID=$PersonID");
                //DebugWriteln("Role=$Role");

                if (REL_PersonToProject::HasRole($Database, $ProjectID, PROJECT_MANAGER)) {
                    $Result = true;
                } else {

                    //$Result="VisitID=$VisitID"; // for debugging
                    //DebugWriteln("VisitID=$VisitID");
                    if ($VisitID > 0) { // was evaluated using >=0 GJN

                        $VisitSet = TBL_Visits::GetSetFromID($Database, $VisitID);

                        $InsertLogSet = TBL_InsertLogs::GetSetFromID($Database, $VisitSet->Field("InsertLogID"));

                        if ($InsertLogSet->FetchRow()) {
                            //DebugWriteln("VisitID=$VisitID");

                            $UploaderID = $InsertLogSet->Field("UploaderID");
                            //DebugWriteln("TBL_Projects::CanEditData(), UploaderID=$UploaderID, PersonID=$PersonID");
                            //$Result="UploaderID=".$UploaderID." PersonID=$PersonID"; // for debugging

                            if ($UploaderID == $PersonID) {
                                //DebugWriteln("CanEdit");
                                $Result = true;
                            } else {
                                $ErrorString = "Sorry, only authorities or the user who added this data can edit it.";
                            }
                        }
                    } else {
                        $ErrorString = "Sorry, only authorities can edit project data.";
                    }
                }
            } else {
                $ErrorString = "Sorry, this project is not active and cannot be edited";
            }
            //DebugWriteln("TBL_Projects::CanEditData(), ".$ErrorString);
        }

        return($Result);
    }

    //**********************************************************************************
    // Not sure where else to put these
    //**********************************************************************************

    public static function GetShowProjectsFlag($WebsiteID) {
        switch ($WebsiteID) {
            case WEBSITE_NIISS: $ShowProjectsFlag = true;
                break;
            case WEBSITE_TMAP: $ShowProjectsFlag = true;
                break;
            case WEBSITE_IBISADMIN: $ShowProjectsFlag = true;
                break;
            case WEBSITE_GISIN: $ShowProjectsFlag = true;
                break;
            case WEBSITE_AHB: $ShowProjectsFlag = true;
                break;
            case WEBSITE_USGS_RAM: $ShowProjectsFlag = true;
                break;
            case WEBSITE_CITSCI: $ShowProjectsFlag = true;
                break;
            case WEBSITE_GREG: $ShowProjectsFlag = true;
                break;
            case WEBSITE_JIM: $ShowProjectsFlag = true;
                break;
            case WEBSITE_IBIS: $ShowProjectsFlag = true;
                break;
            case WEBSITE_IDSOURCE: $ShowProjectsFlag = false;
                break; // false
            case WEBSITE_SABCC: $ShowProjectsFlag = true;
                break;
            case WEBSITE_CENTROID: $ShowProjectsFlag = false;
                break; // false
            case WEBSITE_COTRAILS: $ShowProjectsFlag = false;
                break; // false
            case WEBSITE_COLORADOVIEW: $ShowProjectsFlag = false;
                break; // false
            case WEBSITE_CFRI: $ShowProjectsFlag = true;
                break;
            case WEBSITE_GLEDN: $ShowProjectsFlag = false;
                break;
            case WEBSITE_FRPP: $ShowProjectsFlag = true;
                break;
            case WEBSITE_MSI: $ShowProjectsFlag = false;
                break;
            case WEBSITE_LEAF: $ShowProjectsFlag = true;
                break;
            case WEBSITE_CERC: $ShowProjectsFlag = true;
                break;
            case WEBSITE_HEADWATERS: $ShowProjectsFlag = true;
                break;
            case WEBSITE_MYTREETRACKER: $ShowProjectsFlag = true;
                break;
        }
        return ($ShowProjectsFlag);
    }

    public static function GetLimitProjectsFlag($WebsiteID) {
        switch ($WebsiteID) {
            case WEBSITE_NIISS: $LimitProjectsFlag = false;
                break;
            case WEBSITE_TMAP: $LimitProjectsFlag = true;
                break;
            case WEBSITE_IBISADMIN: $LimitProjectsFlag = false;
                break;
            case WEBSITE_GISIN: $LimitProjectsFlag = false;
                break;
            case WEBSITE_AHB: $LimitProjectsFlag = true;
                break;
            case WEBSITE_USGS_RAM: $LimitProjectsFlag = false;
                break;
            case WEBSITE_CITSCI: $LimitProjectsFlag = true;
                break;
            case WEBSITE_GREG: $LimitProjectsFlag = true;
                break;
            case WEBSITE_JIM: $LimitProjectsFlag = false;
                break;
            case WEBSITE_IBIS: $LimitProjectsFlag = false;
                break;
            case WEBSITE_IDSOURCE: $LimitProjectsFlag = true;
                break;
            case WEBSITE_SABCC: $LimitProjectsFlag = true;
                break;
            case WEBSITE_CENTROID: $LimitProjectsFlag = true;
                break;
            case WEBSITE_COTRAILS: $LimitProjectsFlag = true;
                break;
            case WEBSITE_COLORADOVIEW: $LimitProjectsFlag = true;
                break;
            case WEBSITE_CFRI: $LimitProjectsFlag = true;
                break;
            case WEBSITE_GLEDN: $LimitProjectsFlag = true;
                break;
            case WEBSITE_FRPP: $LimitProjectsFlag = true;
                break;
            case WEBSITE_MSI: $LimitProjectsFlag = true;
                break;
            case WEBSITE_CERC: $LimitProjectsFlag = true;
                break;
            case WEBSITE_HEADWATERS: $LimitProjectsFlag = true;
                break;
            case WEBSITE_LEAF: $LimitProjectsFlag = true;
                break;
            case WEBSITE_MYTREETRACKER: $LimitProjectsFlag = true;
                break;
        }
        return ($LimitProjectsFlag);
    }

    public static function GetBounds($Database, $ProjectID, $RefX, $RefY, $RefWidth, $RefHeight) {
    // 
    // get the bounding box for the specified project for zooming purposes//
    
        $SelectString = "SELECT MIN(TBL_SpatialLayerData.RefX) AS MinRefX, " .
                "MAX(TBL_SpatialLayerData.RefX + TBL_SpatialLayerData.RefWidth) AS MaxRefX, " .
                "MIN(TBL_SpatialLayerData.RefY + TBL_SpatialLayerData.RefHeight) AS MinRefY, " .
                "MAX(TBL_SpatialLayerData.RefY) AS MaxRefY " .
                "FROM TBL_SpatialLayerData INNER JOIN " .
                "TBL_Areas ON TBL_SpatialLayerData.AreaID = TBL_Areas.ID " .
                "WHERE (TBL_Areas.ProjectID = $ProjectID)";

        $BoundSet = $Database->Execute($SelectString);

        $MinRefX = $BoundSet->Field("MinRefX");
        $MaxRefX = $BoundSet->Field("MaxRefX");
        $MinRefY = $BoundSet->Field("MinRefY");
        $MaxRefY = $BoundSet->Field("MaxRefY");

        $RefX = $MinRefX;
        $RefWidth = $MaxRefX - $MinRefX;
        $RefY = $MaxRefY;
        $RefHeight = $MinRefY - $MaxRefY;
    }

    public static function GetCurrentProjectName($Database) {
        $Text = "No current project";

        $ProjectID = TBL_People::GetCurrentProjectID($Database, GetUserID());

        $ProjectSet = NULL;

        if ($ProjectID >= 0) {
            $ProjectSet = TBL_Projects::GetSetFromID($Database, $ProjectID);
        }

        if ($ProjectSet != null) {
            $Text = $ProjectSet->Field("ProjName");
        }

        return($Text);
    }

    // THESE SHOULD BE REPLACED jjg

    public static function GetAll($Database) { // should use GetSet() - jjg
        $SelectString = "SELECT * " .
                "FROM TBL_Projects " .
                "ORDER BY ProjName";

        $ProjectSet = $Database->Execute($SelectString);

        return($ProjectSet);
    }

    public static function GetNumVisitsForID($Database, $ProjectID) { // probably should be replaced with call to GetNumrows() in TBL_Visits
        $SelectString = "SELECT COUNT(*) AS NumVisits " .
                "FROM TBL_Visits " .
                "WHERE (ProjectID = $ProjectID)";

        $NumVisitSet = $Database->Execute($SelectString);



        return($NumVisitSet);
    }

    public static function GetNumVisits($Database, $ProjectID) { // probably should be replaced with call to GetNumrows() in TBL_Visits
        $SelectString = "SELECT COUNT(*) AS NumVisits " .
                "FROM TBL_Visits " .
                "WHERE (ProjectID = $ProjectID)";

        $NumVisitSet = $Database->Execute($SelectString);

        $NumVisits = $NumVisitSet->Field("NumVisits");

        return($NumVisits);
    }

    public static function GetProjectData($Database, $TheTable, $ProjectID, $TotalRows, $CurrentRow, $NUMROWS, $TabID = null) {
        $UserID = GetUserID();
        $NumRows = (int) $NUMROWS;
        $TotalRows = (int) $TotalRows;
        $CurrentRow = (int) $CurrentRow;
        $DescendingFlag = false;

        // write out the id

        if ($CurrentRow >= $TotalRows) {
            $LastPage = (int) ((($TotalRows + $NumRows - 1) / $NumRows) - 1); // from PageSettings

            $CurrentRow = $LastPage * $NumRows; // go to the last page
        }

        if ($CurrentRow < 0)
            $CurrentRow = 0;

        $SelectString1 = "SELECT TOP " . ($TotalRows - $CurrentRow) . " ID FROM TBL_Projects_GetProjectData($ProjectID)";


        TBL_DBTables::AddOrderByClause($SelectString1, "ID", !$DescendingFlag); // query the rows in the opposite order of what the user wants
        //$Set=$Database->Execute($SelectString);
        /*
          $SelectString="SELECT TOP ".($TotalRows-$CurrentRow)." ProjectID, AreaName, Latitude, Longitude, VisitDate, Species, AttributeName, FloatValue, IntValue, LookupName, VisitID
          FROM VIEW_SiteCharacteristicData
          WHERE ProjectID=$ProjectID
          UNION
          SELECT TOP ".($TotalRows-$CurrentRow)." ProjectID, AreaName, Latitude, Longitude, VisitDate, Species, AttributeName, FloatValue, IntValue, LookupName, VisitID
          FROM VIEW_OrganismAttributeData
          WHERE ProjectID=$ProjectID ORDER BY VisitDate" ;
         */

        $SelectString = TBL_DBTables::GetSelectClause(0, $NumRows, null);

        $SelectString.="FROM TBL_Projects_GetProjectData($ProjectID) " .
                "WHERE ID IN ($SelectString1) ";

        $SelectString.="ORDER BY ID ";

        if ($DescendingFlag)
            $SelectString.="DESC "; // can't use order by function, finds previous order by

            
//DebugWriteln("$SelectString");

        $Set = $Database->Execute($SelectString);

        return($Set);
    }

    public static function GetProjectTotalRows($Database, $TheTable, $ProjectID) {
        $UserID = GetUserID();

        // write out the id

        $SelectString1 = "SELECT Count(*) FROM VIEW_SiteCharacteristicData  WHERE ProjectID=$ProjectID";
        $SelectString2 = "SELECT Count(*) FROM VIEW_OrganismAttributeData  WHERE ProjectID=$ProjectID ";

        $Set1 = $Database->Execute($SelectString1);
        $Set2 = $Database->Execute($SelectString2);

        $count1 = $Set1->Field(1);
        $count2 = $Set2->Field(1);
        $count = $count1 + $count2;


        return($count);
    }

}

?>