<?php

namespace Classes\DBTable;

class RELMediaToVisit {
    public static function Insert($dbConn, $MediaID, $VisitID, $PersonID = null)
    {
        $ID = null;

        // see if the Media is already associated with the project

        $SelectString = "SELECT * ".
            "FROM \"REL_MediaToVisit\" ".
            "WHERE (\"MediaID\"='$MediaID') ".
            "AND \"VisitID\"='$VisitID'";

        $stmt = $dbConn->prepare($SelectString);
        $stmt->execute();

        $Set = $stmt->fetch();
        $stmt = null;

        if ($Set) {
            $ID = $Set["ID"];
        } else {
            // insert the new record and get it's ID

            $ExecString="INSERT INTO \"REL_MediaToVisit\" (\"MediaID\", \"VisitID\") VALUES ('$MediaID','$VisitID')";

            $stmt = $dbConn->prepare($ExecString);
            $stmt->execute();

            $ID = $dbConn->lastInsertId('"REL_MediaToVisit_ID_seq"');
            $stmt = null;

            // find the next order number

            $SelectString = "SELECT MAX(\"OrderNumber\") AS \"OrderNumber\" ".
                "FROM \"REL_MediaToVisit\" ".
                "WHERE \"VisitID\"=$VisitID ".
                "LIMIT 1";

            $stmt = $dbConn->prepare($SelectString);
            $stmt->execute();

            $Set = $stmt->fetch();
            $stmt = null;

            $OrderNumber = 1;

            if ($Set)
                $OrderNumber = (int) $Set["OrderNumber"] + 1;

            // set the order number in the new record

            $UpdateString = "UPDATE \"REL_MediaToVisit\" ".
                "SET \"OrderNumber\"=$OrderNumber, ".
                "\"PersonID\"=$PersonID ".
                "WHERE \"ID\"=$ID";

            $stmt = $dbConn->prepare($UpdateString);
            $stmt->execute();
            $stmt = null;;
        }

        return $ID;
    }
}
