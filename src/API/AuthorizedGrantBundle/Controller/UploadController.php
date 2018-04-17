<?php

namespace API\AuthorizedGrantBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Classes\DBConnection\DBConnection;
use Classes\DBTable\TBLPeople;
use Classes\DBTable\TBLAreas;
use API\Classes\Constants;
use API\Classes\CommonFunctions;
use SimpleXMLElement;
use Classes\DBTable\Upload;
use Classes\DBTable\Date;
use Classes\DBTable\TBLInsertLogs;
use Classes\DBTable\TBLVisits;
use Classes\DBTable\TBLOrganismData;
use Classes\DBTable\LKUAttributeTypes;
use Classes\DBTable\TBLAttributeData;
use Classes\DBTable\TBLMedia;
use Classes\DBTable\RELMediaToOrganismData;
use Classes\DBTable\RELMediaToVisit;

class UploadController extends Controller {

    private $grantType = "authorization_code";
    private $scope = "1"; //user scope
    private $ownerType = "user";


    public function uploadDatasheetsAPIAction(Request $request)
    {
        $returnArray = array('status' => Constants::FAILURE_STATUS, 'message' => "");
        $returnHTML = "";

        if ($request->getMethod() == 'POST') {
			// Set up log file for debugging
            $loggerService = $this->get('api.logger.mobile');

            $accessToken = $request->get("Token", null);

            $loggerService->logger->info("Token=$accessToken");

            if ($accessToken != null) {
                $accessDetails = CommonFunctions::validateToken($accessToken, $this->grantType, $this->scope, $this->ownerType);
                if ($accessDetails && $accessDetails["owner_type"] == 'user') {
                    $login = $accessDetails["owner_id"];
                    $citScitDB = new DBConnection();
                    $dbConn = $citScitDB->connect();
                    $peopleSet = TBLPeople::GetSetFromLogin($dbConn, $login);

                    if ($peopleSet)
                    {
                        // get userid from validated person set
                        $UserID = $peopleSet["ID"];

                        // Initial Debugs
                        $loggerService->logger->info("****Begin Processing of App files****");

                        // Initial Settings
                        $TimeStamp = strtotime(date("Y-m-d H:i:s"));
                        $XMLMovedFlag=FALSE;
                        $ImageMovedFlag=TRUE;

                        // get number of images from NumFiles posted params
                        $NumFiles=$_POST["NumFiles"];

                        $loggerService->logger->info("NumFiles=$NumFiles");

						$XMLOriginalFileName=$_FILES['XMLData']['name'];

                        $loggerService->logger->info("XMLFileName=$XMLOriginalFileName");

                        // MOVE XML FILE (And create the Observation folder to put XML and images into)

                        if ($_FILES["XMLData"]) // xml file exists in the post, so move it
						{
							// get and edit FILE NAME
							$XMLOriginalFileName=$_FILES['XMLData']['name'];
							$XMLFileName=$TimeStamp."_".$XMLOriginalFileName;
							$XMLFileName=str_replace("+"," ",$XMLFileName);
							// Create Observation Name (folder name)
							$ObservationName=substr($XMLFileName,0,-4);  // removes the extension (.xml)
							$ObservationName = str_replace(' ', '', $ObservationName);

							// create observation folder to put XML AND images into
							$ObservationPath = "/var/www/citsci/inetpub/UserUploads/$UserID/MobileData/$ObservationName/";  // if on dev/LIVE

							if (!file_exists($ObservationPath))
							{
								mkdir($ObservationPath,0777,TRUE);
							}

							$FullXMLPath=$ObservationPath.$XMLOriginalFileName;

							// Get XML file and Move it into observation folder
							$XMLMovedFlag=move_uploaded_file($_FILES['XMLData']['tmp_name'],$FullXMLPath);

							// Debugs
							//echo("ObservationName=$ObservationName<br>XMLMovedFlag=$XMLMovedFlag<br>FullXMLFilePath=$ObservationPath$XMLOriginalFileName<br>--------------<br>");
                            $loggerService->logger->info("ObservationName=$ObservationName");
                            $loggerService->logger->info("XMLMovedFlag=$XMLMovedFlag");
                            $loggerService->logger->info("FullXMLFilePath=$ObservationPath$XMLOriginalFileName");
                            $loggerService->logger->info("----------");
						}
						else
						{
							//echo("Could not retrieve XML file from POST<br>");
						}

                        // MOVE IMAGE FILES if any

                        $files = array();

                        if($NumFiles>0)
                        {
                            $FileArray=array();

                            $loggerService->logger->info("IN PHOTO UPLOAD - before MOVE");

                            $Directory = "/var/www/citsci/inetpub/UserUploads/$UserID/Media/";

                            if (!(file_exists($Directory))) {
                                mkdir($Directory,0777,TRUE);
                            }

                            // Move images to Observation folder
                            $files = Upload::MoveUploadedFiles($NumFiles,$Directory,100000000,$FileArray, $loggerService);  //$Result=Upload::MoveUploadedFiles($NumFiles,$ObservationPath,100000000,$FileArray);

                            $loggerService->logger->info("IN PHOTO UPLOAD - photo directory: $Directory");

                            foreach ($files as $photofilename) {
                                print_r($photofilename."\n");
                                if (!(file_exists($Directory."_thumbnails"))) {mkdir($Directory."_thumbnails",0777,TRUE);};
                                if (!(file_exists($Directory."_display"))) {mkdir($Directory."_display",0777,TRUE);};
                                if (!(file_exists($Directory."_print"))) {mkdir($Directory."_print",0777,TRUE);};

                                copy("$Directory$photofilename","{$Directory}_thumbnails/$photofilename");
                                copy("$Directory$photofilename","{$Directory}_display/$photofilename");
                                copy("$Directory$photofilename","{$Directory}_print/$photofilename");
                            }
						}

                        $loggerService->logger->info("BEFORE EXECUTE:");
                        $loggerService->logger->info("FullXMLPath=$FullXMLPath");
                        $loggerService->logger->info("NumFiles=$NumFiles");
                        $loggerService->logger->info("USERID=$UserID");
                        $loggerService->logger->info("ObservationName=$ObservationName");

                        $this->insertData($dbConn, $FullXMLPath, $UserID, $ObservationName, $files);

                        $returnArray = array('status' => Constants::SUCCESS_STATUS, 'message' => "","returnHTML" => $returnHTML);
                    } else {
                        $returnArray = array('status' => Constants::FAILURE_STATUS, 'message' => "Invalid User", "returnHTML" => $returnHTML);
                    }
                } else {
                    $returnArray = array('status' => Constants::FAILURE_STATUS, 'message' => "Invalid Token", "returnHTML" => $returnHTML);
                }
            } else {
                $returnArray = array('status' => Constants::FAILURE_STATUS, 'message' => "Invalid Token", "returnHTML" => $returnHTML);
            }
        } else {
            $returnArray["message"] = Constants::INVALID_REQUEST_MESSAGE;
            $returnArray["returnHTML"] = $returnHTML;
        }

        $mode = $request->get("Mode", "JSON");

        switch ($mode) {
            case "XML":
                $xmlRoot = new SimpleXMLElement("<?xml version=\"1.0\"?><OutputItem></OutputItem>");
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

    /**
     * Duplicated temporarily the InsertAppData.app code in this method to improve the API security.
     * IMPORTANT: we should avoid connect directly the API and web-app using a shell script.
     *
     * @return void
     */
    protected function insertData($dbConn, $XMLPath, $UserID, $ObservationName, $files)
    {
        $loggerService = $this->get('api.logger.insertdata');

        // Initial Debugs
        $loggerService->logger->info("**Begin InsertAppData.php***");
        $loggerService->logger->info("Received XMLPath=$XMLPath");
        $loggerService->logger->info("Files=".implode(', ', $files));
        $loggerService->logger->info("UserID=$UserID");
        $loggerService->logger->info("ObservationName=$ObservationName");

        // Create XML object

        $GODMElement = simplexml_load_file($XMLPath);

        //---------------------------------------
        // processes XML Data
        //---------------------------------------

        $loggerService->logger->info("<Response>");
        $ProjectID = null;
        $FormID = null;
        $ErrorString = null;

        // Get Values from XML up to ORGANISMDATA or SITECHARACTERISTICS

        $FormID = $GODMElement['FormId'];
        $ProjectElement = $GODMElement->Project;
        $AreaElement = $ProjectElement->Area;
        $VisitElement = $AreaElement->Visit;
        $ProjectID = $ProjectElement['ID'];
        $LocationName = $ProjectElement->Area['AreaName'];
        $PredefinedAreaID = $ProjectElement->Area['AreaID'];
        $RefX = $ProjectElement->Area['X'];
        $RefY = $ProjectElement->Area['Y'];
        $CoordinateSystemID = $ProjectElement->Area['CoordinateSystemID'];
        $Accuracy = $ProjectElement->Area['Accuracy'];
        $Date = $AreaElement->Visit['Date'];
        $AuthorityName = $VisitElement->Authority->AuthorityOption['Value'];  // NEVER USED
        $AuthorityId = $VisitElement->Authority->AuthorityOption['ID'];
        $RecorderName = $VisitElement->Recorder->RecorderOption['Value'];  // NEVER USED
        $RecorderId = $UserID; // how it was set up in previous version
        $RecTime = $VisitElement->Time['Value'];  // NEVER USED
        $VisitComment = $VisitElement->VisitComment['Value'];

        // Decode special characters

        $LocationName = html_entity_decode($LocationName);
        $VisitComment = html_entity_decode($VisitComment);
        $PredefinedAreaID = null;

        // If predefined location, get AreaName
        if ($PredefinedAreaID > 0)
        {
            $PredefinedAreaName = TBLAreas::GetAreaName($dbConn, $PredefinedAreaID);
            $loggerService->logger->info("PredefinedAreaName=$PredefinedAreaName");
        }

        // Debugs

        $loggerService->logger->info("UserID=$UserID");
        $loggerService->logger->info("ObservationName=$ObservationName");
        $loggerService->logger->info("Date=$Date");
        $loggerService->logger->info("FormID=$FormID");
        $loggerService->logger->info("ProjectID=$ProjectID");
        $loggerService->logger->info("LocationName=$LocationName");
        $loggerService->logger->info("PredefinedAreaID=$PredefinedAreaID");
        $loggerService->logger->info("RefX=$RefX");
        $loggerService->logger->info("RefY=$RefY");
        $loggerService->logger->info("CoordinateSystemID=$CoordinateSystemID");
        $loggerService->logger->info("Accuracy=$Accuracy");
        $loggerService->logger->info("AuthorityName=$AuthorityName");
        $loggerService->logger->info("AuthorityId=$AuthorityId");
        $loggerService->logger->info("RecorderName=$RecorderName");
        $loggerService->logger->info("RecorderID=$RecorderId");
        $loggerService->logger->info("RecTime=$RecTime");
        $loggerService->logger->info("VisitComment=$VisitComment");

        /////////////////////
        // Create a VISIT  //
        /////////////////////

        $loggerService->logger->info("**Begin creating visit**");

        $DateObj = new Date();
        $DatePieces = explode("/",$Date);

        $DateObj->Year = $DatePieces[2];
        $DateObj->Day = $DatePieces[1];
        $DateObj->Month = $DatePieces[0];

        $PersonID = $UserID;

        $PersonName = TBLPeople::GetPersonsName($dbConn,$PersonID);

        if (($Accuracy == null) || ($Accuracy == ""))
            $Accuracy=5;

        $InsertLogID = TBLInsertLogs::Insert(
            $dbConn,
            Constants::INSERT_LOG_APP,
            null,
            $PersonName,
            Constants::NOT_SPECIFIED,
            Constants::NOT_SPECIFIED,
            $PersonID,
            $FormID,
            $ProjectID,
            7
        );

        if (!$InsertLogID)
            return;

        $VisitID = TBLVisits::InsertVisitOnly(
            $dbConn,
            $RecorderId,
            $DateObj,
            $RefX,
            $RefY,
            $ProjectID,
            $LocationName,
            null,
            $CoordinateSystemID,
            $Accuracy,
            $FormID,
            Constants::INSERT_LOG_APP,
            $VisitComment,
            $InsertLogID,
            $PredefinedAreaID
        );

        /////////////////////////////////////////////////////
        // Process Organism and Site Characteristic Data  ///
        /////////////////////////////////////////////////////

        foreach ($VisitElement->children() as $Element) {
            switch ($Element->getName()) {
                case "OrganismData":
                    $NumEmpty = 0;
                    $NumAttributes = 1; // 1 for the org comment

                    // Preprocessing: check if organism XML has empty values
                    // This piece of code counts how many organism data records including the organism comment <AttributeDataOption Value=""> are empty values and
                    // compares it to the total num of records since iOS can send an Organism XML with all empty measurements and we don't want to create the orgdataid, Android will not send the org XML

                    foreach ($Element->children() as $OrganismDataElement) { // $OrganismDataElement's include things like: <OrganismDataOption ...><OrganismDataOption...><OrganismDataOption...>
                        $OrganismDataElementName = $OrganismDataElement->getName();

                        if ($OrganismDataElementName == "AttributeData") {
                            $AttributeDataElements = $OrganismDataElement->children();
                            $NumAttributes = count($AttributeDataElements) + $NumAttributes;

                            foreach ($AttributeDataElements as $AttributeDataElement) { // $OrganismDataElement's include things like: <OrganismDataOption ...><OrganismDataOption...><OrganismDataOption...>
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
                                    $NumEmpty++; // if org comments are blank and there are no photos (need to be more specific=query for no org photo), don't add organism
                                }
                            } else if ($Key == "OrganismInfoID") { // this is to check if there's a photo associated with the organisminfoid
                                $CurrentOrganismInfoID = $Value;
                                $loggerService->logger->info("**For PHOTO check: OrganismInfoID=$CurrentOrganismInfoID**");
                            }
                        }
                    }  // end foreach ($Element->children() as $OrganismDataElement)

                    $loggerService->logger->info("**COMPARING EMPTY ORG VALUES**");
                    $loggerService->logger->info("****NumAttributes=$NumAttributes, NumEmpty=$NumEmpty**");

                    // More Preprocessing: Check if there's a photo associated with the organisminfoid

                    $PhotoForThisOrganism = 0;

                    if (!empty($files)) {
                        foreach($files as $photofilename) {
                            trigger_error($photofilename);
                            preg_match('/(?P<name>\w+)_(?P<organism>\w+)_(?P<OrganismInfoID>\d+)_(?P<imagenumber>\w+)/', $photofilename, $matches);

                            if (array_key_exists('OrganismInfoID', $matches)) {
                                if ($CurrentOrganismInfoID == $matches["OrganismInfoID"]) { // if there's a match
                                    $PhotoForThisOrganism = 1;
                                    break;
                                }
                            }
                        }
                    }

                    ///////////////////////////////////////////////////
                    // PART 2: Process the organism and its records ///
                    ///////////////////////////////////////////////////

                    // process if there's at least one org measurement OR there's a photo associated with the org

                    if (($NumEmpty < $NumAttributes) || ($PhotoForThisOrganism == 1)) {
                        foreach ($Element->children() as $OrganismDataElement) { // $Element's children include things like: <OrganismDataOption> and <AttributeData>
                            foreach ($OrganismDataElement->attributes() as $Key => $Value) { // $OrganismDataElement's attribute keys include OrganismInfoID, OrganismComment
                                if ($Key == "OrganismInfoID") { // then process the organism
                                    $OrgInfoId = $Value;

                                    // insert rest of organism info

                                    $DateObj = new Date();
                                    $DatePieces = explode("/", $Date);
                                    $DateObj->Year = $DatePieces[2];
                                    $DateObj->Day = $DatePieces[1];
                                    $DateObj->Month = $DatePieces[0];

                                    $PersonID = $UserID; // was defaulting to null; GJN

                                    $PersonName = TBLPeople::GetPersonsName($dbConn, $PersonID);

                                    $InsertLogID = TBLInsertLogs::Insert(
                                        $dbConn,
                                        Constants::INSERT_LOG_APP,
                                        null,
                                        $PersonName,
                                        Constants::NOT_SPECIFIED,
                                        Constants::NOT_SPECIFIED,
                                        $PersonID,
                                        $FormID,
                                        $ProjectID,
                                        7
                                    );

                                    if ($Accuracy == null || $Accuracy == "")
                                        $Accuracy = 5;

                                    $loggerService->logger->info("**** DEBUG: NumEmpty=$NumEmpty, NumAttributes=$NumAttributes, RefX=$RefX, RefY=$RefY, CoordinateSystemID=$CoordinateSystemID, LocationName=$LocationName, PredefinedAreaID=$PredefinedAreaID");

                                    $OrganismDataID = TBLOrganismData::AddPoint(
                                        $dbConn,
                                        $PersonID,
                                        $ProjectID,
                                        $RefX,
                                        $RefY,
                                        $CoordinateSystemID,
                                        $LocationName,
                                        $DateObj,
                                        null,
                                        null,
                                        $OrgInfoId,
                                        $Accuracy,
                                        false,
                                        null,
                                        null,
                                        null,
                                        $VisitComment,
                                        $InsertLogID,
                                        null,
                                        $FormID,
                                        $PredefinedAreaID
                                    );

                                    $OrganismDataSet = TBLOrganismData::GetSetFromID($dbConn, $OrganismDataID);

                                    if ($OrganismDataSet) {
                                        $VisitID = $OrganismDataSet["VisitID"];

                                        if ($AuthorityId > 0) {
                                            TBLVisits::SetFieldValue($dbConn, "AuthorityID", $VisitID, $AuthorityId);
                                        }
                                    }

                                    $loggerService->logger->info("****ORGANISM: ");
                                    $loggerService->logger->info("OrgInfoID=$OrgInfoId");
                                    $loggerService->logger->info("InsertLogID=$InsertLogID");
                                    $loggerService->logger->info("OrganismDataID=$OrganismDataID");
                                }

                                if ($Key == "OrganismComment") {
                                    $OrgComments = $Value;
                                    $OrgComments = html_entity_decode($OrgComments);

                                    if ($OrgComments != "") {
                                        TBLOrganismData::SetFieldValue($dbConn,"Comments",$OrganismDataID,"$OrgComments");
                                        $loggerService->logger->info("OrgComment=$OrgComments");
                                    }
                                }
                            }

                            //////////////////////////
                            /// PROCESS ORG DATA /////
                            //////////////////////////

                            $ValType = null;

                            foreach ($OrganismDataElement->children() as $Element) { // $Element = <AttributeDataOption>
                                switch ($Element->getName()) {
                                    case "AttributeDataOption":
                                        $AtteTyID = null;
                                        $AttrVal = null;
                                        $AttrValID = null;

                                        foreach($Element->attributes() as $Key => $Value) {
                                            if ($Key == "Value") {
                                                $AttrVal = $Value;
                                            } elseif ($Key == "AttributeDataTypeID") {
                                                $AtteTyID = $Value;
                                            } elseif ($Key == "UnitID") {
                                                $UnitID = $Value;

                                                if ($UnitID == "") {
                                                    $UnitID = null;
                                                }
                                            }

                                            if ($AtteTyID != null && $AttrVal != null && $AttrVal != "-- Select --" && $AttrVal != "") { // error checking for blank entries
                                                $RecSet = LKUAttributeTypes::GetSetFromID($dbConn, $AtteTyID);
                                                $AttName = ''; // for debugging

                                                if ($RecSet) {
                                                    $ValType = $RecSet['ValueType'];
                                                    $AttName = $RecSet['Name'];
                                                }

                                                if ($ValType == 1 || $ValType == 3) { // categorical or int
                                                    $AttrVal = (int) $AttrVal;
                                                } elseif ($ValType == 2) { // float
                                                    $AttrVal = (float) $AttrVal;
                                                } elseif ($ValType == 5) { // string
                                                    $AttrVal = (string) $AttrVal;
                                                    $AttrVal = html_entity_decode($AttrVal);
                                                } elseif ($ValType == 6) { // date/time
                                                    $AttrVal = (string) $AttrVal;
                                                    $AttrVal = date('m/d/Y H:i', strtotime($AttrVal));
                                                }

                                                $loggerService->logger->info("OrgAttribute=$AttName, OrgAttributeValue=$AttrVal");

                                                TBLAttributeData::Insert(
                                                    $dbConn,
                                                    null,
                                                    $OrganismDataID,
                                                    null,
                                                    $AtteTyID,
                                                    $AttrVal,
                                                    null,
                                                    null,
                                                    null,
                                                    null,
                                                    null,
                                                    $UnitID
                                                );
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
                        switch ($SiteCharacteristicsData->getName()) { // was $Element->getName()
                            case "AttributeDataOption":
                                $AtteTyID = null;
                                $AttrVal = null;
                                $AttrValID = null;
                                $ValType = null;

                                foreach($SiteCharacteristicsData->attributes() as $Key => $Value) {
                                    if ($Key == "Value") {
                                        $AttrVal = $Value;
                                    } elseif ($Key == "AttributeDataTypeID") {
                                        $AtteTyID = $Value;
                                    } elseif ($Key == "UnitID") {
                                        $UnitID = $Value;

                                        if ($UnitID == "") {
                                            $UnitID = null;
                                        }
                                    }

                                    if ($AtteTyID != null && $AttrVal != null && $AttrVal != "-- Select --" && $AttrVal != "") {
                                        $RecSet = LKUAttributeTypes::GetSetFromID($dbConn, $AtteTyID);
                                        $AttName = ''; // for debugging

                                        if ($RecSet) {
                                            $ValType = $RecSet['ValueType'];
                                            $AttName = $RecSet['Name'];
                                        }


                                        if ($ValType == 1 || $ValType == 3) { // categorical or int
                                            $AttrVal = (int) $AttrVal;
                                        } elseif ($ValType == 2) { // float
                                            $AttrVal = (float) $AttrVal;
                                        } elseif ($ValType == 5) { // string
                                            $AttrVal = (string) $AttrVal;
                                            $AttrVal = html_entity_decode($AttrVal);
                                        } elseif ($ValType == 6) { // date/time
                                            $AttrVal = (string) $AttrVal;
                                            $AttrVal = date('m/d/Y H:i', strtotime($AttrVal));
                                        }

                                        $loggerService->logger->info("SiteChar=$AttName, SiteValue=$AttrVal");

                                        TBLAttributeData::Insert(
                                            $dbConn,
                                            $VisitID,
                                            null,
                                            null,
                                            $AtteTyID,
                                            $AttrVal,
                                            null,
                                            null,
                                            null,
                                            null,
                                            null,
                                            $UnitID
                                        );
                                    }
                                }

                                break;
                        }
                    }

                    break;
            }
        }

        $loggerService->logger->info("---- END XML Processing ----");

        // ------------------------------------------------------------------------------------
        // PHOTOS
        //-------------------------------------------------------------------------------------

        if (!empty($files)) { // if we were sent pictures...
            // ------------------------------------------------------------------------------------
            // find photos here and process them by relating them to obs and /or organisminfoid...
            //-------------------------------------------------------------------------------------

            $Directory = "/var/www/citsci/inetpub/UserUploads/$UserID/Media/";

            foreach ($files as $photofilename) {
                // Insert new media ID
                $MediaID = TBLMedia::Insert($dbConn, $photofilename, $photofilename, $UserID); // db label path userid; $Database,"$XMLName",$MediaFileName,$ID

                $loggerService->logger->info("***IMAGE Processing: FileName=$photofilename, MediaID=$MediaID, ImagePath=$Directory.$photofilename");

                preg_match('/(?P<name>\w+)_(?P<organism>\w+)_(?P<OrganismInfoID>\d+)_(?P<imagenumber>\w+)/', $photofilename, $matches);

                if (array_key_exists('OrganismInfoID', $matches)) {
                    $OrganismInfoID = $matches["OrganismInfoID"];

                    $SelectString="SELECT \"TBL_OrganismData\".\"ID\" AS \"OrganismDataID\"
					                FROM \"TBL_Visits\"
					                INNER JOIN \"TBL_OrganismData\" ON \"TBL_Visits\".\"ID\" = \"TBL_OrganismData\".\"VisitID\"
					                WHERE (\"TBL_Visits\".\"ID\" = $VisitID) AND (\"TBL_OrganismData\".\"OrganismInfoID\" = $OrganismInfoID)
                                    ORDER BY \"TBL_Visits\".\"VisitDate\" DESC";

                    $stmt = $dbConn->prepare($SelectString);
                    $stmt->execute();

                    $Set = $stmt->fetch();
                    $stmt = null;

                    $OrganismDataID = null;

                    if ($Set) {
                        $OrganismDataID = $Set["OrganismDataID"];
                    }

                    RELMediaToOrganismData::Insert($dbConn, $MediaID, $OrganismDataID, $UserID);
                    $loggerService->logger->info("Organism Image Processed for FileName=$photofilename, MediaID=$MediaID, OrgInfoID=$OrganismInfoID");
                } else {
                    RELMediaToVisit::Insert($dbConn, $MediaID, $VisitID, $UserID);
                    $loggerService->logger->info("VISIT Image Processed for FileName=$photofilename, MediaID=$MediaID");
                }
            }
        }
    }
}
