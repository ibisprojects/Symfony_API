<?php

namespace Classes\DBTable;
//**************************************************************************************
// FileName: TBL_FormEntries.php
// Author: GJN
// Owner: GJN
// Notes: This static class interacts with the database table: TBL_FormEntries
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


//*********************************************************************************
//	Definitions
//*********************************************************************************

define("TBL_FORMENTRIES_HOW_SPECIFIED_ENTERED",1);
define("TBL_FORMENTRIES_HOW_SPECIFIED_LIST",2);
define("TBL_FORMENTRIES_HOW_SPECIFIED_SPECIFIED",3);
define("TBL_FORMENTRIES_HOW_SPECIFIED_HIDDEN",4);


define("TBL_SPECIRES_SORTBY_SCINAME",0);
define("TBL_SPECIRES_SORTBY_COMMNAME",1);
class TBL_FormEntries extends TBL_DBTables
{		
	//******************************************************************************
    // Static Database functions
    //******************************************************************************
   
    public static function GetSet($Database,$FormID=null,$DatabaseTableID=null) // $OrganismInfoID=null,$ControlAgentID=null
    {
    	$SelectString="SELECT * FROM TBL_FormEntries ";
		
    	if ($FormID!==null) TBL_DBTables::AddWhereClause($SelectString,"FormID=$FormID");
    	
    	if ($DatabaseTableID==DATABASE_TABLE_ORGANISM_INFOS) TBL_DBTables::AddWhereClause($SelectString,"(OrganismInfoID IS NOT NULL)");
    	if ($DatabaseTableID==DATABASE_TABLE_CONTROL_AGENTS) TBL_DBTables::AddWhereClause($SelectString,"(ControlAgentID IS NOT NULL)");
    	
		$FormEntrySet=$Database->Execute($SelectString);
	
		return($FormEntrySet);
    }
     
    public static function GetSetFromID($Database,$FormEntryID)
    {
    	$SelectString="SELECT * FROM TBL_FormEntries ".
    		"WHERE ID = $FormEntryID";
    		
		$FormEntrySet=$Database->Execute($SelectString);
	
		return($FormEntrySet);
    }
    
    public static function GetSetFromFormIDNullParentFormEntries($Database,$FormID,$DatabaseTableID) // $DatabaseFieldID=null,$DatabaseTableID=null
    {
    	if ($FormID==0) $FormID=-999; // GJN, for now just always ensure that if we have formid set to 0 return an empty recordset
        
        $SelectString="SELECT * FROM TBL_FormEntries ".
    		"WHERE (FormID = $FormID) ".
    		"AND (ParentFormEntryID IS NULL) ";
    	
    	if ($DatabaseTableID==DATABASE_TABLE_ORGANISM_INFOS)
    	{
    		TBL_DBTables::AddWhereClause($SelectString,"(OrganismInfoID IS NOT NULL)");
    	}
    	else if ($DatabaseTableID==DATABASE_TABLE_CONTROL_AGENTS)
    	{
    		TBL_DBTables::AddWhereClause($SelectString,"(ControlAgentID IS NOT NULL)");
    	}
    	else if ($DatabaseTableID==DATABASE_TABLE_VISITS)
    	{
    		$SelectString="SELECT * FROM TBL_FormEntries ".
    			"WHERE (FormID = $FormID) AND (ParentFormEntryID IS NULL) AND (OrganismInfoID IS NULL) AND (ControlAgentID IS NULL) AND (Picklist IS NULL) AND (AllOrganismPicklist IS NULL) ".
                    "ORDER BY OrderNumber"; 
    	}
           
    	
    	//if ($DatabaseTableID==DATABASE_TABLE_ORGANISM_INFOS) DebugWriteln("SelectString=$SelectString");
    	//if ($DatabaseTableID==DATABASE_TABLE_VISITS) DebugWriteln("Site chars SelectString=$SelectString");
    	
		$FormEntrySet=$Database->Execute($SelectString);
	
		return($FormEntrySet);
    }
    
    public static function GetSpeciesPickListEntriesFromFormID($Database,$FormID)
    {
       $SelectString="SELECT * FROM TBL_FormEntries 
    		WHERE (FormID=$FormID) AND (Picklist=1)";
       
       $FormEntrySet=$Database->Execute($SelectString);
       
       return($FormEntrySet);
    }
    
    public static function GetOrganismFormEntries($Database,$FormID)
    {
        $SelectString="SELECT ID, FormID, AttributeTypeID, Name, HowSpecified, ParentFormEntryID, OrderNumber, UnitID, SubPlotTypeID, OrganismInfoID, 
                AttributeValueID, Picklist, AllOrganismPicklist 
            FROM TBL_FormEntries
            WHERE (FormID = $FormID) AND (ParentFormEntryID IS NULL) AND (OrganismInfoID IS NOT NULL) OR
                (FormID = $FormID) AND (ParentFormEntryID IS NULL) AND (Picklist = 1) OR
				(FormID = $FormID) AND (ParentFormEntryID IS NULL) AND (AllOrganismPicklist = 1)";
        
        $FormEntrySet=$Database->Execute($SelectString);
        
        return($FormEntrySet);
    }
    
    
    
