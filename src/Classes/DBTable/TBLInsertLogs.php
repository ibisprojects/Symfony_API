<?php

namespace Classes\DBTable;

use Classes\TBLDBTables;

define("NOT_SPECIFIED", "");
define("INSERT_LOG_UNKNOWN", 0);
define("INSERT_LOG_CLASSIFICATION", 1);
define("INSERT_LOG_SURVEY_ADD", 2);
define("INSERT_LOG_AREA_ADD", 3);
define("INSERT_LOG_MEDIA", 4);
define("INSERT_LOG_TSN", 5);
define("INSERT_LOG_UPLOADED_RASTER", 6);
define("INSERT_LOG_GENERATED_RASTER", 7);
define("INSERT_LOG_IDSOURCE_FILE", 8);
define("INSERT_LOG_GISIN_TO_IBIS_COPY", 9);
define("INSERT_LOG_FILE", 10);
define("INSERT_LOG_FORM", 11); // e.g., datasheets, data entry forms, etc.
define("INSERT_LOG_APP", 12); // e.g., CitSciMobile

$InsertLogTypeStrings = array("", "Classification", "Survey Addition", "Area Addition", "Media", "Organism Information",
    "Uploaded Raster", "Generated Raster", "IDSource File", "GISIN To IBIS Copy", "File Upload", "Datasheet", "Mobile App");

$InsertLogTypesFromJobTypes = array(0,
    INSERT_LOG_SURVEY_ADD,
    INSERT_LOG_CLASSIFICATION,
    INSERT_LOG_AREA_ADD,
    0,
    0,
    0,
    0,
    0,
    0,
    0,
    0);

//*********************************************************************************
//	Definitions
//*********************************************************************************

class TBLInsertLogs {

    public static function GetFieldValue($dbConn, $FieldName, $ID, $Default = 0) {
        $Result = TBLDBTables::GetFieldValue($dbConn, "TBL_InsertLogs", $FieldName, $ID, $Default);

        return($Result);
    }

    public static function Insert($dbConn, $Type, $UserSessionID = null, $Name = null, $FilePath = NOT_SPECIFIED, $MetadataID = NOT_SPECIFIED, $UserID = NOT_SPECIFIED, $FormID = NOT_SPECIFIED, $ProjectID = NOT_SPECIFIED, $WebsiteID = NOT_SPECIFIED) {
        //
        // Inputs:
        //	$Type - Type of insert log being created
        //	$UserSessionID - User session this is matched to.  May be null for system operations
        //	
        if ($UserSessionID == 0)
            $UserSessionID = null;

        // insert the record

        $InsertLogID = -1;

        $ExecString = "EXEC insert_TBL_InsertLogs " . $Name . "";

        $stmt = $dbConn->prepare($ExecString);
        $stmt->execute();
        $InsertLogID = $dbConn->lastInsertId();

        // update the record

        if (($UserID === NOT_SPECIFIED) || ($UserID == 0))
            return -1;


        $UpdateString = "UPDATE TBL_InsertLogs " .
                "SET UserSessionID=" . $UserSessionID . ", " .
                "Type='$Type', " .
                "DateUploaded=(GetDate()), " .
                "UploaderID=" . $UserID . " ";

        TBLDBTables::AddStringUpdate($UpdateString, 'FilePath', $FilePath);
        TBLDBTables::AddIntUpdate($UpdateString, 'FormID', $FormID);
        TBLDBTables::AddIntUpdate($UpdateString, 'ProjectID', $ProjectID);

        if ($WebsiteID !== NOT_SPECIFIED)
            TBLDBTables::AddIntUpdate($UpdateString, 'WebsiteID', $WebsiteID);

        $UpdateString.="WHERE ID=$InsertLogID";

        $stmt = $dbConn->prepare($UpdateString);
        $stmt->bindValue("UserID", $UserID);
        $stmt->execute();


        TBLInsertLogs::Update($dbConn, $InsertLogID, $Name, $MetadataID);

        return($InsertLogID);
    }

    public static function Update($dbConn, $InsertLogID, $Name, $MetadataID = NOT_SPECIFIED) {

        if ($MetadataID == NOT_SPECIFIED)
            $MetadataID = null;

        $UpdateString = "UPDATE TBL_InsertLogs ";

        TBLDBTables::AddStringUpdate($UpdateString, 'Name', $Name);
        TBLDBTables::AddIntUpdate($UpdateString, 'MetadataID', $MetadataID);

        $UpdateString = $UpdateString . "WHERE ID=" . $InsertLogID;

        $UpdateString.="WHERE ID=$InsertLogID";

        $stmt = $dbConn->prepare($UpdateString);
        //$stmt->bindValue("UserID", $UserID);
        $stmt->execute();

        return($InsertLogID);
    }

}

?>
