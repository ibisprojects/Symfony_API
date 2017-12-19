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
use DateTime;
use Classes\DBTable\Upload; 
use Classes\DBTable\Date;

require_once("C:/inetpub/wwwroot/cwis438/Classes/LogFile.php");


class UploadController extends Controller { 

    private $grantType = "authorization_code";
    private $scope = "1"; //user scope
    private $ownerType = "user";		
	

    public function uploadDatasheetsAPIAction(Request $request) {
		
        $returnArray = array('status' => Constants::FAILURE_STATUS, 'message' => "");

        if ($request->getMethod() == 'POST') {
			
			// Set up log file for debugging
			$LogFile="C:/Logs/_MobileAppLog.log";					
			$returnHTML="";
			
            $accessToken = $request->get("Token", null);
			
			file_put_contents($LogFile,"Token=$accessToken\r\n");
			
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
                        file_put_contents($LogFile,"****Begin Processing of App files****\r\n",FILE_APPEND);

                        // Initial Settings
                        $TimeStamp = strtotime(date("Y-m-d H:i:s"));                          
                        $XMLMovedFlag=FALSE;
                        $ImageMovedFlag=TRUE;

                        // get number of images from NumFiles posted params
                        $NumFiles=$_POST["NumFiles"];
						
						file_put_contents($LogFile,"NumFiles=$NumFiles\r\n",FILE_APPEND);
						
						$XMLOriginalFileName=$_FILES['XMLData']['name'];
						
						file_put_contents($LogFile,"XMLFileName=$XMLOriginalFileName\r\n",FILE_APPEND);
                                               
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
							$ObservationPath = "D:/inetpub/UserUploads/$UserID/MobileData/$ObservationName/";  // if on LIVE

							if (!file_exists($ObservationPath))
							{
								mkdir($ObservationPath,0777,TRUE);
							} 

							$FullXMLPath=$ObservationPath.$XMLOriginalFileName;

							// Get XML file and Move it into observation folder
							$XMLMovedFlag=move_uploaded_file($_FILES['XMLData']['tmp_name'],$FullXMLPath);

							// Debugs
							//echo("ObservationName=$ObservationName<br>XMLMovedFlag=$XMLMovedFlag<br>FullXMLFilePath=$ObservationPath$XMLOriginalFileName<br>--------------<br>");
							file_put_contents($LogFile,"ObservationName=$ObservationName\r\nXMLMovedFlag=$XMLMovedFlag\r\nFullXMLFilePath=$ObservationPath$XMLOriginalFileName\r\n----------\r\n",FILE_APPEND);
						}
						else
						{
							//echo("Could not retrieve XML file from POST<br>");
						}

                        // MOVE IMAGE FILES if any                                         

                        if($NumFiles>0)
                        {	                      
                            $FileArray=array();	
							
							file_put_contents($LogFile,"IN PHOTO UPLOAD - before MOVE\r\n",FILE_APPEND);

                            // Move images to Observation folder	
                            Upload::MoveUploadedFiles($NumFiles,$ObservationPath,100000000,$FileArray);  //$Result=Upload::MoveUploadedFiles($NumFiles,$ObservationPath,100000000,$FileArray);
							
							$Directory = "D:/inetpub/UserUploads/$UserID/MobileData/$ObservationName/";
   
							$handle=opendir($Directory);
							
							file_put_contents($LogFile,"IN PHOTO UPLOAD - photo directory: $Directory\r\n",FILE_APPEND);
							
							while (false!==($photofilename=readdir($handle))) 
							{
								//$Extension=GetFileExtensionFromFilePath($Directory.$photofilename);
								$Extension=pathinfo($Directory.$photofilename, PATHINFO_EXTENSION);

								$Extension=strtolower($Extension); 	

								if (($Extension=="jpg")||($Extension=="png")||($Extension=="gif")||($Extension=="jpeg")||($Extension=="tif")) // if the entries are images... (this excludes the . and .. subfolders)
								{																	
									if (!(file_exists($Directory."/_thumbnails"))) {mkdir($Directory."/_thumbnails",0777,TRUE);};
									if (!(file_exists($Directory."/_display"))) {mkdir($Directory."/_display",0777,TRUE);};
									if (!(file_exists($Directory."/_print"))) {mkdir($Directory."/_print",0777,TRUE);};						

									copy("$Directory/$photofilename","$Directory/_thumbnails/$photofilename");
									copy("$Directory/$photofilename","$Directory/_display/$photofilename");
									copy("$Directory/$photofilename","$Directory/_print/$photofilename");
								}
							}
						}
						
						// Send file path to cwis438/webservices/InsertAppData.php for processing
						
						$FullXMLPath=urlencode($FullXMLPath);
						
						file_put_contents($LogFile,"\r\nBEFORE EXECUTE: \r\nFullXMLPath=$FullXMLPath\r\n NumFiles=$NumFiles\r\n USERID=$UserID\r\n ObservationName=$ObservationName\r\n",FILE_APPEND);
						
						$CommandString="\"C:/Program Files (x86)/PHP/v5.5.32/php.exe\" C:/Inetpub/wwwroot/cwis438/WebServices/InsertAppData.php $FullXMLPath $UserID $ObservationName $NumFiles >> C:/logs/_System.log 2>&1 &\"";
						
						exec($CommandString);  
    
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
            $returnArray["returnHTML"] =$returnHTML;
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
   
}
