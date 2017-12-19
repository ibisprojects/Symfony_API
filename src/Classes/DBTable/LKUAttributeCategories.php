<?php

//**************************************************************************************
// FileName: LKU_AttributeCategories.php
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

define("ATTRIBUTE_CATEGORY_ORGANISM_DATA",1); // this is the ID of the category
define("ATTRIBUTE_CATEGORY_ABIOTIC",2);
define("ATTRIBUTE_CATEGORY_SOIL_CHEMISTRY",3);
define("ATTRIBUTE_CATEGORY_SOIL_TYPE",4);
define("ATTRIBUTE_CATEGORY_BIOTIC",5);
define("ATTRIBUTE_CATEGORY_AREA",6);

//**************************************************************************************
// Class Definition
//**************************************************************************************

class LKU_AttributeCategories
{
	//**********************************************************************************
    // Basic database functions
	//**********************************************************************************

	public static function GetSet($Database,$DatabaseTableID=null)
    {
    	$SelectString="SELECT * ".
			"FROM LKU_AttributeCategories ";

		if ($DatabaseTableID!==null) $SelectString.=" WHERE DatabaseTableID=$DatabaseTableID ";

		$SelectString.="ORDER BY ID";

		//DebugWriteln("SelectString=$SelectString");

		$Set=$Database->Execute($SelectString);

		return($Set);
    }

	public static function GetSetFromID($Database,$ID)
    {
    	$SelectString="SELECT * ".
			"FROM LKU_AttributeCategories ".
			"WHERE ID='$ID'";

//		DebugWriteln("SelectString=$SelectString");

		$Set=$Database->Execute($SelectString);

		return($Set);
    }

    public static function GetNameForID($Database,$ID)
    {
    	$Name="Untitled";

    	$Set=LKU_AttributeCategories::GetSetFromID($Database,$ID);

    	if ($Set->FetchRow()) $Name=$Set->Field("Name");

		return($Name);
    }

	public static function Delete($Database,$AttributeCategoriesID)
	{
		TBL_DBTables::Delete($Database,"LKU_AttributeCategories",$AttributeCategoriesID);
	}

	//**********************************************************************************
    // Additional database functions
	//**********************************************************************************
}
?>
