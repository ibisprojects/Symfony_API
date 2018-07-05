<?php

namespace API\ClientGrantBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use API\Classes\CommonFunctions;
use API\Classes\Constants;
use API\Classes\WebServices\TreeDataAPI;
use Classes\DBConnection\DBConnection;
use API\Classes\Validators;
use Classes\DBTable\TBLPeople;
use Classes\DBTable\RELPersonToProject;
use Classes\DBTable\TBLProjects;

define("ROLE_CONTRIBUTOR", "2");

class DefaultController extends Controller {

    private $grantType = "client_credentials";
    private $scope = "2"; //client scope
    private $ownerType = "client";

    public function UserVerificationAPIAction(Request $request) {
        $returnArray = array('status' => Constants::FAILURE_STATUS, 'message' => "");
        if ($request->getMethod() == 'POST') {
            $accessToken = $request->get("Token", null);
            if ($accessToken != null && CommonFunctions::validateToken($accessToken, $this->grantType, $this->scope, $this->ownerType)) {
                $login = $request->get("Login", "");
                $verificationCode = $request->get("VerificationCode", "");
                $citScitDB = new DBConnection();
                $dbConn = $citScitDB->connect();
                $peopleSet = TBLPeople::GetSetFromLogin($dbConn, $login);
                if ($peopleSet) {
                    if ($peopleSet["VerificationCode"] == $verificationCode) {
                        TBLPeople::verifyUser($dbConn, $peopleSet["ID"]);
                        $returnArray["status"] = Constants::SUCCESS_STATUS;
                        $returnArray["message"] = "User Verification Successful";
                    }
                    else{
                        $returnArray["message"] = Constants::INVALID_VERIFICATION_CODE;
                    }
                }
                else{
                    $returnArray["message"] = Constants::INVALID_USER_ID;
                }
            } else {
                $returnArray["message"] = Constants::INVALID_TOKEN_MESSAGE;
            }
        } else {
            $returnArray["message"] = Constants::INVALID_REQUEST_MESSAGE;
        }
        $returnJSON = new Response(json_encode($returnArray));
        $returnJSON->headers->set('Content-Type', 'application/json');
        return $returnJSON;
    }

