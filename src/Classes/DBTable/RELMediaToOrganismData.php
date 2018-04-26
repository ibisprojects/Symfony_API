<?php

namespace Classes\DBTable;

class RELMediaToOrganismData {
    public static function Insert($dbConn, $MediaID, $OrganismDataID, $PersonID = null)
    {
        $ID = null;

        // see if the Media is already associated with the project

        $SelectString = "SELECT * ".
            "FROM \"REL_MediaToOrganismData\" ".
            "WHERE (\"MediaID\"='$MediaID') ".
            "AND \"OrganismDataID\"='$OrganismDataID'";

        $stmt = $dbConn->prepare($SelectString);
        $stmt->execute();

        $Set = $stmt->fetch();
        $stmt = null;

        if (!empty($Set["ID"])) {
            $ID = $Set["ID"];
        } else {
            // insert the new record and get it's ID

            $ExecString = "INSERT INTO \"REL_MediaToOrganismData\" (\"MediaID\", \"OrganismDataID\") VALUES ($MediaID, $OrganismDataID)";

            $stmt = $dbConn->prepare($ExecString);
            $stmt->execute();

            $ID = $dbConn->lastInsertId('"REL_MediaToOrganismData_ID_seq"');
            $stmt = null;

            // find the next order number

            $SelectString="SELECT \"OrderNumber\" ".
                "FROM \"REL_MediaToOrganismData\" ".
                "WHERE \"OrganismDataID\"=$OrganismDataID ".
                "ORDER BY \"OrderNumber\" DESC LIMIT 1";

            $stmt = $dbConn->prepare($SelectString);
            $stmt->execute();

            $Set = $stmt->fetch();
            $stmt = null;

            $OrderNumber = 1;

            if (!empty($Set["OrderNumber"]))
                $OrderNumber = (int) $Set["OrderNumber"] + 1;
trigger_error(print_r($OrderNumber, 1));
            // set the order number in the new record

            $UpdateString = "UPDATE \"REL_MediaToOrganismData\" ".
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
