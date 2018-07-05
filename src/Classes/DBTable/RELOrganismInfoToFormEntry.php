<?php

namespace Classes\DBTable;

//**************************************************************************************
// FileName: REL_OrganismInfoToFormEntry.php
// Rs
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

use Classes\TBLDBTables;

//*********************************************************************************
//	Definitions
//*********************************************************************************

class RELOrganismInfoToFormEntry {

    //******************************************************************************
    // Static Database functions
    //******************************************************************************

    public static function GetPickListSetFromFormEntryID($dbConn, $FormEntryID, $SortOption) {
        /*
          $SelectString="SELECT TBL_OrganismInfos.Name, REL_OrganismInfoToFormEntry.OrganismInfoID, TBL_FormEntries.ID
          FROM TBL_FormEntries INNER JOIN
          REL_OrganismInfoToFormEntry ON TBL_FormEntries.ID = REL_OrganismInfoToFormEntry.FormEntryID FULL OUTER JOIN
          TBL_OrganismInfos ON REL_OrganismInfoToFormEntry.OrganismInfoID = TBL_OrganismInfos.ID
          WHERE (TBL_FormEntries.ID = $FormEntryID)
          ORDER BY TBL_OrganismInfos.Name";
         */
        $SelectString = "SELECT \"REL_OrganismInfoToFormEntry\".\"OrganismInfoID\", \"TBL_FormEntries\".\"ID\", \"TBL_TaxonUnits\".\"UnitName1\", \"TBL_TaxonUnits\".\"UnitName2\",
                             CONCAT(\"TBL_TaxonUnits\".\"UnitName1\", ' ', \"TBL_TaxonUnits\".\"UnitName2\", ' (', \"TBL_OrganismInfos\".\"Name\", ')') AS \"Name\"
                        FROM \"REL_OrganismInfoToTSN\" INNER JOIN
                              \"TBL_OrganismInfos\" ON \"REL_OrganismInfoToTSN\".\"OrganismInfoID\" = \"TBL_OrganismInfos\".\"ID\" INNER JOIN
                              \"TBL_TaxonUnits\" ON \"REL_OrganismInfoToTSN\".\"TSN\" = \"TBL_TaxonUnits\".\"TSN\" FULL OUTER JOIN
                              \"TBL_FormEntries\" INNER JOIN
                              \"REL_OrganismInfoToFormEntry\" ON \"TBL_FormEntries\".\"ID\" = \"REL_OrganismInfoToFormEntry\".\"FormEntryID\" ON
                              \"TBL_OrganismInfos\".\"ID\" = \"REL_OrganismInfoToFormEntry\".\"OrganismInfoID\"
                        WHERE (\"TBL_FormEntries\".\"ID\" = :FormEntryID) ";
        //ORDER BY Name";
        $OrderByString = " ORDER BY \"TBL_TaxonUnits\".\"UnitName1\",\"TBL_TaxonUnits\".\"UnitName2\",\"TBL_TaxonUnits\".\"UnitName3\"";
        if ($SortOption != "0") {
            $OrderByString = " ORDER BY \"TBL_OrganismInfos\".\"Name\"";
        }
        $SelectString .= $OrderByString;

        $stmt = $dbConn->prepare($SelectString);
        $stmt->bindValue("FormEntryID", $FormEntryID);
        $stmt->execute();
        $tempArray = array();
        while ($OrganismInfo = $stmt->fetch()) {
            $tempArray []= array("ID"=>$OrganismInfo["OrganismInfoID"],"Name"=>$OrganismInfo["Name"]);
        }
        $stmt->execute();
        return $tempArray;
    }
}

?>
