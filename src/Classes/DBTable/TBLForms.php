<?php

namespace Classes\DBTable;

//**************************************************************************************
// FileName: TBL_Forms.php
// Author: GJN
// Owner: GJN
// Notes: This static class interacts with the database table: TBL_Forms
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
//require_once("C:/Inetpub/wwwroot/Classes/DBTable/TBL_DBTables.php");
//require_once("C:/Inetpub/wwwroot/cwis438/Classes/DBTable/TBL_FormEntries.php");
//require_once("C:/Inetpub/wwwroot/utilities/ValidUtil.php");
//*********************************************************************************
//	Definitions
//*********************************************************************************

use Classes\DBTable\LKUUnits;
use Classes\DBTable\TBLOrganismInfos;
use Classes\DBTable\RELAreaToForm;

class TBLForms {
    public static function GetSetFromID($dbConn, $FormID) {
        $SelectString = "SELECT * FROM \"TBL_Forms\" " .
                "WHERE \"ID\" = :FormID";

        $selectStmt = $dbConn->prepare($SelectString);
        $selectStmt->bindValue("FormID", $FormID);
        $selectStmt->execute();
        $FormSet = $selectStmt->fetch();
        return($FormSet);
    }

    public static function GetSetFromProjectID($Database, $ProjectID) {
        $SelectString = "SELECT * FROM \"TBL_Forms\" WHERE \"ProjectID\" = :projectID";
        $selectStmt = $Database->prepare($SelectString);
        $selectStmt->bindValue("projectID", $ProjectID);
        $selectStmt->execute();
        $FormSet = $selectStmt->fetch();
        return($FormSet);
    }

    //******************************************************************************
    // Additional Functions
    //******************************************************************************

