<?php

namespace API\Classes\WebServices;

//**************************************************************************************
// FileName: GetProjectTreeData_Scalable.php
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
// Author: gjn
// Owner: gjn
// This page returns spatial data as JSON data within specified Bounds, Project, TreeID
//**************************************************************************************
//**************************************************************************************
// Includes
//**************************************************************************************

use Classes\DBTable\TBLForms;

class TreeDataAPI {

    public static function getTreeData($Database, $WebSiteID = 7, $ProjectID = 0, $TreeID = 0, $ZoomLevel = 0, $Bounds) {

        if ($Bounds == null || $Bounds == "" || $Bounds == "undefined") {
            $Bounds = "((-90.0, -180.0), (90.0, 180.0))"; // default that makes sense (world)
        }
        $BoundsTrimmed = str_replace(array("(", ")", " "), "", $Bounds);
        $BoundsTrimmedExploded = explode(",", $BoundsTrimmed);

        $X1 = $BoundsTrimmedExploded[0];
        $Y1 = $BoundsTrimmedExploded[1];

        $X2 = $BoundsTrimmedExploded[2];
        $Y2 = $BoundsTrimmedExploded[3];

        $BoundsString = "$Y1,$X2,$Y2,$X2,$Y2,$X1,$Y1,$X1";


        //$UDFString = $BoundsString . ", $ProjectID";


        $NumRecordsWithinBoundsQuery = "SELECT COUNT(*) AS NumRecords, TBL_OrganismData.OrganismInfoID
                                        FROM TBL_OrganismData INNER JOIN
                                        TBL_Visits ON TBL_OrganismData.VisitID = TBL_Visits.ID INNER JOIN
                                        TBL_SpatialLayerData INNER JOIN
                                        TBL_Areas ON TBL_SpatialLayerData.AreaID = TBL_Areas.ID ON TBL_Visits.AreaID = TBL_Areas.ID
                                        WHERE (TBL_Areas.ProjectID = :ProjectID) AND (TBL_SpatialLayerData.RefX IS NOT NULL) 
                                        AND (TBL_SpatialLayerData.RefY IS NOT NULL) 
                                        AND (TBL_SpatialLayerData.RefX > :Y1)
                                        AND (TBL_SpatialLayerData.RefX < :Y2) 
                                        AND (TBL_SpatialLayerData.RefY > :X1) 
                                        AND (TBL_SpatialLayerData.RefY < :X2)
                                        GROUP BY TBL_OrganismData.OrganismInfoID";
                                        //HAVING (TBL_OrganismData.OrganismInfoID = :treeID)"; // treeid
       
        
        $stmt = $Database->prepare($NumRecordsWithinBoundsQuery);
        $stmt->bindValue("ProjectID", $ProjectID);
        $stmt->bindValue("Y1", $Y1);
        $stmt->bindValue("Y2", $Y2);
        $stmt->bindValue("X1", $X1);
        $stmt->bindValue("X2", $X2);
        //$stmt->bindValue("treeID", $TreeID);
        $stmt->execute();
        $NumRecordsWithinBoundsSet = $stmt->fetch();
        $NumRecordsWithinBounds = $NumRecordsWithinBoundsSet["NumRecords"];
        //print_r($NumRecordsWithinBoundsQuery);
        // print_r($NumRecordsWithinBounds);
        $RealDataFlag = false;
        $RoundFactor = 2;

        switch ($NumRecordsWithinBounds) {
            case ($NumRecordsWithinBounds < 10000): // somehow call GetProjectData(); do the same as gerprojectdata, same query, no rounding...
                

                $SelectString = "SELECT TBL_SpatialLayerData.RefX, TBL_SpatialLayerData.RefY, TBL_SpatialLayerData.GeometryData, TBL_SpatialLayerData.AreaID, TBL_Areas.AreaName, TBL_Areas.ID AS AreaID, TBL_Projects.ID,
                                 TBL_Visits.ID AS VisitID, TBL_OrganismInfos.Name AS CommonName, STUFF(COALESCE (' ' + TBL_TaxonUnits.UnitName1, '')
                                 + COALESCE (' ' + TBL_TaxonUnits.UnitName2, '') + COALESCE (' ' + TBL_TaxonUnits.UnitName3, ''), 1, 1, '') AS SciName, TBL_AttributeData.FloatValue AS DBH, 
                                 TBL_OrganismInfos.ID AS OrgInfoID
                                 FROM TBL_AttributeData RIGHT OUTER JOIN
                                 TBL_OrganismData ON TBL_AttributeData.OrganismDataID = TBL_OrganismData.ID LEFT OUTER JOIN
                                 TBL_TaxonUnits INNER JOIN
                                 REL_OrganismInfoToTSN ON TBL_TaxonUnits.TSN = REL_OrganismInfoToTSN.TSN RIGHT OUTER JOIN
                                 TBL_OrganismInfos ON REL_OrganismInfoToTSN.OrganismInfoID = TBL_OrganismInfos.ID ON
                                TBL_OrganismData.OrganismInfoID = TBL_OrganismInfos.ID RIGHT OUTER JOIN
                                TBL_SpatialLayerData INNER JOIN
                                TBL_Areas ON TBL_SpatialLayerData.AreaID = TBL_Areas.ID INNER JOIN
                                TBL_Projects ON TBL_Areas.ProjectID = TBL_Projects.ID INNER JOIN
                                TBL_Visits ON TBL_Areas.ID = TBL_Visits.AreaID ON TBL_OrganismData.VisitID = TBL_Visits.ID
                                WHERE     (TBL_Projects.ID = $ProjectID) AND (TBL_SpatialLayerData.RefX IS NOT NULL) AND (TBL_SpatialLayerData.RefY IS NOT NULL) AND (TBL_SpatialLayerData.RefX > $Y1)
                                AND (TBL_SpatialLayerData.RefX < $Y2) AND (TBL_SpatialLayerData.RefY > $X1) AND (TBL_SpatialLayerData.RefY < $X2) AND (TBL_AttributeData.AttributeTypeID = 86)";
                                //AND (TBL_OrganismData.OrganismInfoID = $TreeID)";
                 
                $RealDataFlag = true;
                break;
            case ($NumRecordsWithinBounds < 20000):
                $RoundFactor = 2;
                break;
            case ($NumRecordsWithinBounds < 40000):
                $RoundFactor = 1;
                break;
            case ($NumRecordsWithinBounds < 60000):
                $RoundFactor = 1;
                break;
            case ($NumRecordsWithinBounds < 80000):
                $RoundFactor = 1;
                break;
            case ($NumRecordsWithinBounds < 100000):
                $RoundFactor = 0;
                break;
            default:
                $RoundFactor = 0;
        }

        if ($RealDataFlag == false) {


            $SelectString = "SELECT DISTINCT
                                ROUND(TBL_SpatialLayerData.RefX, $RoundFactor) AS RefX, ROUND(TBL_SpatialLayerData.RefY, $RoundFactor) AS RefY, TBL_Visits.ProjectID,
                                MIN(TBL_SpatialLayerData.ID) AS MinID, TBL_OrganismData.OrganismInfoID
                                FROM TBL_Visits INNER JOIN                                                                      
                                TBL_SpatialLayerData ON TBL_Visits.AreaID = TBL_SpatialLayerData.AreaID INNER JOIN
                                TBL_OrganismData ON TBL_Visits.ID = TBL_OrganismData.VisitID
                                GROUP BY ROUND(TBL_SpatialLayerData.RefX,$RoundFactor), ROUND(TBL_SpatialLayerData.RefY,$RoundFactor), TBL_Visits.ProjectID
                                HAVING (ROUND(TBL_SpatialLayerData.RefX,$RoundFactor) IS NOT NULL) AND (ROUND(TBL_SpatialLayerData.RefY,$RoundFactor) IS NOT NULL) AND 
                                (ROUND(TBL_SpatialLayerData.RefX,$RoundFactor) > $Y1) AND (ROUND(TBL_SpatialLayerData.RefX,$RoundFactor) < $Y2) AND 
                                (ROUND(TBL_SpatialLayerData.RefY,$RoundFactor) > $X1) AND (ROUND(TBL_SpatialLayerData.RefY,$RoundFactor) < $X2)
                                AND (TBL_Visits.ProjectID = $ProjectID) AND (TBL_OrganismData.OrganismInfoID = $TreeID)";
        }

        $selectStmt = $Database->prepare($SelectString);
        $selectStmt->execute();
        $ProjectDataSet = $selectStmt->fetch();
       
        $i = 0;
        $DataArray = array();
        
        while ($ProjectDataSet) {
            $Latitude = $ProjectDataSet["RefY"];
            $Longitude = $ProjectDataSet["RefX"];

            if ($RoundFactor <= 2) { // if we have rounded the data to some degree, then round the output json; if RealDataFlag==false
                $Latitude = round($Latitude, $RoundFactor);
                $Longitude = round($Longitude, $RoundFactor);
            }
            $detailsArray = array();
            if ($RealDataFlag) {


                $AreaID = $ProjectDataSet["AreaID"];
                $VisitID = $ProjectDataSet["VisitID"];

                $FormSet = TBLForms::GetSetFromProjectID($Database, $ProjectID);
                $FormID = $FormSet["ID"];

                $AreaName = $ProjectDataSet["AreaName"];
                $Latitude = $ProjectDataSet["RefY"];
                $Longitude = $ProjectDataSet["RefX"];
                //$Accuracy=(float)$AreaSet["Uncertainty");
                //$ProjName=$AreaSet["ProjName");
                //$ProjNameLimited=WordLimiter($ProjName,2);

                $DisplayLatitude = round($Latitude, 4);
                $DisplayLongitude = round($Longitude, 4);

                $CommonName = $ProjectDataSet["CommonName"];
                $SciName = $ProjectDataSet["SciName"];
                $DBH = $ProjectDataSet["DBH"];
                $DBH = number_format($DBH, 1);

                //$EditLink="/cwis438/websites/MyTreeTracker/Tree_Edit.php?AreaID=$AreaID&WebSiteID=$WebSiteID";
                $ReVisitLink = "/cwis438/websites/MyTreeTracker/ReVisitTree_Form.php?ProjectID=$ProjectID&FormID=$FormID&AreaID=$AreaID&VisitID=$VisitID&WebSiteID=$WebSiteID";
                $DetailsLink = "/cwis438/websites/MyTreeTracker/Tree_Info.php?AreaID=$AreaID&WebSiteID=$WebSiteID";


                $detailsArray["CommonName"]=$CommonName;
                $detailsArray["ScientificName"]=$SciName;
                $detailsArray["DBH"]=$DBH;
                $detailsArray["AreaName"]=$AreaName;
                $detailsArray["ReVisitLink"]=$ReVisitLink;
                $detailsArray["DetailsLink"]=$DetailsLink;
                
            } 

            $CoordinatesArray = array("Y" => $Latitude, "X" => $Longitude);
             
            if ($RealDataFlag) { // true
                // add a third json element storing the areaname so we can easily display the area name alt tag / title tag for these dots and use areaid
                $CoordinatesArray['AreaID'] = $AreaID; // we eventually do not need this unless we want to show areaid as title (alt) or balloon...
                $CoordinatesArray['AreaName'] = $AreaName; // we eventually do not need this unless we want to show areaid as title (alt) or balloon...
            }
            $detailsArray["Coordinates"]=$CoordinatesArray;
            array_push($DataArray, $detailsArray);

            $i++;
            $ProjectDataSet = $selectStmt->fetch();
           
        }



        if ($RealDataFlag == true) {
            $Value = 1;
        } else {
            $Value = 0;
        }
        return array("DataPoints" => $DataArray, "NumRecordsWithinBounds" => $i, "RealData" => $Value);
    }

}

//**************************************************************************************
?>