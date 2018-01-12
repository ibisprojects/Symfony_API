<?php

//**************************************************************************************
// FileName: REL_AreaToArea.php
//
//	Manages relationships between areas.
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

class RELAreaToArea
{
    public static function UpdateRelationships(
        $dbConn,
        $TargetAreaID,
        $MatchAreaTypeID,
        &$MatchIDs = array(),
        &$MatchNames = array(),
        &$MatchCodes = array(),
        &$MatchRelationships = array()
    )
        //
        //	Updates the relationships for the specified TargetAreaID (Area2ID) and a
        //	MatchAreaTypeID (Area1 Types)
        //
    {
        // delete any existing relationships (this can be done because the relationships are leave nodes in the database)

        $DeleteString="DELETE ".
            "FROM \"REL_AreaToArea\" ".
            "WHERE \"Area2ID\"=$TargetAreaID ".
            "AND \"Area1ID\" IN ".
            "(SELECT \"ID\" FROM \"TBL_Areas\" WHERE \"AreaSubtypeID\"=$MatchAreaTypeID)";

        $stmt = $dbConn->prepare($DeleteString);
        $stmt->execute();

        $stmt = null;

        // get the relationships between the current target and the match type

        $SelectString = "SELECT \"TBL_Areas\".\"ID\", \"AreaName\", \"Code\"
			FROM \"TBL_Areas\"
			INNER JOIN \"TBL_SpatialLayerData\" ON \"TBL_SpatialLayerData\".\"AreaID\"=\"TBL_Areas\".\"ID\"
			WHERE \"AreaSubtypeID\"=$MatchAreaTypeID
			AND ST_Distance(\"GeometryData\", (SELECT \"GeometryData\" FROM \"TBL_SpatialLayerData\" WHERE \"AreaID\"=$TargetAreaID))=0";

        $stmt = $dbConn->prepare($SelectString);
        $stmt->execute();

        $MatchNumComponents = 0;

        while ($row = $stmt->fetch()) {
            $MatchIDs[$MatchNumComponents] = $row["ID"];
            $MatchCodes[$MatchNumComponents] = $row["Code"];
            $MatchNames[$MatchNumComponents] = $row["AreaName"];
            $MatchRelationships[$MatchNumComponents] = 1; // jjg - not using relationsihps any more

            // add the relationship to the database

            $insertString = "INSERT INTO \"REL_AreaToArea\" (\"Area1ID\", \"Area2ID\", \"Relationship\") ".
                "VALUES ($MatchIDs[$MatchNumComponents], $TargetAreaID, $MatchRelationships[$MatchNumComponents])";

            $stmti = $dbConn->prepare($insertString);
            $stmti->execute();

            $stmti = null;

            $MatchNumComponents++;
        }

        $stmt = null;

        return $MatchNumComponents;
    }
}