    public static function GetSetFormEntriesProjectID($dbConn, $ProjectID) {


        /*$OrganismSelectString = "SELECT TBL_Forms.ID AS FormID, TBL_Forms.Name, TBL_Forms.AreaSubTypeID, TBL_FormEntries.AttributeTypeID, TBL_FormEntries.ParentFormEntryID,
                         TBL_FormEntries.HowSpecified, TBL_FormEntries.OrderNumber, TBL_FormEntries.UnitID, TBL_FormEntries.SubPlotTypeID, TBL_FormEntries.OrganismInfoID, TBL_FormEntries.Picklist,
                         TBL_FormEntries.AttributeValueID, TBL_FormEntries.ControlAgentID, TBL_FormEntries.AllOrganismPicklist, TBL_FormEntries.SortBy, TBL_FormEntries.ID AS EntryID, TBL_Forms.LocationDefinition,
                         TBL_FormEntries.AllOrganismPicklist
                         FROM TBL_Forms RIGHT OUTER JOIN
                         TBL_FormEntries ON TBL_Forms.ID = TBL_FormEntries.FormID
                         WHERE (TBL_Forms.ProjectID = :ProjectID) AND ((TBL_FormEntries.ParentFormEntryID IS NULL) AND (TBL_FormEntries.OrganismInfoID IS NOT NULL) OR
							(TBL_FormEntries.ParentFormEntryID IS NULL) AND (TBL_FormEntries.Picklist = 1) OR
							(TBL_FormEntries.ParentFormEntryID IS NULL) AND (TBL_FormEntries.AllOrganismPicklist = 1))
                         ORDER BY TBL_Forms.ID ";*/

		// bioblitz is blocked from being sent to apps with this query
		/*$OrganismSelectString = "SELECT TBL_Forms.ID AS FormID, TBL_Forms.Name, TBL_Forms.AreaSubTypeID, TBL_FormEntries.AttributeTypeID, TBL_FormEntries.ParentFormEntryID,
                         TBL_FormEntries.HowSpecified, TBL_FormEntries.OrderNumber, TBL_FormEntries.UnitID, TBL_FormEntries.SubPlotTypeID, TBL_FormEntries.OrganismInfoID, TBL_FormEntries.Picklist,
                         TBL_FormEntries.AttributeValueID, TBL_FormEntries.ControlAgentID, TBL_FormEntries.SortBy, TBL_FormEntries.ID AS EntryID, TBL_Forms.LocationDefinition
                         FROM TBL_Forms RIGHT OUTER JOIN
                         TBL_FormEntries ON TBL_Forms.ID = TBL_FormEntries.FormID
                         WHERE (TBL_Forms.ProjectID = :ProjectID) AND ((TBL_FormEntries.ParentFormEntryID IS NULL) AND (TBL_FormEntries.OrganismInfoID IS NOT NULL) OR
							(TBL_FormEntries.ParentFormEntryID IS NULL) AND (TBL_FormEntries.Picklist = 1))
                         ORDER BY TBL_Forms.ID"; */

		$OrganismSelectString = "SELECT \"TBL_Forms\".\"ID\" AS \"FormID\", \"TBL_Forms\".\"Name\", \"TBL_Forms\".\"AreaSubTypeID\", \"TBL_FormEntries\".\"AttributeTypeID\", \"TBL_FormEntries\".\"ParentFormEntryID\",
                         \"TBL_FormEntries\".\"HowSpecified\", \"TBL_FormEntries\".\"OrderNumber\", \"TBL_FormEntries\".\"UnitID\", \"TBL_FormEntries\".\"SubPlotTypeID\", \"TBL_FormEntries\".\"OrganismInfoID\", \"TBL_FormEntries\".\"Picklist\",
                         \"TBL_FormEntries\".\"AttributeValueID\", \"TBL_FormEntries\".\"ControlAgentID\", \"TBL_FormEntries\".\"SortBy\", \"TBL_FormEntries\".\"ID\" AS \"EntryID\", \"TBL_Forms\".\"LocationDefinition\"
                         FROM \"TBL_Forms\" RIGHT OUTER JOIN
                         \"TBL_FormEntries\" ON \"TBL_Forms\".\"ID\" = \"TBL_FormEntries\".\"FormID\"
                         WHERE (\"TBL_Forms\".\"ProjectID\" = :ProjectID)
                         ORDER BY \"TBL_Forms\".\"ID\"";   // edit made 2/24/17

		//AND ((TBL_FormEntries.ParentFormEntryID IS NULL) AND (TBL_FormEntries.OrganismInfoID IS NOT NULL) OR (TBL_FormEntries.ParentFormEntryID IS NULL) AND (TBL_FormEntries.Picklist = 1))

        $organismStmt = $dbConn->prepare($OrganismSelectString);
        $organismStmt->bindValue("ProjectID", $ProjectID);
        $organismStmt->execute();
        $data = array();

		//$LogFile="C:/Logs/_InvalidAppLog.log";

        while ($OrganismFormEntry = $organismStmt->fetch())
		{
            $FormID = $OrganismFormEntry["FormID"];
			$ParentFormEntryID = $OrganismFormEntry["ParentFormEntryID"];
			$OrganismInfoID = $OrganismFormEntry["OrganismInfoID"];
			$Picklist = $OrganismFormEntry["Picklist"];

			// Validate the form

			$checkArray = TBLForms::validateDataEntryForm($dbConn, $FormID);
            $dataSheetValid = true;
            if ($checkArray[0] != "No Errors")
			{
                $dataSheetValid = false;
            }

			if ($dataSheetValid==true)
			{
				//file_put_contents($LogFile,"FormID=$FormID) HasAttributes=$checkArray[0]\r\n",FILE_APPEND);

				//Add common form details and site characteristics once to return output
				if (!key_exists($FormID, $data)) {
					$data[$FormID] = array("DataSheetID" => $OrganismFormEntry["FormID"], "Name" => $OrganismFormEntry["Name"], "AreaSubTypeID" => $OrganismFormEntry["AreaSubTypeID"], "Predefined" => $OrganismFormEntry["LocationDefinition"]);
					if ($OrganismFormEntry["LocationDefinition"] == "1") {
						$locations = RELAreaToForm::GetSet($dbConn, 0, $FormID);
						$data[$FormID]["locations"] = $locations;
					}
					$data[$FormID]["SiteChar"] = array();
					$data[$FormID]["OrgAttributes"] = array();

					$SiteCharSelectString = "SELECT \"TBL_Forms\".\"ID\" AS \"FormID\", \"TBL_Forms\".\"Name\", \"TBL_Forms\".\"AreaSubTypeID\", \"TBL_FormEntries\".\"AttributeTypeID\", \"TBL_FormEntries\".\"ParentFormEntryID\",
							 \"TBL_FormEntries\".\"HowSpecified\", \"TBL_FormEntries\".\"OrderNumber\", \"TBL_FormEntries\".\"UnitID\", \"TBL_FormEntries\".\"SubPlotTypeID\", \"TBL_FormEntries\".\"OrganismInfoID\", \"TBL_FormEntries\".\"Picklist\",
							 \"TBL_FormEntries\".\"AttributeValueID\", \"TBL_FormEntries\".\"ControlAgentID\", \"TBL_FormEntries\".\"SortBy\", \"TBL_FormEntries\".\"ID\" AS \"EntryID\", \"TBL_Forms\".\"LocationDefinition\",
							 \"TBL_FormEntries\".\"AllOrganismPicklist\"
							 FROM \"TBL_Forms\" RIGHT OUTER JOIN
							 \"TBL_FormEntries\" ON \"TBL_Forms\".\"ID\" = \"TBL_FormEntries\".\"FormID\"
							 WHERE (\"TBL_Forms\".\"ProjectID\" = :ProjectID) and (\"TBL_Forms\".\"ID\" = :FormID)
							 AND (\"ParentFormEntryID\" IS NULL) AND (\"OrganismInfoID\" IS NULL) AND (\"ControlAgentID\" IS NULL) AND (\"Picklist\" IS NULL) AND (\"AllOrganismPicklist\" IS NULL)               
							 ORDER BY \"TBL_FormEntries\".\"OrderNumber\" ";
					$SiteCharStmt = $dbConn->prepare($SiteCharSelectString);
					$SiteCharStmt->bindValue("ProjectID", $ProjectID);
					$SiteCharStmt->bindValue("FormID", $FormID);
					$SiteCharStmt->execute();
					while ($SiteCharFormEntry = $SiteCharStmt->fetch()) {
						$data[$FormID]["SiteChar"] [] = TBLForms::getEntryDetails($dbConn, $SiteCharFormEntry);
					}
				}

				if((($ParentFormEntryID===NULL) && ($OrganismInfoID!=NULL)) || (($ParentFormEntryID===NULL) && ($Picklist==1)))  // edit made 2/24/17
				{
					//add organism entry details to form output
					$data[$FormID]["OrgAttributes"][] = TBLForms::getEntryDetails($dbConn, $OrganismFormEntry);

					$OrganismEntryID = $OrganismFormEntry["EntryID"];
					/*$SelectString = "SELECT TBL_Forms.ID AS FormID, TBL_Forms.Name, TBL_Forms.AreaSubTypeID, TBL_FormEntries.AttributeTypeID, TBL_FormEntries.ParentFormEntryID,
								 TBL_FormEntries.HowSpecified, TBL_FormEntries.OrderNumber, TBL_FormEntries.UnitID, TBL_FormEntries.SubPlotTypeID, TBL_FormEntries.OrganismInfoID, TBL_FormEntries.Picklist,
								 TBL_FormEntries.AttributeValueID, TBL_FormEntries.ControlAgentID, TBL_FormEntries.AllOrganismPicklist, TBL_FormEntries.SortBy, TBL_FormEntries.ID AS EntryID, TBL_Forms.LocationDefinition,
								 TBL_FormEntries.AllOrganismPicklist
								 FROM TBL_Forms RIGHT OUTER JOIN
								 TBL_FormEntries ON TBL_Forms.ID = TBL_FormEntries.FormID
								 WHERE (TBL_Forms.ProjectID = :ProjectID) and (TBL_FormEntries.ParentFormEntryID = :FormEntryID)
								 ORDER BY TBL_FormEntries.OrderNumber ";*/
					$SelectString = "SELECT \"TBL_Forms\".\"ID\" AS \"FormID\", \"TBL_Forms\".\"Name\", \"TBL_Forms\".\"AreaSubTypeID\", \"TBL_FormEntries\".\"AttributeTypeID\", \"TBL_FormEntries\".\"ParentFormEntryID\",
								 \"TBL_FormEntries\".\"HowSpecified\", \"TBL_FormEntries\".\"OrderNumber\", \"TBL_FormEntries\".\"UnitID\", \"TBL_FormEntries\".\"SubPlotTypeID\", \"TBL_FormEntries\".\"OrganismInfoID\", \"TBL_FormEntries\".\"Picklist\",
								 \"TBL_FormEntries\".\"AttributeValueID\", \"TBL_FormEntries\".\"ControlAgentID\", \"TBL_FormEntries\".\"SortBy\", \"TBL_FormEntries\".\"ID\" AS \"EntryID\", \"TBL_Forms\".\"LocationDefinition\"
								 FROM \"TBL_Forms\" RIGHT OUTER JOIN
								 \"TBL_FormEntries\" ON \"TBL_Forms\".\"ID\" = \"TBL_FormEntries\".\"FormID\"
								 WHERE (\"TBL_Forms\".\"ProjectID\" = :ProjectID) and (\"TBL_FormEntries\".\"ParentFormEntryID\" = :FormEntryID)
								 ORDER BY \"TBL_FormEntries\".\"OrderNumber\" ";

					$stmt = $dbConn->prepare($SelectString);
					$stmt->bindValue("ProjectID", $ProjectID);
					$stmt->bindValue("FormEntryID", $OrganismEntryID);
					$stmt->execute();

					while ($FormEntry = $stmt->fetch()) {
						//Add the attributes for the organism into output array
						$data[$FormID]["OrgAttributes"][] = TBLForms::getEntryDetails($dbConn, $FormEntry);
					}
				}
			}
            //if (TBLForms::is_empty($FormEntry["ParentFormEntryID"]) && TBLForms::is_empty($FormEntry["OrganismInfoID"]) && TBLForms::is_empty($FormEntry["ControlAgentID"]) && TBLForms::is_empty($FormEntry["Picklist"]) && TBLForms::is_empty($FormEntry["AllOrganismPicklist"])) {
            //    $data[$FormID]["SiteChar"] [] = $tempAttrArray;
            //}
        }

        $returnData = array();
        foreach ($data as $key => $value) {
            $returnData[] = $value;
        }
        return $returnData;
    }

	public static function validateDataEntryForm($dbConn, $FormID)
	{
        $ResultArray = array();
        if ($FormID > 0)
		{

            // *********************************************************************************
            // (1) loop through all organismformentries for this form
            // *********************************************************************************
            $FormSet = TBLForms::GetSetFromID($dbConn,$FormID);
            $FormName = $FormSet["Name"];

			//file_put_contents($LogFile,"Form=$FormName(ID=$FormID)\r\n",FILE_APPEND);

            //$Set = TBLForms::GetOrganismFormEntries($dbConn, $FormID);
			$SelectString="SELECT \"ID\", \"FormID\", \"AttributeTypeID\", \"Name\", \"HowSpecified\", \"ParentFormEntryID\", \"OrderNumber\", \"UnitID\", \"SubPlotTypeID\", \"OrganismInfoID\",
                \"AttributeValueID\", \"Picklist\", \"AllOrganismPicklist\"
            FROM \"TBL_FormEntries\"
            WHERE (\"FormID\" = $FormID) AND (\"ParentFormEntryID\" IS NULL) AND (\"OrganismInfoID\" IS NOT NULL) OR
                (\"FormID\" = $FormID) AND (\"ParentFormEntryID\" IS NULL) AND (\"Picklist\" = 1) OR
				(\"FormID\" = $FormID) AND (\"ParentFormEntryID\" IS NULL) AND (\"AllOrganismPicklist\" = 1)";

			$selectStmt = $dbConn->prepare($SelectString);
			$selectStmt->execute();
			//$Set = $selectStmt->fetch();

            $HasAttributes = "";
            $ErrNo=1;
            if($FormName==null||$FormName=="")
			{
                $HasAttributes=$ErrNo.". Datasheet should have a name. \n";
                $ErrNo++;
            }

            while ($Set = $selectStmt->fetch())
			{
				// for each organism form entry...
                $ParentFormEntryID = $Set["ID"];
                $IsPredefinedPicklist = $Set["Picklist"];
				$IsAllOrgPicklist = $Set["AllOrganismPicklist"];

				//file_put_contents($LogFile,"IN LOOP ParentFormEntryID=$ParentFormEntryID\r\n",FILE_APPEND);

                //$Set2 = TBLForms::GetFormEntriesFromParentFormEntryID($dbConn, $ParentFormEntryID);

				$SelectString2="SELECT \"TBL_FormEntries\".\"ID\", \"TBL_FormEntries\".\"FormID\", \"TBL_FormEntries\".\"ParentFormEntryID\", \"TBL_FormEntries\".\"OrganismInfoID\", \"TBL_FormEntries\".\"AttributeTypeID\",
                      \"LKU_AttributeTypes\".\"Name\" AS \"AttributeTypeName\", \"TBL_FormEntries\".\"AttributeValueID\", \"TBL_FormEntries\".\"UnitID\"
                FROM \"TBL_FormEntries\" INNER JOIN
                      \"LKU_AttributeTypes\" ON \"TBL_FormEntries\".\"AttributeTypeID\" = \"LKU_AttributeTypes\".\"ID\"
                WHERE (\"TBL_FormEntries\".\"ParentFormEntryID\" = $ParentFormEntryID)";

				$selectStmt2 = $dbConn->prepare($SelectString2);
				$selectStmt2->execute();

                $test_array = $selectStmt2->fetch();
                if (!is_array($test_array) || count($test_array) < 1) {
                    $HasAttributes .= $ErrNo.".";
                    if (($IsPredefinedPicklist == 1)||($IsAllOrgPicklist == 1)) {
                        $HasAttributes .= " Picklist Has";
                    }
                    $HasAttributes .= " No Attribute Selected \n";
                    $ErrNo++;
                }
            }
            if ($HasAttributes == "") {
                $HasAttributes = "No Errors";
            }

            array_push($ResultArray, "$HasAttributes");

        } else { // no FormID
            //do nothing or send error saying no formid...
        }
        return $ResultArray;
    }

    private static function getEntryDetails($dbConn, $FormEntry) {
        $HowSpecifiedString = TBLForms::getHowSpecifiedString($FormEntry["HowSpecified"]);
        $tempAttrArray = array("ID" => $FormEntry["EntryID"], "AttributeTypeID" => $FormEntry["AttributeTypeID"], "AttributeValueID" => $FormEntry["AttributeValueID"], "ParentFormEntryID" => $FormEntry["ParentFormEntryID"], "HowSpecified" => $FormEntry["HowSpecified"], "HowSpecifiedString" => $HowSpecifiedString, "OrderNumber" => $FormEntry["OrderNumber"]
            , "UnitID" => $FormEntry["UnitID"], "SubplotTypeID" => $FormEntry["SubPlotTypeID"], "OrganismInfoID" => $FormEntry["OrganismInfoID"], "Picklist" => $FormEntry["Picklist"]
            , "AttributeCategoryID" => NULL, "MinimumValue" => NULL, "MaximumValue" => NULL, "Description" => NULL, "Name" => "");

        if (is_numeric($FormEntry["UnitID"])) {
            $UnitSet = LKUUnits::GetSetFromID($dbConn, $FormEntry["UnitID"]);
            $tempAttrArray["UnitDetails"] = array("Name1" => "GOt", "Name" => $UnitSet["Name"], "Abbreviation" => $UnitSet["Abbreviation"]);
        }

        if (is_numeric($FormEntry["OrganismInfoID"])) {
            $SciName = TBLOrganismInfos::GetName($dbConn, $FormEntry["OrganismInfoID"]);
            $tempAttrArray["OrganismDetails"] = array("Name" => $SciName);
        }

        $tempAttrArray["OrganismType"] = "0";

        if ($FormEntry["Picklist"] === true) {
            $tempAttrArray["OrganismType"] = "1";
        }
		/*else if (is_numeric($FormEntry["AllOrganismPicklist"]) && $FormEntry["AllOrganismPicklist"] == "1") {
            $tempAttrArray["OrganismType"] = "2";
        }*/

        $tempAttrArray["ValueType"] = null;
        if (is_numeric($FormEntry["AttributeTypeID"])) {

            $AttributeType = LKUAttributeTypes::GetSetFromID($dbConn, $FormEntry["AttributeTypeID"]);
            $tempAttrArray["MinimumValue"] = $AttributeType["MinimumValue"];
            $tempAttrArray["MaximumValue"] = $AttributeType["MaximumValue"];
            $tempAttrArray["Name"] = $AttributeType["Name"];
            $tempAttrArray["Description"] = $AttributeType["Description"];
            $tempAttrArray["AttributeCategoryID"] = $AttributeType["AttributeCategoryID"];
            $tempAttrArray["ValueType"] = $AttributeType["ValueType"];
            $tempAttrArray["UnitTypeID"] = $AttributeType["UnitTypeID"];
            if (is_numeric($AttributeType["UnitTypeID"])) {
                $UnitTypeSet = LKUUnitTypes::GetSetFromID($dbConn, $AttributeType["UnitTypeID"]);
                $tempAttrArray["UnitTypeDetails"] = array("Name" => $UnitTypeSet["Name"], "Description" => $UnitTypeSet["Description"]);
            }
            if (is_numeric($FormEntry["UnitID"])) {
                $UnitSet = LKUUnits::GetSetFromID($dbConn, $FormEntry["UnitID"]);
                $tempAttrArray["UnitDetails"] = array("Name" => $UnitSet["Name"], "Abbreviation" => $UnitSet["Abbreviation"]);
            }
            if (is_numeric($AttributeType["ValueType"]) && $AttributeType["ValueType"] == "1") {
                $AttributeValueSet = LKUAttributeValues::GetSetFromType($dbConn, $FormEntry["AttributeTypeID"]);
                $tempAttrArray["AttributeValuesPossible"] = $AttributeValueSet;
            }
        }
        if ($FormEntry["Picklist"] === true) {
            $OrganismSet = RELOrganismInfoToFormEntry::GetPickListSetFromFormEntryID($dbConn, $FormEntry["EntryID"], $FormEntry["SortBy"]);
            $tempAttrArray["OrganismList"] = $OrganismSet;
        }
        return $tempAttrArray;
    }

    private static function getHowSpecifiedString($input) {
        $output = "";
        switch ($input) {
            case "1":
                $output = "Entered";
                break;
            case "2":
                $output = "List";
                break;
            case "3":
                $output = "Specified";
                break;
            case "4":
                $output = "Hidden";
                break;
        }
        return $output;
    }
}

?>
