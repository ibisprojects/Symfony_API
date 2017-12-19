<?php

namespace API\AuthorizedGrantBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Classes\DBConnection\DBConnection;
use Classes\DBTable\TBLPeople;
use API\Classes\Constants;
use API\Classes\CommonFunctions;
use SimpleXMLElement;

class UserController extends Controller {

    private $grantType = "authorization_code";
    private $scope = "1"; //user scope
    private $ownerType = "user";

    public function getUserDataAPIAction(Request $request) {
        $returnArray = array('status' => Constants::FAILURE_STATUS, 'message' => "");
        if ($request->getMethod() == 'POST') {
            $accessToken = $request->get("Token", null);
            if ($accessToken != null) {
                $accessDetails = CommonFunctions::validateToken($accessToken, $this->grantType, $this->scope, $this->ownerType);
                if ($accessDetails && $accessDetails["owner_type"] == 'user') {
                    $login = $accessDetails["owner_id"];
                    $projectID = $request->get("ProjectID", "");
                    $citScitDB = new DBConnection();
                    $dbConn = $citScitDB->connect();
                    $peopleSet = TBLPeople::GetSetFromLogin($dbConn, $login);
                    if ($peopleSet) {
                        $returnArray = array('status' => Constants::SUCCESS_STATUS, 'message' => "");
                        $returnArray["data"] = array("FirstName"=>$peopleSet["FirstName"],"LastName"=>$peopleSet["LastName"],"Login"=>$peopleSet["Login"]);
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

}
