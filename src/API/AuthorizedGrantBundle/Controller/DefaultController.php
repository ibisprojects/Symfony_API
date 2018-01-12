<?php

namespace API\AuthorizedGrantBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Classes\DBConnection\DBConnection;
use Classes\DBConnection\LiveDBConnection;
use Classes\DBTable\TBLProjects;
use Classes\DBTable\TBLPeople;
use Classes\DBTable\TBLForms;
use API\Classes\Constants;
use API\Classes\CommonFunctions;
use Classes\DBTable\RELPersonToProject;
use SimpleXMLElement;

class DefaultController extends Controller {

    private $grantType = "authorization_code";
    private $scope = "1"; //user scope
    private $ownerType = "user";

    public function getProjectsAndDatasheetsAPIAction(Request $request) {
        $returnArray = array('status' => Constants::FAILURE_STATUS, 'message' => "");
        if ($request->getMethod() == 'POST') {
            $accessToken = $request->get("Token", null);
            if ($accessToken != null) {
                $accessDetails = CommonFunctions::validateToken($accessToken, $this->grantType, $this->scope, $this->ownerType);
                if ($accessDetails && $accessDetails["owner_type"] == 'user') {
                    $login = $accessDetails["owner_id"];
                    $projectID = $request->get("ProjectID", "");
                    $citScitDB = new LiveDBConnection();
                    $dbConn = $citScitDB->connect();
                    $peopleSet = TBLPeople::GetSetFromLogin($dbConn, $login);
                    if ($peopleSet) {
                        $userID = $peopleSet["ID"];
                        $projectSets = TBLProjects::GetSetForPersonID($dbConn, $userID, $projectID);
                        $returnArray = array('status' => Constants::SUCCESS_STATUS, 'message' => "");
                        $data = array();
                        //$Role = PROJECT_CONTRIBUTOR;
                        $Role = "2";
                        foreach ($projectSets as $projectSet)
						{
                            $datasheetsData = TBLForms::GetSetFormEntriesProjectID($dbConn, $projectSet["ProjectID"]);
                            $PersonSet = TBLPeople::GetPersonSetFromProjectID($dbConn, $projectSet["ProjectID"],$Role );
                            $data[] = array("ProjectID" => $projectSet["ProjectID"], "ProjectName" => $projectSet["ProjName"], "Status" => $projectSet["Status"],
                            "Description" => $projectSet["Description"], "PinLatitude" => $projectSet["PinLatitude"], "PinLongitude" => $projectSet["PinLongitude"]
                            ,"Authority" => $PersonSet, "Datasheets" => $datasheetsData);
                        }
                        $returnArray["data"] = $data;
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

    public function getDatasheetsAPIAction(Request $request) {
        $returnArray = array('status' => Constants::FAILURE_STATUS, 'message' => "");
        if ($request->getMethod() == 'POST') {
            $accessToken = $request->get("Token", null);
            if ($accessToken != null) {
                $accessDetails = CommonFunctions::validateToken($accessToken, $this->grantType, $this->scope, $this->ownerType);
                if ($accessDetails && $accessDetails["owner_type"] == 'user') {
                    $login = $accessDetails["owner_id"];
                    $projectID = $request->get("ProjectID", "");
                    $citScitDB = new LiveDBConnection();
                    $dbConn = $citScitDB->connect();
                    $peopleSet = TBLPeople::GetSetFromLogin($dbConn, $login);
                    if ($peopleSet) {
                        $userID = $peopleSet["ID"];
                        if (is_numeric($projectID) && RELPersonToProject::HasRole($dbConn, $projectID, PERMISSION_CONTRIB, $userID)) {

                            $FormData = TBLForms::GetSetFormEntriesProjectID($dbConn, $projectID);
                            $PersonSet = TBLPeople::GetPersonSetFromProjectID($dbConn, $projectID, PROJECT_CONTRIBUTOR);
                            for ($index=0;$index<count($FormData);$index++) {
                                $FormData[$index]["Authority"]=$PersonSet;
                            }
                            $returnArray = array('status' => Constants::SUCCESS_STATUS, 'message' => "");
                            $returnArray["data"] = $FormData;
                        } else {
                            $returnArray = array('status' => Constants::FAILURE_STATUS, 'message' => "Not a project member");
                        }
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

    public function getProjectListAPIAction(Request $request) {
        $returnArray = array('status' => Constants::FAILURE_STATUS, 'message' => "");
        if ($request->getMethod() == 'POST') {
            $accessToken = $request->get("Token", null);
            if ($accessToken != null) {
                $accessDetails = CommonFunctions::validateToken($accessToken, $this->grantType, $this->scope, $this->ownerType);
                if ($accessDetails && $accessDetails["owner_type"] == 'user') {
                    $login = $accessDetails["owner_id"];
                    $projectID = $request->get("ProjectID", "");
                    $citScitDB = new LiveDBConnection();
                    $dbConn = $citScitDB->connect();
                    $peopleSet = TBLPeople::GetSetFromLogin($dbConn, $login);
                    if ($peopleSet) {
                        $userID = $peopleSet["ID"];
                        $projectSets = TBLProjects::GetSetForPersonID($dbConn, $userID, $projectID);
                        $returnArray = array('status' => Constants::SUCCESS_STATUS, 'message' => "");
                        $data = array();
                        foreach ($projectSets as $projectSet) {
                            $data[] = array("ProjectID" => $projectSet["ProjectID"], "ProjectName" => $projectSet["ProjName"], "Status" => $projectSet["Status"],
                                "Description" => $projectSet["Description"], "PinLatitude" => $projectSet["PinLatitude"], "PinLongitude" => $projectSet["PinLongitude"]);
                        }
                        $returnArray["data"] = $data;
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

	public function getMinAppRevisionAPIAction(Request $request) {
        $returnArray = array('status' => Constants::FAILURE_STATUS, 'message' => "");
        if ($request->getMethod() == 'POST')
		{
			// confirmation to app that API request was a success
			$returnArray = array('status' => Constants::SUCCESS_STATUS, 'message' => "2.1");
		}
		else
		{
            $returnArray = array('status' => Constants::FAILURE_STATUS, 'message' => "");
        }

		if ($request->getMethod() == 'GET')
		{
			// confirmation to app that API request was a success
			$returnArray = array('status' => Constants::SUCCESS_STATUS, 'message' => "2.1");
		}
		else
		{
			$returnArray = array('status' => Constants::SUCCESS_STATUS, 'message' => "2.1");
        }

        $mode = $request->get("Mode", "JSON");
        switch ($mode)
		{
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

}
