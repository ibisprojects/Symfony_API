<?php

namespace API\AuthorizedGrantBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Classes\DBConnection\DBConnection;
use Classes\DBTable\TBLProjects;
use Classes\DBTable\TBLPeople;
use Classes\DBTable\TBLForms;
use Classes\DBTable\TBLInsertLogs;
use API\Classes\Constants;
use API\Classes\CommonFunctions;
use Classes\DBTable\RELPersonToProject;
use SimpleXMLElement;
use DateTime;
use Classes\DBTable\TBLOrganismData;
use Classes\DBTable\TBLVisits;
use Classes\DBTable\LKUAttributeTypes;
use Classes\DBTable\LKUAttributeValues;
use Classes\DBTable\TBLAttributeData;

class UploadController extends Controller {

    private $grantType = "authorization_code";
    private $scope = "1"; //user scope
    private $ownerType = "user";

    public function uploadDatasheetsAPIAction(Request $request) {
        $returnArray = array('status' => Constants::FAILURE_STATUS, 'message' => "");
        if ($request->getMethod() == 'POST') {
            $accessToken = $request->get("Token", null);
            if ($accessToken != null) {
                $accessDetails = CommonFunctions::validateToken($accessToken, $this->grantType, $this->scope, $this->ownerType);
                if ($accessDetails && $accessDetails["owner_type"] == 'user') {
                    $login = $accessDetails["owner_id"];
                    $citScitDB = new DBConnection();
                    $dbConn = $citScitDB->connect();
                    $peopleSet = TBLPeople::GetSetFromLogin($dbConn, $login);
                    if ($peopleSet) {
                        $userID = $peopleSet["ID"];

                        //Upload Starts here
                        $xmlTextData = $request->get("XMLData", null);
                        $xmlData = new SimpleXMLElement($xmlTextData);
                        $this->processXML($dbConn, $xmlData, $userID);
                    } else {
                        $returnArray = array('status' => Constants::FAILURE_STATUS, 'message' => "Invalid User");
                    }
                } else {
                    $returnArray = array('status' => Constants::FAILURE_STATUS, 'message' => "Invalid Token");
                }
            } else {
                $returnArray = array('status' => Constants::FAILURE_STATUS, 'message' => "Invalid Token");
            }
        } else {
            $returnArray["message"] = Constants::INVALID_REQUEST_MESSAGE;
        }
        $mode = $request->get("Mode", "JSON");
        switch ($mode) {
            case "XML":
                $xmlRoot = new SimpleXMLElement("<?xml version=\"1.0\"?><SearchHotels></SearchHotels>");
                $node = $xmlRoot->addChild('request');
                $XML = CommonFunctions::array2xml($returnArray, $node);
                $return = new Response($XML);
                break;
            default:
                $return = new Response(json_encode($returnArray));
                $return->headers->set('Content-Type', 'application/json');
        }
        return $return;
    }