    public static function GetOrganismInfoPicklistFormEntries($Database,$FormID)  // gets predefined org picklist (see GetAllOrganismInfoPicklistFormEntries for all org picklist)
    {
        if ($FormID==0) $FormID=-999; // GJN, for now just always ensure that if we have formid set to 0 return an empty recordset
    
        $SelectString="SELECT ID
                        FROM TBL_FormEntries
                        WHERE (FormID = $FormID) AND (Picklist = 1)";
         
        $FormEntrySet=$Database->Execute($SelectString);
    
        return($FormEntrySet);
    }  
	
	public static function GetAllOrganismInfoPicklistFormEntries($Database,$FormID)  // gets all org picklist
    {
        if ($FormID==0) $FormID=-999; // GJN, for now just always ensure that if we have formid set to 0 return an empty recordset
    
        $SelectString="SELECT ID
                        FROM TBL_FormEntries
                        WHERE (FormID = $FormID) AND (AllOrganismPicklist = 1)";
         
        $FormEntrySet=$Database->Execute($SelectString);
    
        return($FormEntrySet);
    }   
                   
    public static function GetOrganismInfoSetFromFormID($Database,$FormID) // $DatabaseFieldID=null,$DatabaseTableID=null
    {
        if ($FormID==0) $FormID=-999; // GJN, for now just always ensure that if we have formid set to 0 return an empty recordset
    
        $SelectString="SELECT TBL_FormEntries.ID, TBL_FormEntries.FormID, TBL_FormEntries.AttributeTypeID, TBL_FormEntries.Name, TBL_FormEntries.HowSpecified, 
                          TBL_FormEntries.ParentFormEntryID, TBL_FormEntries.OrderNumber, TBL_FormEntries.UnitID, TBL_FormEntries.DatabaseTableID, TBL_FormEntries.SubPlotTypeID, 
                          TBL_FormEntries.OrganismInfoID, TBL_FormEntries.ControlAgentID, TBL_FormEntries.AttributeValueID, TBL_OrganismInfos.Name AS OrganismInfoName
                      FROM TBL_FormEntries INNER JOIN
                          TBL_OrganismInfos ON TBL_FormEntries.OrganismInfoID = TBL_OrganismInfos.ID
                      WHERE (TBL_FormEntries.FormID = $FormID) AND (TBL_FormEntries.ParentFormEntryID IS NULL) AND (TBL_FormEntries.OrganismInfoID IS NOT NULL) ";
        
        //DebugWriteln("SelectString=$SelectString");
         
        $FormEntrySet=$Database->Execute($SelectString);
    
        return($FormEntrySet);
    }
    
    public static function GetFormEntriesFromParentFormEntryID($Database,$ParentFormEntryID) // $DatabaseFieldID=null,$DatabaseTableID=null
    {
        //if ($FormID==0) $FormID=-999; // GJN, for now just always ensure that if we have formid set to 0 return an empty recordset
    
        $SelectString="SELECT TBL_FormEntries.ID, TBL_FormEntries.FormID, TBL_FormEntries.ParentFormEntryID, TBL_FormEntries.OrganismInfoID, TBL_FormEntries.AttributeTypeID, 
                      LKU_AttributeTypes.Name AS AttributeTypeName, TBL_FormEntries.AttributeValueID, TBL_FormEntries.UnitID
                FROM TBL_FormEntries INNER JOIN
                      LKU_AttributeTypes ON TBL_FormEntries.AttributeTypeID = LKU_AttributeTypes.ID
                WHERE (TBL_FormEntries.ParentFormEntryID = $ParentFormEntryID)";
        
        //DebugWriteln("SelectString=$SelectString");
         
        $FormEntrySet=$Database->Execute($SelectString);
    
        return($FormEntrySet);
    }
    
    public static function GetSiteCharacteristicsSetFromFormID($Database,$FormID) // $DatabaseFieldID=null,$DatabaseTableID=null
    {
        if ($FormID==0) $FormID=-999; // GJN, for now just always ensure that if we have formid set to 0 return an empty recordset
    
        $SelectString="SELECT TBL_FormEntries.ID, TBL_FormEntries.FormID, TBL_FormEntries.AttributeTypeID, TBL_FormEntries.ParentFormEntryID, TBL_FormEntries.AttributeValueID, 
                          LKU_AttributeTypes.Name AS AttributeTypeName, TBL_FormEntries.UnitID
                      FROM TBL_FormEntries INNER JOIN
                          LKU_AttributeTypes ON TBL_FormEntries.AttributeTypeID = LKU_AttributeTypes.ID
                      WHERE (TBL_FormEntries.FormID = $FormID) AND (TBL_FormEntries.OrganismInfoID IS NULL) AND (TBL_FormEntries.ParentFormEntryID IS NULL)";
    
        //DebugWriteln("SelectString=$SelectString");
         
        $FormEntrySet=$Database->Execute($SelectString);
    
        return($FormEntrySet);
    }
    
