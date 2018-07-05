<?php

namespace Classes\DBTable;

use Classes\TBLDBTables;

class TBLGISINSpeciesStatuses
{
    public static function Delete($dbConn, $ID = null)
    {
        TBLDBTables::Delete($dbConn, "TBL_GISIN_SpeciesStatuses", $ID);
    }
}
