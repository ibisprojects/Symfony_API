<?php

//**************************************************************************************
// FileName: TBL_Media.php
// Owner:
// Author:
// Notes: ...
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

namespace Classes\DBTable;

use Classes\Utilities\SQL;
use Classes\TBLDBTables;

//**************************************************************************************
// Class Definition
//**************************************************************************************
class TBLMedia
{
    public static function GetSetFromID($dbConn, $MediaID)
    {
        $MediaID = SQL::SafeInt($MediaID);

        $SelectString = "SELECT * ".
            "FROM \"TBL_Media\" ".
            "WHERE \"ID\"=".$MediaID."";

        $stmt = $dbConn->prepare($SelectString);
        $stmt->execute();

        return $stmt->fetch();
    }

    public static function Insert($dbConn, $Label = 'Untitled', $FilePath, $UserID = null, $Type = 1)
    {
        // find the new order number (just puts it at the last Media overall)

        $SelectString="SELECT MAX(\"OrderNumber\") AS \"OrderNumber\"
			FROM \"TBL_Media\"
			LIMIT 1";

        $stmt = $dbConn->prepare($SelectString);
        $stmt->execute();

        $Set = $stmt->fetch();
        $stmt = null;

        $OrderNumber = 1;

        if ($Set)
            $OrderNumber = (int) $Set["OrderNumber"] + 1;

        $FilePath = str_replace("'", "", $FilePath);

        $ExecString = 'INSERT INTO "TBL_Media" ("Name", "Type", "OrderNumber", "FilePath"'.
            ($UserID != null ? ', "PersonID"' : '').
            ") VALUES ('$Label', $Type, $OrderNumber, '$FilePath'".
            ($UserID != null ? ', '.$UserID : '').')';

        $stmt = $dbConn->prepare($ExecString);
        $stmt->execute();

        return $dbConn->lastInsertId('TBL_Media_ID_seq');
    }

    public static function Delete($dbConn, $MediaID)
    {
        // delete any files associated with the media (this needs to be run by a trigger in T-SQL)

        if ($set = TBLMedia::GetSetFromID($dbConn, $MediaID)) {
            $FilePath = $set["FilePath"];
            $PersonID = $set["PersonID"];

            $FullPath = "/var/www/citsci/inetpub/UserUploads/$PersonID/Media/";

            // delete all versions

            $ImagePath = "{$FullPath}_print/{$FilePath}";

            if (file_exists($ImagePath))
                unlink($ImagePath);

            $ImagePath = "{$FullPath}_display/{$FilePath}";

            if (file_exists($ImagePath))
                unlink($ImagePath);

            $ImagePath = "{$FullPath}_thumbnails/{$FilePath}";

            if (file_exists($ImagePath))
                unlink($ImagePath);

            $ImagePath = "{$FullPath}{$FilePath}";

            if (file_exists($ImagePath))
                unlink($ImagePath);
        }

        // delete the TBL_Media entry (this will cause the REL_Media... tables to be updated

        TBLDBTables::Delete($dbConn, "TBL_Media", $MediaID);
    }
}