    public function processXML($dbConn, $GODMElement, $UserID) {
        $AuthorityId = 0; // default to zero but then try to go find it in xml and replace it with value from xml if value is legit b/c greater than zero

        $FormID = (string) $GODMElement['FormId'];
        $ProjectElement = $GODMElement->Project;
        $ProjectID = (string) $ProjectElement['ID'];
        $LocationName = $ProjectElement->Name['Value'];
        $AreaElement = $ProjectElement->Area;
        $aname = $AreaElement->getName();
        $pname = $ProjectElement->getName();
        $AreaElement = $ProjectElement->Area;
        $areaelementname = $AreaElement->getName();
         $VisitID = 0;
         
        if (is_numeric($ProjectID) && RELPersonToProject::HasRole($dbConn, $ProjectID, PERMISSION_CONTRIB, $UserID)) {
            foreach ($AreaElement->attributes() as $Key => $Value) {
                if ($Key == "X") {
                    $RefX = $Value;
                } elseif ($Key == "Y") {
                    $RefY = $Value;
                } elseif ($Key == "CoordinateSystemID") {
                    $CoordinateSystemID = $Value;
                } elseif ($Key == "Accuracy") {
                    $Accuracy = $Value;
                }
            }
            $VisitElement = $ProjectElement->Visit;
            foreach ($VisitElement->attributes() as $Key => $Value) {
                if ($Key == "Date") {
                    $Date = $Value;
                }
            }


            foreach ($VisitElement->children() as $Element) {
                switch ($Element->getName()) {
                    case "Authority":
                        $AuthorityName = $Element->children()->AuthorityOption['Value'];
                        $AuthorityId = $Element->children()->AuthorityOption['ID'];
                        break;

                    case "Recorder":
                        $RecorderName = $Element->children()->RecorderOption['Value'];
                        $RecorderId = $UserID;
                        break;

                    case "Time":
                        $Time = $Element['Value'];
                        $RecTime = $Time;
                        break;

                    case "VisitComment":
                        $VisitComment = $Element['Value'];
                        break;

                    case "OrganismData":                       
                        $NumEmpty = 0;
                        $NumAttributes = 1;

                        foreach ($Element->children() as $OrganismDataElement) { // $OrganismDataElement's include things like: <OrganismDataOption ...><OrganismDataOption...><OrganismDataOption...>
                            $OrganismDataElementName = $OrganismDataElement->getName();


                            if ($OrganismDataElementName == "AttributeData") {
                                $AttributeDataElements = $OrganismDataElement->children();
                                $NumAttributes = count($AttributeDataElements) + $NumAttributes;

                                foreach ($OrganismDataElement->children() as $AttributeDataElement) { // $OrganismDataElement's include things like: <OrganismDataOption ...><OrganismDataOption...><OrganismDataOption...>
                                    $AttributeDataElementName = $AttributeDataElement->getName();                               

                                    foreach ($AttributeDataElement->attributes() as $Name => $Value) { // $a => $b
                                        if ($Name == "Value") {
                                            if (($Value == "-- Select --") || ($Value == "")) {
                                                $NumEmpty++;
                                            }
                                        }
                                    }
                                }
                            }

                            foreach ($OrganismDataElement->attributes() as $Key => $Value) { // was $OrganismDataElement
                                if ($Key == "OrganismComment") {
                                    if ($Value == "") {
                                        $NumEmpty++;
                                    }
                                }
                            }
                        }


                        if ($NumEmpty < $NumAttributes) {// and comments not= null or blank... // if they are not all empty, go forth and add the organsim data... if we have at least one attribute value for the organism... do AddPoint()
                            foreach ($Element->children() as $OrganismDataElement) { // $OrganismDataElement's include things like: <OrganismDataOption ...><OrganismDataOption...><OrganismDataOption...>

                                foreach ($OrganismDataElement->attributes() as $Key => $Value) { // was $OrganismDataElement
                                    if ($Key == "OrganismInfoID") {
                                        $OrgInfoId = $Value;
                                        //$DateObj = new Date();
                                        $DatePieces = explode("/", $Date);
                                       $DateObj = new \DateTime($DatePieces[2]."-".$DatePieces[0]."-".$DatePieces[1]   );

                                        $PersonID = $UserID; // was defaulting to null; GJN


                                        $PersonName = TBLPeople::GetPersonsName($dbConn, $PersonID);
                                        $InsertLogID = TBLInsertLogs::Insert($dbConn, INSERT_LOG_FORM, null, $PersonName, NOT_SPECIFIED, NOT_SPECIFIED, $UserID, $FormID);


                                        if (($Accuracy == null) || ($Accuracy == ""))
                                            $Accuracy = 5;
                                        $OrganismDataID = TBLOrganismData::AddPoint($dbConn, $PersonID, $ProjectID, $RefX, $RefY, $CoordinateSystemID, $LocationName, $DateObj, null, null, $OrgInfoId, $Accuracy, true, null, null, null, $VisitComment, $InsertLogID, null, $FormID);


                                        $OrganismDataSet = TBLOrganismData::GetSetFromID($dbConn, $OrganismDataID);

                                        $VisitID = $OrganismDataSet["VisitID"];

                                        if ($AuthorityId > 0) {
                                            TBLVisits::SetFieldValue($dbConn, "AuthorityID", $VisitID, $AuthorityId);
                                        }
                                    }

                                    if ($Key == "OrganismComment") {
                                        $OrgComments = $Value;

                                        if ($OrgComments != "") {
                                            TBLOrganismData::SetFieldValue($dbConn, "Comments", $OrganismDataID, "$OrgComments");
                                        }
                                    }
                                }

                                $ValType = null;
                                foreach ($OrganismDataElement->children() as $Element) {
                                    switch ($Element->getName()) {
                                        case "AttributeDataOption":
                                            $AtteTyID = null;
                                            $AttrVal = null;
                                            $AttrValID = null;

                                            foreach ($Element->attributes() as $Key => $Value) {
                                                if ($Key == "Value") {
                                                    $AttrVal = $Value;
                                                } elseif ($Key == "AttributeDataTypeID") {
                                                    $AtteTyID = $Value;
                                                }

                                                if ($AtteTyID != null && $AttrVal != null && $AttrVal != "-- Select --" && $AttrVal != "") { // error checking for blank entries
                                                    $RecSet = LKUAttributeTypes::GetSetFromID($dbConn, $AtteTyID);
                                                    $ValType = $RecSet['ValueType'];

                                                    if ($ValType == 1) {
                                                        $AttrVal = LKUAttributeValues::GetIDFromName($dbConn, $AttrVal);
                                                    } elseif ($ValType == 2) {
                                                        $AttrVal = (float) $AttrVal;
                                                    } elseif ($ValType == 3) {
                                                        $AttrVal = (int) $AttrVal;
                                                    } else {
                                                        $AttrVal = $AttrVal;
                                                    }
                                                    TBLAttributeData::Insert($dbConn, null, $OrganismDataID, null, $AtteTyID, $AttrVal, null, null, null, null);
                                                }
                                            }
                                            break;
                                    }
                                }
                            }
                        }

                        break;

                    case "SiteCharacteristics":
                        foreach ($Element->children() as $SiteCharacteristicsData) {
                            if ($VisitID == 0) {
                                $DatePieces = explode("/", $Date);
                                $DateObj = new \DateTime($DatePieces[2]."-".$DatePieces[0]."-".$DatePieces[1]   );
                            }

                            switch ($SiteCharacteristicsData->getName()) { // was $Element->getName()
                                case "AttributeDataOption":
                                    $AtteTyID = null;
                                    $AttrVal = null;
                                    $AttrValID = null;
                                    $ValType = null;

                                    foreach ($SiteCharacteristicsData->attributes() as $Key => $Value) {
                                        if ($Key == "Value") {
                                            $AttrVal = $Value;
                                        } elseif ($Key == "AttributeDataTypeID") {
                                            $AtteTyID = $Value;
                                        }

                                        if ($AtteTyID != null && $AttrVal != null && $AttrVal != "-- Select --" && $AttrVal != "") {
                                            $RecSet = LKUAttributeTypes::GetSetFromID($dbConn, $AtteTyID);
                                            $ValType = $RecSet['ValueType'];

                                            if ($ValType == 1) {
                                                $AttrVal = LKUAttributeValues::GetIDFromName($dbConn, $AttrVal);
                                            } elseif ($ValType == 2) {
                                                $AttrVal = (float) $AttrVal;
                                            } else {
                                                $AttrVal = (int) $AttrVal;
                                            }

                                            TBLAttributeData::Insert($dbConn, $VisitID, null, null, $AtteTyID, $AttrVal, null, null, null, null);
                                        }
                                    }
                                    break;
                            }
                        }
                        break;

                    case "AttributeDataOption":
                        foreach ($Element->children() as $AttributeDataOption) {
                            //$this->GetAttributeInfo($Database,$AttributeDataOption);
                        }
                        break;
                }
            }
        } else {
            print_r("Not Member");
        }
    }

}