    public function UserRegistrationAPIAction(Request $request) {
        $returnArray = array('status' => Constants::FAILURE_STATUS, 'message' => "");
        if ($request->getMethod() == 'POST') {
            $accessToken = $request->get("Token", null);
            if ($accessToken != null && CommonFunctions::validateToken($accessToken, $this->grantType, $this->scope, $this->ownerType)) {
                $firstName = $request->get("FirstName", "");
                $lastName = $request->get("LastName", "");
                $login = $request->get("Login", "");
                $email = $request->get("Email", "");
                $role = $request->get("Role", "CitizenScientist");
                $coordinatorMessage = $request->get("CoordinatorMessage", "");
                $password = $request->get("Password", "");
                $acceptTerms = $request->get("AcceptTerms", "0");
                $websiteID = $request->get("WebsiteID", "1");
                $sendVerificationEmail = $request->get("SendVerificationEmail", "0");
                $autoVerify = $request->get("AutoVerify", "0");
                $autoLinkProjects = $request->get("AutoLinkProjects", "0");
                $testMode = $request->get("Test", "0");

                $validInputs = true;
                if (!Validators::validateEmail($this, $email)) {
                    $returnArray["message"] .= ", Email entered is invalid";
                    $validInputs = false;
                }
                if (!Validators::validateNonNumericalString($this, $firstName, Constants::NAME_MIN_LENGTH, Constants::NAME_MAX_LENGTH)) {
                    $returnArray["message"] .= ", First name entered is invalid";
                    $validInputs = false;
                }
                if (!Validators::validateNonNumericalString($this, $lastName, Constants::NAME_MIN_LENGTH, Constants::NAME_MAX_LENGTH)) {
                    $returnArray["message"] .= ", Last name entered is invalid";
                    $validInputs = false;
                }
                if (!Validators::validateString($this, $password, Constants::PASSWORD_MIN_LENGTH, Constants::PASSWORD_MAX_LENGTH)) {
                    $returnArray["message"] .= ", Password entered is invalid";
                    $validInputs = false;
                }
                if (!Validators::validateString($this, $login, Constants::LOGIN_MIN_LENGTH, Constants::LOGIN_MAX_LENGTH)) {
                    $returnArray["message"] .= ", Login entered is invalid";
                    $validInputs = false;
                }
                if ($acceptTerms != 1 || $acceptTerms != "1") {
                    $returnArray["message"] .= ", Terms not accepted";
                    $validInputs = false;
                }
                if ($role != "CitizenScientist" && $role != "ProjectCoordinator") {
                    $returnArray["message"] .= ", Role entered is invalid";
                    $validInputs = false;
                }
                if (!is_numeric($websiteID)) {
                    $returnArray["message"] .= ", WebSiteID entered is invalid";
                    $validInputs = false;
                }
                $returnArray["message"] = trim(trim($returnArray["message"], ","));
                if ($validInputs) {
                    $citScitDB = new DBConnection();
                    $dbConn = $citScitDB->connect();
                    if (!TBLPeople::GetSetFromLogin($dbConn, $login)) {
                        if (!TBLPeople::GetSetFromEmail($dbConn, $email)) {
                            if ($testMode == "0") {
                                $verificationCode = TBLPeople::Register($dbConn, $firstName, $lastName, $acceptTerms, $acceptTerms, $email, $login, $password, $role, $coordinatorMessage, $websiteID, $autoVerify, $sendVerificationEmail);
                                $returnArray["status"] = Constants::SUCCESS_STATUS;
                                $returnArray["message"] = "User Registration Successful";
                                $returnArray["data"] = array("VerificationCode" => $verificationCode);
                                if ($autoLinkProjects != null && $autoVerify == "1") {
                                    $autolinkProjectArray = explode(";", $autoLinkProjects);
                                    $personSet = TBLPeople::GetSetFromLogin($dbConn, $login);
                                    $personID = $personSet["ID"];
                                    foreach ($autolinkProjectArray as $projectID) {
                                        if (is_numeric($projectID) && TBLProjects::GetSetFromID($dbConn, $projectID)) {
                                            RELPersonToProject::Insert($dbConn, $personID, $projectID, ROLE_CONTRIBUTOR);
                                        }
                                    }
                                }
                            }
                            else{
                                $returnArray["status"] = Constants::SUCCESS_STATUS;
                                $returnArray["message"] = "User Registration Successful";
                            }
                        } else {
                            $returnArray["message"] = Constants::EMAIL_EXISTS_MESSAGE;
                        }
                    } else {
                        $returnArray["message"] = Constants::LOGIN_EXISTS_MESSAGE;
                    }
                }
            } else {
                $returnArray["message"] = Constants::INVALID_TOKEN_MESSAGE;
            }
        } else {
            $returnArray["message"] = Constants::INVALID_REQUEST_MESSAGE;
        }
        $returnJSON = new Response(json_encode($returnArray));
        $returnJSON->headers->set('Content-Type', 'application/json');
        return $returnJSON;
    }

    public function TreeDataAPIAction(Request $request) {
        $returnArray = array('status' => Constants::FAILURE_STATUS, 'message' => "");
        if ($request->getMethod() == 'POST') {
            $accessToken = $request->get("Token", null);

            if ($accessToken != null && CommonFunctions::validateToken($accessToken, $this->grantType, $this->scope, $this->ownerType)) {
                $citScitDB = new DBConnection();
                $dbConn = $citScitDB->connect();
                $WebSiteID = $request->get("WebSiteID", 7);
                $ProjectID = $request->get("ProjectID", 0);
                $TreeID = $request->get("TreeID", 0);
                $ZoomLevel = $request->get("ZoomLevel", 0);
                $Bounds = $request->get("Bounds", null);
                $returnData = TreeDataAPI::getTreeData($dbConn, $WebSiteID, $ProjectID, $TreeID, $ZoomLevel, $Bounds);
                $returnArray["status"] = Constants::SUCCESS_STATUS;
                $returnArray["data"] = $returnData;
            } else {
                $returnArray["message"] = Constants::INVALID_TOKEN_MESSAGE;
            }
        } else {
            $returnArray["message"] = Constants::INVALID_REQUEST_MESSAGE;
        }
        $returnJSON = new Response(json_encode($returnArray));
        $returnJSON->headers->set('Content-Type', 'application/json');
        return $returnJSON;
    }

}
