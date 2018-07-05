<?php

namespace Classes\DBTable;

use Classes\TBLDBTables;
use Classes\Utilities\SQL;
use API\Classes\Constants;

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
        $FilePath = Constants::NOT_SPECIFIED,
        $MetadataID = Constants::NOT_SPECIFIED,
        $UserID = Constants::NOT_SPECIFIED,
        $FormID = Constants::NOT_SPECIFIED,
        $ProjectID = Constants::NOT_SPECIFIED,
        $WebsiteID = Constants::NOT_SPECIFIED
    )
    {
        if (($UserID === Constants::NOT_SPECIFIED) ||  empty($UserID))
            return -1;

        //
        // Inputs:
        //	$Type - Type of insert log being created
        //	$UserSessionID - must be null for this operation
        //
        $UserSessionID = null;

        if ($WebsiteID===Constants::NOT_SPECIFIED || empty($WebsiteID)) $WebsiteID = null;

        if ($FilePath===Constants::NOT_SPECIFIED || empty($FilePath)) $FilePath = null;

        if ($FormID===Constants::NOT_SPECIFIED || empty($FormID)) $FormID = null;

        if ($ProjectID===Constants::NOT_SPECIFIED || empty($ProjectID)) $ProjectID = null;

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

    public static function Update($dbConn, $InsertLogID, $Name, $MetadataID = Constants::NOT_SPECIFIED) {
        if ($MetadataID == Constants::NOT_SPECIFIED || empty($MetadataID))
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
