<?php

namespace Classes\DBTable;

use Classes\TBLDBTables;
use Classes\Utilities\SQL;

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

//*********************************************************************************
//	Definitions
//*********************************************************************************

class TBLInsertLogs {
    public static function GetFieldValue($dbConn, $FieldName, $ID, $Default = 0) {
        $Result = TBLDBTables::GetFieldValue($dbConn, "TBL_InsertLogs", $FieldName, $ID, $Default);

        return($Result);
    }

    public static function SetFieldValue($Database,$FieldName,$ID,$Value)
    {
        if (($FieldName=="MetadataID")&&($Value===0)) $Value=null;
        TBLDBTables::SetFieldValue($Database,"TBL_InsertLogs",$FieldName,$ID,$Value);
    }

    public static function Insert(
        $dbConn,
        $Type,
        $UserSessionID = null,
        $Name = null,
        $FilePath = NOT_SPECIFIED,
        $MetadataID = NOT_SPECIFIED,
        $UserID = NOT_SPECIFIED,
        $FormID = NOT_SPECIFIED,
        $ProjectID = NOT_SPECIFIED,
        $WebsiteID = NOT_SPECIFIED
    )
    {
        if (($UserID === NOT_SPECIFIED) ||  empty($UserID))
            return -1;

        //
        // Inputs:
        //	$Type - Type of insert log being created
        //	$UserSessionID - must be null for this operation
        //
        $UserSessionID = null;

        if ($WebsiteID===NOT_SPECIFIED || empty($WebsiteID)) $WebsiteID = null;

        if ($FilePath===NOT_SPECIFIED || empty($FilePath)) $FilePath = null;

        if ($FormID===NOT_SPECIFIED || empty($FormID)) $FormID = null;

        if ($ProjectID===NOT_SPECIFIED || empty($ProjectID)) $ProjectID = null;

        // insert the record

        $ExecString='INSERT INTO "TBL_InsertLogs" (
				"Name",
				"UserSessionID",
				"Type",
				"DateUploaded",
				"UploaderID",
				"FilePath",
				"FormID",
				"ProjectID",
				"WebsiteID"
			) VALUES ('.
                SQL::GetString($Name).', '.
                SQL::GetInt($UserSessionID).', '.
                $Type.', 
                now(), '.
                $UserID.', '.
                SQL::GetString($FilePath).', '.
                SQL::GetInt($FormID).', '.
                SQL::GetInt($ProjectID).', '.
                SQL::GetInt($WebsiteID).
            ')';

        $stmt = $dbConn->prepare($ExecString);
        $stmt->execute();

        $InsertLogID=$dbConn->lastInsertId('"TBL_InsertLogs_ID_seq"');

        TBLInsertLogs::Update($dbConn,$InsertLogID,$Name,$MetadataID);

        return($InsertLogID);
    }

    public static function Update($dbConn, $InsertLogID, $Name, $MetadataID = NOT_SPECIFIED) {
        if ($MetadataID == NOT_SPECIFIED || empty($MetadataID))
            $MetadataID = null;

        $UpdateString = "UPDATE \"TBL_InsertLogs\" ";

        TBLDBTables::AddStringUpdate($UpdateString, 'Name', $Name);
        TBLDBTables::AddIntUpdate($UpdateString, 'MetadataID', $MetadataID);

        $UpdateString .= "WHERE \"ID\"=$InsertLogID";

        $stmt = $dbConn->prepare($UpdateString);
        $stmt->execute();

        return($InsertLogID);
    }
}

?>