    public static function CountSetFromFormIDNullParentFormEntries($Database,$FormID,$DatabaseTableID) // $DatabaseFieldID=null,$DatabaseTableID=null
    {
        if ($FormID==0) $FormID=-999; // GJN, for now just always ensure that if we have formid set to 0 return an empty recordset
        
        $SelectString="SELECT COUNT(*) AS NumFormEntries FROM TBL_FormEntries ".
                "WHERE ((FormID = $FormID) ".
                "AND (ParentFormEntryID IS NULL)) ";
         
        if ($DatabaseTableID==DATABASE_TABLE_ORGANISM_INFOS)
        {
            TBL_DBTables::AddWhereClause($SelectString,"(OrganismInfoID IS NOT NULL)");
        }
        else if ($DatabaseTableID==DATABASE_TABLE_CONTROL_AGENTS)
        {
            TBL_DBTables::AddWhereClause($SelectString,"(ControlAgentID IS NOT NULL)");
        }
        else if ($DatabaseTableID==DATABASE_TABLE_VISITS)
        {
            $SelectString="SELECT * FROM TBL_FormEntries ".
                    "WHERE (FormID = $FormID) AND (ParentFormEntryID IS NULL) AND (OrganismInfoID IS NULL) AND (ControlAgentID IS NULL) ";
        }
         
        //DebugWriteln("SelectString=$SelectString");
         
        $FormEntrySet=$Database->Execute($SelectString);
        
        $NumFormEntries=$FormEntrySet->Field("NumFormEntries");
        
        return($NumFormEntries);
    }
    
    public static function GetNumOrganismFormEntries($Database,$FormID)
    {
        $SelectString="SELECT COUNT(*) AS NumFormEntries
            FROM TBL_FormEntries
            WHERE (FormID = $FormID) AND (ParentFormEntryID IS NULL) AND (OrganismInfoID IS NOT NULL) OR
                (FormID = $FormID) AND (ParentFormEntryID IS NULL) AND (Picklist = 1) OR
				(FormID = $FormID) AND (ParentFormEntryID IS NULL) AND (AllOrganismPicklist = 1)";
        
        $FormEntrySet=$Database->Execute($SelectString);
        
        $NumFormEntries=$FormEntrySet->Field("NumFormEntries");
        
        return($NumFormEntries);
    } 
    
    public static function GetSetFromParentFormEntryID($Database,$ParentFormEntryID,$DatabaseTableID=null) // $OrganismInfoID=null,$ControlAgentID=null
    {
    	$SelectString="SELECT * FROM TBL_FormEntries ".
    		"WHERE ParentFormEntryID = $ParentFormEntryID ";
		
    	if ($DatabaseTableID==DATABASE_TABLE_ORGANISM_INFOS) TBL_DBTables::AddWhereClause($SelectString,"(OrganismInfoID IS NOT NULL)");
    	else if ($DatabaseTableID==DATABASE_TABLE_CONTROL_AGENTS) TBL_DBTables::AddWhereClause($SelectString,"(ControlAgentID IS NOT NULL)");
    	
    	//DebugWriteln("***************** GetSetFromParentFormEntryID ******************* SelectString=$SelectString");
    	
		$FormEntrySet=$Database->Execute($SelectString);
	
		return($FormEntrySet);
    }
    
    public static function Insert($Database,$Name="Untitled")
	{	
		$FormEntryID=-1;
		
		$ExecString="EXEC insert_TBL_FormEntries '$Name'";
			
		$FormEntryID=$Database->DoInsert($ExecString);
		
		return($FormEntryID);
	}
	
	public static function Update($Database,$FormEntryID,$FormID,$Name,$OrganismInfoID=null,$ControlAgentID=null) // was just EnteredValue; now will need $OrganismInfoID and $ControlAgentID and eventually not need DatabaseFieldID
	{
		$UpdateString="UPDATE TBL_FormEntries ".
			"SET FormID=".SQL::GetInt($FormID).", ".
				"Name=".SQL::GetString($Name).", ".
				"OrganismInfoID=".SQL::GetInt($OrganismInfoID).", ".
				"ControlAgentID=".SQL::GetString($ControlAgentID)." ".
			"WHERE ID=$FormEntryID";
		
		//DebugWriteln("UpdateString=$UpdateString");
		
		$Database->Execute($UpdateString);
		
		TBL_Forms::UpdateDateLastModified($Database,$FormID);
		
		return($FormEntryID);
	}
	
	public static function Delete($Database,$FormEntryID=0)
	{
		TBL_DBTables::Delete($Database,"TBL_FormEntries",$FormEntryID);
	}
	
    //******************************************************************************
    // Additional Functions
    //******************************************************************************
}
?>
