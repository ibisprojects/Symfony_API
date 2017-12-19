<?php
namespace Classes\DBTable;
//**************************************************************************************
// FileName: TBL_SpatialGridded.php
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


//**************************************************************************************
// Definitions
//**************************************************************************************

define("DEBUGGING_SPATIAL_DATA_TILED",false);
//define("TABLE_POSTFIX","_GoogleMaps");

//**************************************************************************************
// Definitions
//**************************************************************************************

define("SPATIAL_INT_UNKNOWN",0);
define("SPATIAL_INT_POINT",1);
define("SPATIAL_INT_POLYGON",2);
define("SPATIAL_INT_POLYGON_EDGE",3); // jjg - not used yet
define("SPATIAL_INT_POLYGON_INTERIOR",4); // jjg - not used yet
define("SPATIAL_INT_POLYLINE",5);

define("SPATIAL_X_OFFSET",0); //20037508
define("SPATIAL_Y_OFFSET",0); //20037508+25868 (110579), 20037508-37508-12450=19950042, 20000000

define("SPATIAL_Y_FACTOR",1); // COMPUTED: 0.995659, 1.0043599

// the following are the cell widths when indexed by the zoom level (0 is first and not used)

define("CELL_PIXEL_WIDTH",512);

define("ZOOM_LEVEL_MIN",1);
define("ZOOM_LEVEL_MAX",15);

//**************************************************************************************
// Class Definition
//**************************************************************************************

//**************************************************************************************
// Class Definition
//**************************************************************************************

class TBLSpatialGridded
{
	// the width of a pixel in map units (sort of meters in Mercator)
	
	public static $TBL_SpatialGriddeds_PixelWidths=array(
		0,
		//262144,
		131072,
		65536,
		32768,
		16384,
		8192,
		4096,
		2048,
		1024,
		512,
		256,
		128,
		64,
		32,
		16,
		8,
		4,
	
	);
	
	// the height of a pixel in map units (sort of meters in Mercator)
	
	public static $TBL_SpatialGriddeds_PixelHeights=array(
		0,
		//262144,
		131072,
		65536,
		32768,
		16384,
		8192,
		4096,
		2048,
		1024,
		512,
		256,
		128,
		64,
		32,
		16,
		8,
		4,
	
	);
	
	// total number of "meters" in each cell vertically
	
    static public $TBL_SpatialGriddeds_CellHeights=array(
		0,
		67108864, // comment out for 256x256 pixel cells
		33554432,
		16777216,
		8388608,
		4194304,
		2097152,
		1048576,
		524288,
		262144,
		131072,
		65536,
		32768,
		16384,
		8192,
		4096,
		2048,
		1024,
		512	
	);
	
	// total number of "meters" in each cell horiztonally
	
	static public $TBL_SpatialGriddeds_CellWidths=array(
		0,
		67108864, // comment out for 256x256 pixel cells
		33554432,
		16777216,
		8388608,
		4194304,
		2097152,
		1048576,
		524288,
		262144,
		131072,
		65536,
		32768,
		16384,
		8192,
		4096,
		2048,
		1024,
		512
	);
   public static function staticValue() {
    	$Test=TBL_SpatialGridded::$TBL_SpatialGriddeds_PixelHeights[1];
        return $Test;
    }
		
    //******************************************************************************
    // Private functions
    //******************************************************************************   
	//**************************************************************************************
	//**************************************************************************************
	private static function GetTilePaths($ZoomLevel,$ColumnIndex,$RowIndex,
		$OrganismInfoID,$NumPresent,$NumAbsent,
		$ProjectID,$AreaID,$InsertLogID,$AreaSubtypeID,$RouteID,$AreaAttributeValueID,
		$IconNumber,
		&$RootPath,&$WebPath)
	//
	// Returns:
	//	RootPath ("C:/GODMTiles...")  for the desired tile
	//	WebPath ("/GODMTiles") for the desired tile
	//	Index to select an icon file
	//
	{
//		DebugWriteln("NumPresent 3 =$NumPresent");
//		DebugWriteln("NumAbsent3=$NumAbsent");
		$IconNumber=(int)$IconNumber; // make sure we have an integer
		
		$FileName=$ZoomLevel."_".$ColumnIndex."_".$RowIndex.".png";
		
		// setup the paths
		
		$PartialPath="/GODMTiles/$IconNumber/"; // default is for all cells
		
//		$Index=0;
		if ($OrganismInfoID>0) 
		{
			if (($NumPresent>0)&&($NumAbsent==0)) // present only
			{
				$PartialPath="/GODMTiles/OrganismInfoIDs/$OrganismInfoID/1/$IconNumber";
			}
			else if (($NumPresent==0)&&($NumAbsent>0)) // absent only
			{
				$PartialPath="/GODMTiles/OrganismInfoIDs/$OrganismInfoID/0/$IconNumber";
			}
			else // present and absent (same if both are 0)
			{
				$PartialPath="/GODMTiles/OrganismInfoIDs/$OrganismInfoID/$IconNumber";
			}
//			$Index=$OrganismInfoID%8;
		}
		else if ($ProjectID>0) 
		{
			$PartialPath="/GODMTiles/ProjectIDs/$ProjectID/$IconNumber";
//			$Index=$ProjectID%8;
		}
		else if ($AreaID>0)
		{
			$PartialPath="/GODMTiles/AreaIDs/$AreaID/$IconNumber";
//			$Index=$ProjectID%8;
		}
		else if ($InsertLogID>0)
		{
			$PartialPath="/GODMTiles/InsertLogIDs/$InsertLogID/$IconNumber";
//			$Index=$ProjectID%8;
		}
		else if ($AreaSubtypeID>0) 
		{
			$PartialPath="/GODMTiles/AreaSubtypeIDs/$AreaSubtypeID/$IconNumber";
//			$Index=$ProjectID%8;
		}
		else if ($RouteID>0) 
		{
			$PartialPath="/GODMTiles/RouteID/$RouteID/$IconNumber";
		}
		else if ($AreaAttributeValueID>0) 
		{
			$PartialPath="/GODMTiles/AreaAttributeValueID/$AreaAttributeValueID/$IconNumber";
		}
		
		$RootPath=GetRootPathFromWebPath($PartialPath);
		
//		MakeSurePathExists($RootPath); // make sure the folder exists
		
		$RootPath=AppendPath($RootPath,$FileName);
		
		$WebPath=AppendPath($PartialPath,$FileName);
		
//		DebugWriteln("WebPath=$WebPath");
//		DebugWriteln("RootPath=$RootPath");

//		return($Index);
	}
	//***************************************************************************
	private static function GetHighResGeometry($Database,$GeographicGridID,$AreaID)
	//
	// Returns an original resolution Geometry string in GoogleMaps projection
	//
	{
		$Vectors=null;
		$GeometryString=null;
		
		// get the geographic data ID
		
		$SelectString="SELECT ID,RefX,RefY,RefWidth,RefHeight ".
			"FROM TBL_SpatialLayerData ".
			"WHERE SpatialLayerGridID=$GeographicGridID ".
				"AND AreaID=$AreaID ".
				"AND CoordinateSystemID=1 ".
				"AND GeometryData IS NOT NULL";
//		DebugWriteln("SelectString=$SelectString");
		
		$GeographicSet=$Database->Execute($SelectString);
		
		if ($GeographicSet->FetchRow())
		{
			$SpatialLayerDataID=$GeographicSet->Field("ID");
			
			$SelectString="SELECT GeometryData.STAsBinary() ".
				"FROM TBL_SpatialLayerData ".
				"WHERE ID=$SpatialLayerDataID";
			
			$ErrorString=BlueSpray::GetGeometryFromDatabase($SelectString,$GeometryString);
//DebugWriteln("GeometryString=$GeometryString");		
			
			if ($GeometryString!=null) $GeometryString=Projector::ProjectGeometryFromGeographicToGoogleMaps($GeometryString);
//DebugWriteln("GeometryString2=$GeometryString");		
		}	
		return($GeometryString);
	}
	private static function Insert($Database,$Type=SPATIAL_INT_POINT,$X=0,$Y=0,
		$Width=0,$Height=0,$CoordinateSystemID=null,$ZoomLevel=null,
		$GeometryString=null,$AreaID=null)
	//
	// This function does insert data but it is only called by TBL_SpatialGridded::InsertClusteredData()
	// Inputs:
	// $Database - points to GoogleMaps database
	//	$Type
	//	$X,$Y,Width,Height - 
	//	CoorindateSystemID - reqquired
	//	ZoomLevel - required
	//	Vectors - binary object
	//	AreaID - not used
	//	AreaToOrganisms - object from AreaToOrganisms class which contains an array of organisminfoIDs (used for deleting exising files)
	//
	// Returns: ARRAY of IDs for the SpatialGriddeds
	//
	{
		$Database_SpatialData=new DB_Connection();
		$Database_SpatialData->Connect("SpatialData_GoogleMaps","sa","cheatgrass");
		
	   	$TBL_SpatialGridded="TBL_SpatialGridded_".$ZoomLevel;
		
		$SpatialGriddedIDs=array();
		
		if (DEBUGGING_SPATIAL_DATA_TILED) DebugWriteln("X=$X, Y=$Y, Width=$Width, Height=$Height, ZoomLevel=$ZoomLevel");
//		if (DEBUGGING_SPATIAL_DATA_TILED) DebugWriteln("ZoomLevel=$ZoomLevel CellHeight=".TBL_SpatialGridded::$TBL_SpatialGriddeds_CellHeights[$ZoomLevel]);
	
		// compute the cell row and column
		
		$CellWidth=TBL_SpatialGridded::$TBL_SpatialGriddeds_CellWidths[$ZoomLevel];
		$CellHeight=TBL_SpatialGridded::$TBL_SpatialGriddeds_CellHeights[$ZoomLevel]/SPATIAL_Y_FACTOR;
//		$CellWidth=TBL_SpatialGridded::$TBL_SpatialGriddeds_CellWidths[$ZoomLevel]*2;
		if (DEBUGGING_SPATIAL_DATA_TILED) DebugWriteln("CellWidth=$CellWidth, CellHeight=$CellHeight");
		
		$StartColumnIndex=(int)(($X+SPATIAL_X_OFFSET)/$CellWidth); // 
		$EndColumnIndex=(int)(($X+$Width+SPATIAL_X_OFFSET)/$CellWidth);
		
//		$Temp1=(SPATIAL_Y_OFFSET-$Y);
//		$Temp2=(SPATIAL_Y_OFFSET-($Y-$Height));
		
		$StartRowIndex=(int)((SPATIAL_Y_OFFSET-$Y)/$CellHeight);
		$EndRowIndex=(int)((SPATIAL_Y_OFFSET-($Y+$Height))/$CellHeight);
		
		if (DEBUGGING_SPATIAL_DATA_TILED) DebugWriteln("StartColumnIndex=$StartColumnIndex, EndColumnIndex=$EndColumnIndex, StartRowIndex=$StartRowIndex, EndRowIndex=$EndRowIndex");
		
//		$Y=-$Y; // y values must be negative for the DLL to work
		
		for ($ColumnIndex=$StartColumnIndex;$ColumnIndex<=$EndColumnIndex;$ColumnIndex++)
		{
			for ($RowIndex=$StartRowIndex;$RowIndex<=$EndRowIndex;$RowIndex++)
			{
				if (DEBUGGING_SPATIAL_DATA_TILED) DebugWriteln("Column=$ColumnIndex, Row=$RowIndex");
				// crop the geometry string to the cell
				
				$RefX=$CellWidth*$ColumnIndex;
				$RefY=-$CellHeight*$RowIndex;
				$RefWidth=$CellWidth;
				$RefHeight=-$CellHeight;
//				DebugWriteln("1 RefX=$RefX, RefY=$RefY, RefWidth=$RefWidth, RefHeight=$RefHeight");
				
				// padd the area by 10% on each side
				
				$RefX-=$RefWidth/10;
				$RefY-=$RefHeight/10; // adds 10%
				$RefWidth=$RefWidth*1.2;
				$RefHeight=$RefHeight*1.2;
//
//				DebugWriteln("2 RefX=$RefX, RefY=$RefY, RefWidth=$RefWidth, RefHeight=$RefHeight");
//				DebugWriteln("Original GeometryString=$GeometryString");
				
				$GeometryString3=$GeometryString;

/*				BlueSpray::GetGeometryType($GeometryString,$GeometryType);

				if (($GeometryType!="Point")&&($GeometryType!="LineString"))
				{
					$ErrorString=BlueSpray::CropGeometry($GeometryString3,$RefX,$RefY,$RefWidth,$RefHeight);
				}
*/				$Test=substr($GeometryString3,0,strlen("GEOMETRYCOLLECTION EMPTY"));
				
//				DebugWriteln("Cropped GeometryString=$GeometryString3");
				
				if ($Test!="GEOMETRYCOLLECTION EMPTY")
				{
//					DebugWriteln("Inserting ZoomLevel=$ZoomLevel, ColumnIndex=$ColumnIndex, RowIndex=$RowIndex");		
					
			      	// insert the SpatialGridded record
			    	
			 		$ExecString="EXEC insert_TBL_SpatialGridded_".$ZoomLevel." ".$Type;
						
	//				DebugWriteln("ExecString=".$ExecString);
					
					$SpatialGriddedID=$Database->DoInsert($ExecString);
//			    	DebugWriteln("------------- ZoomLevel=$ZoomLevel, SpatialGriddedID=$SpatialGriddedID");

					// update the other values
					
					$UpdateString="UPDATE $TBL_SpatialGridded ".
						"SET RefX=$X, ".
							"RefY=$Y, ".
							"RefWidth=$Width, ".
							"RefHeight=$Height, ".
							"ColumnIndex=$ColumnIndex, ".
							"RowIndex=$RowIndex, ".
							"NumAreas=0, ".
							"NumSensitive=0, ".
							"NumNonsensitive=0, ".
							"GeometryData=geometry::STGeomFromText('$GeometryString3', 0) ".
						"WHERE ID=$SpatialGriddedID";
	//		    	DebugWriteln("UpdateString=$UpdateString");
					
					$Database_SpatialData->Execute($UpdateString);
					
					// delete any files without a relationship
					
	//				TBL_SpatialGridded::DeleteTiles($Database_SpatialData,$ZoomLevel,$SpatialGriddedID,0,0,0);
					
	//				if (file_exists($RootPath)) unlink($RootPath);
					
					$SpatialGriddedIDs[]=$SpatialGriddedID;
				}
			}
		}
 		return($SpatialGriddedIDs);
	}

   	//******************************************************************************
    // Basic database functions
    //******************************************************************************   
	private static function GetSetFromID($Database_SpatialData,$ZoomLevel,$SpatialGriddedID)
    {
    	$TBL_SpatialGridded="TBL_SpatialGridded_".$ZoomLevel;
    	
    	$SpatialGriddedID=SafeInt($SpatialGriddedID);
    	
    	$SelectString="SELECT * ".
			"FROM $TBL_SpatialGridded ".
			"WHERE ID='".$SpatialGriddedID."'";
	
		$Set=$Database_SpatialData->Execute($SelectString);
	
		return($Set);
    }

	
	public static function Delete($Database_SpatialData,$ZoomLevel,$SpatialGriddedID)
	{
	   	$TBL_SpatialGridded="TBL_SpatialGridded_".$ZoomLevel;
	   	$REL_SpatialGriddedToOrganismInfo="REL_SpatialGriddedToOrganismInfo_".$ZoomLevel;
	   	$REL_SpatialGriddedToArea="REL_SpatialGriddedToArea_".$ZoomLevel;
	   	
		TBL_SpatialGridded::DeleteTiles($Database_SpatialData,$ZoomLevel,$SpatialGriddedID,0,0,0);
		
		// Delete related Organism rels but bypass the regular call to avoid the DeleteTiles() function call
		
		$SelectString="SELECT ID ".
			"FROM $REL_SpatialGriddedToOrganismInfo ".
			"WHERE SpatialGriddedID=$SpatialGriddedID";
			
		$RELSet=$Database_SpatialData->Execute($SelectString);
		
		while ($RELSet->FetchRow())
		{
			$DeleteString="DELETE FROM $REL_SpatialGriddedToOrganismInfo WHERE ID=".$RELSet->Field("ID");
			
//			DebugWriteln("DeleteString=$DeleteString");
			
			$Database_SpatialData->Execute($DeleteString);
		}
		
		// Delete related area rels but bypass the regular call to avoid the updates and DeleteTiles() calls
		
		$SelectString="SELECT ID ".
			"FROM $REL_SpatialGriddedToArea ".
			"WHERE SpatialGriddedID=$SpatialGriddedID";
			
		$RELSet=$Database_SpatialData->Execute($SelectString);
		
		while ($RELSet->FetchRow())
		{	
			$DeleteString="DELETE FROM $REL_SpatialGriddedToArea WHERE ID=".$RELSet->Field("ID");
			
//			DebugWriteln("DeleteString=$DeleteString");
			
			$Database_SpatialData->Execute($DeleteString);
		}
		
		//
		
		$DeleteString="DELETE FROM $TBL_SpatialGridded WHERE ID=$SpatialGriddedID";
			
//		DebugWriteln("DeleteString=$DeleteString");
		
		$Database_SpatialData->Execute($DeleteString);
		
//		TBL_DBTables::Delete($Database_SpatialData,$TBL_SpatialGridded,$SpatialGriddedID);
	}
	
	//**************************************************************************************
	// Additional Functions
	//**************************************************************************************
/*	public static function GetIDFromAreaID($Database,$AreaID,$ZoomLevel)
	{
	   	$TBL_SpatialGridded="TBL_SpatialGridded".TABLE_POSTFIX;
	   	$REL_SpatialGriddedToArea="REL_SpatialGriddedToArea".TABLE_POSTFIX;
	   	
		$ID=null;
		
    	$SelectString="SELECT SpatialGriddedID ".
    		"FROM $REL_SpatialGriddedToArea ".
    			"INNER JOIN $TBL_SpatialGridded ".
    				"ON $TBL_SpatialGridded.ID=$REL_SpatialGriddedToArea.SpatialGriddedID ".
    		"WHERE $REL_SpatialGriddedToArea.AreaID=$AreaID ".
    			"AND $TBL_SpatialGridded.ZoomLevel=$ZoomLevel";
    			
    	$RELSet=$Database->Execute($SelectString);
	
		if ($RELSet->FetchRow()) $ID=$RELSet->Field(1);
		
		return($ID);
	}
*/	//**************************************************************************************
	// Functions to insert data
	//**************************************************************************************
	
	//**************************************************************************************
	public static function InsertForAreaID($Database,$AreaID)
	//
	// Call this public static function to update an existing areaID with data in TBL_SpatialLayerData for the geographic data
	//
	{
		$NumPolygonIgnored=NULL;
		$NumPolygons=NULL;
		$NumPoints=NULL;
		$NumClustered=NULL;
		$NumPolygonCells=NULL;
		
		TBL_SpatialGridded::InsertForAreaIDWithStats($Database,$AreaID,&$NumPolygonIgnored,&$NumPolygons,
			&$NumPoints,&$NumClustered,&$NumPolygonCells);
	}

	public static function DeleteFromAreaID($Database,$AreaID)
	//
	// Call this public static function to update an existing areaID with data in TBL_SpatialLayerData for the geographic data
	//
	// Called by TBL_Areas
	//
	{
		$Database_SpatialData=new DB_Connection();
		$Database_SpatialData->Connect("SpatialData_GoogleMaps","sa","cheatgrass");
		
		for ($ZoomLevel=ZOOM_LEVEL_MAX;$ZoomLevel>=ZOOM_LEVEL_MIN;$ZoomLevel--)
		{
			$TBL_SpatialGriddedName="TBL_SpatialGridded_".$ZoomLevel;
			$REL_SpatialGriddedToAreaName="REL_SpatialGriddedToArea_".$ZoomLevel;
			$REL_SpatialGriddedToOrganismInfoName="REL_SpatialGriddedToOrganismInfo_".$ZoomLevel;
		
			$SelectString="SELECT $REL_SpatialGriddedToAreaName.ID AS SpatialGriddedToAreaID, ".
					"$TBL_SpatialGriddedName.ID AS SpatialGriddedID, ".
					"$TBL_SpatialGriddedName.NumAreas AS NumAreas ".
				"FROM $REL_SpatialGriddedToAreaName ".
					"INNER JOIN $TBL_SpatialGriddedName ".
						"ON $TBL_SpatialGriddedName.ID=$REL_SpatialGriddedToAreaName.SpatialGriddedID ".
				"WHERE $REL_SpatialGriddedToAreaName.AreaID=$AreaID ";
//					"AND $TBL_SpatialGriddedName.ZoomLevel=$ZoomLevel";
				
			$RELSet=$Database_SpatialData->Execute($SelectString);
			
			while ($RELSet->FetchRow())
			{
				$SpatialGriddedToAreaID=$RELSet->Field("SpatialGriddedToAreaID");
				$SpatialGriddedID=$RELSet->Field("SpatialGriddedID");
				$NumAreas=$RELSet->Field("NumAreas");
//DebugWriteln("- SpatialGriddedToAreaID=$SpatialGriddedToAreaID");			
				
				REL_SpatialGriddedToArea::Delete($Database,$Database_SpatialData,$ZoomLevel,$SpatialGriddedToAreaID); // updates NumAreas
//				DebugWriteln("TBL_SpatialGridded::DeleteFromAreaID 1");
				
				if ($NumAreas<=1) // the grid cell does not represent areas anymore
				{
//					TBL_SpatialGridded::Delete($Database_SpatialData,$ZoomLevel,$SpatialGriddedID);
//					DebugWriteln("TBL_SpatialGridded::DeleteFromAreaID 2");
				}
			}
		}	
	}
	//**************************************************************************************
	public static function InsertForAreaIDWithStats($Database,$AreaID,&$NumPolygonIgnored,&$NumPolygons,
		&$NumPoints,&$NumClustered,&$NumPolygonCells)
	//
	// This public static function includes the stats on what was updated
	//
	{
		$Database_SpatialData=new DB_Connection();
		$Database_SpatialData->Connect("SpatialData_GoogleMaps","sa","cheatgrass");
		
		$AreaSet=$Database->Execute("SELECT * FROM TBL_Areas WHERE ID=$AreaID");
		
//		$AreaSet=TBL_Areas::GetSetFromID($Database,$AreaID);
		
		$AreaSubtypeID=$AreaSet->Field("AreaSubtypeID");
		
		if (DEBUGGING_SPATIAL_DATA_TILED) DebugWriteln("AreaID=$AreaID, AreaSubtypeID=$AreaSubtypeID");
		
		// get the gridIDs (these functions will add the grids as needed)
		
		$GeographicGridID=TBL_SpatialLayerGrids::GetStandardID($Database,$AreaSubtypeID,COORDINATE_SYSTEM_WGS84_GEOGRAPHIC);
		
//		DebugWriteln("AreaSubtypeID=$AreaSubtypeID, GeographicGridID=$GeographicGridID");
		
		// 
		
		$ErrorString=TBL_SpatialGridded::InsertClusteredData($Database,$Database_SpatialData,$GeographicGridID,$AreaID,
			$NumPolygonIgnored,$NumPolygons,$NumPoints,$NumClustered,$NumPolygonCells);
			
		return($ErrorString);
	}
	
	//**************************************************************************************
	// tile Functions
	//**************************************************************************************
	public static function DeleteTiles($Database_SpatialData,$ZoomLevel,$SpatialGriddedID,$OrganismInfoID=0,
		$ProjectID=0,$AreaID=0,$InsertLogID=0,$AreaSubtypeID=0)
	{
//		DebugWriteln("TBL_SpatialGridded::DeleteTiles() SpatialGriddedID=$SpatialGriddedID");

		$SpatialGriddedSet=TBL_SpatialGridded::GetSetFromID($Database_SpatialData,$ZoomLevel,$SpatialGriddedID);
		
		if ($SpatialGriddedSet->FetchRow())
		{
//			$ZoomLevel=$SpatialGriddedSet->Field("ZoomLevel");
			$ColumnIndex=$SpatialGriddedSet->Field("ColumnIndex");
			$RowIndex=$SpatialGriddedSet->Field("RowIndex");
			
			for ($i=0;$i<27;$i++) // each icon number
			{
				if ($OrganismInfoID>0)
				{
					// delete the organism info tiles (present or absent)
					
					TBL_SpatialGridded::GetTilePaths($ZoomLevel,$ColumnIndex,$RowIndex,
						$OrganismInfoID,0,0,
						$ProjectID,$AreaID,0,0,0,0,
						$i,$RootPath,$WebPath);
				
					if (file_exists($RootPath)) unlink($RootPath);
					
					// delete the organism info tiles (present or absent)
					
					TBL_SpatialGridded::GetTilePaths($ZoomLevel,$ColumnIndex,$RowIndex,
						$OrganismInfoID,1,0,
						$ProjectID,$AreaID,0,0,0,0,
						$i,$RootPath,$WebPath);
				
					if (file_exists($RootPath)) unlink($RootPath);
					
					// delete the organism info tiles (present or absent)
					
					TBL_SpatialGridded::GetTilePaths($ZoomLevel,$ColumnIndex,$RowIndex,
						$OrganismInfoID,0,1,
						$ProjectID,$AreaID,0,0,0,0,
						$i,$RootPath,$WebPath);
				
					if (file_exists($RootPath)) unlink($RootPath);
				}
				else if ($ProjectID>0)// just project 
				{
					TBL_SpatialGridded::GetTilePaths($ZoomLevel,$ColumnIndex,$RowIndex,
							0,0,0,
							$ProjectID,0,0,0,0,0,
							$i,$RootPath,$WebPath);
					
					if (file_exists($RootPath)) unlink($RootPath);
				}
				else if ($AreaID>0) // just area
				{
					TBL_SpatialGridded::GetTilePaths($ZoomLevel,$ColumnIndex,$RowIndex,
							0,0,0,
							0,$AreaID,0,0,0,0,
							$i,$RootPath,$WebPath);
					
					if (file_exists($RootPath)) unlink($RootPath);
				}
				else if ($AreaSubtypeID>0) // just area
				{
					TBL_SpatialGridded::GetTilePaths($ZoomLevel,$ColumnIndex,$RowIndex,
							0,0,0,
							0,0,0,$AreaSubtypeID,0,0,
							$i,$RootPath,$WebPath);

					if (file_exists($RootPath)) unlink($RootPath);
				}
				else if ($InsertLogID>0) // just area
				{
					TBL_SpatialGridded::GetTilePaths($ZoomLevel,$ColumnIndex,$RowIndex,
							0,0,0,
							0,0,$InsertLogID,0,0,0,
							$i,$RootPath,$WebPath);

					if (file_exists($RootPath)) unlink($RootPath);
				}
			}
		}
//		DebugWriteln("TBL_SpatialGridded::DeleteTiles() Returning");
	}

	//**************************************************************************************
	// New functions to use a separate database
	//**************************************************************************************
	//**************************************************************************************
	public static function GetMiddleString($ZoomLevel,$OrganismInfoID=null,$ProjectID=null,$AreaID=null,
		$InsertLogID=null,
		$AreaSubtypeID=null,$RouteID=null,$AreaAttributeValueID=null,
		$UserID=null,
		$NumPresent=null,$NumAbsent=null)
	//
	// Rethrns a "FROM ... WHERE" string that will select the TBL_SpatialGridded records
	//	that contain the specified parameters
	// Also handled sensitive data making it invisible below a certain resolution.
	//
	// Also called by SceneConsole::GetVisitSetFromClick
	//
	{
	   	$TBL_SpatialGridded="TBL_SpatialGridded_".$ZoomLevel;
	   	$REL_SpatialGriddedToArea="REL_SpatialGriddedToArea_".$ZoomLevel;
	   	$REL_SpatialGriddedToOrganismInfo="REL_SpatialGriddedToOrganismInfo_".$ZoomLevel;
	   	
//		DebugWriteln("OrganismInfoID=$OrganismInfoID");
//		DebugWriteln("GetMiddleString UserID=$UserID");

		$FromString="";
		
		$WhereString="";
		
		$FromStringHasArea=false;
		$FromStringHasOrganismInfo=false;
		
		if ($OrganismInfoID>0)
		{
			$FromString.=
				"INNER JOIN $REL_SpatialGriddedToOrganismInfo ".
					"ON $REL_SpatialGriddedToOrganismInfo.SpatialGriddedID=$TBL_SpatialGridded.ID ";
				
			if ($WhereString=="") $WhereString.="WHERE ";
			else $WhereString.="AND ";
			
			$WhereString.="OrganismInfoID=$OrganismInfoID ";
			
			if ($NumPresent>0) $WhereString.=" AND NumPresent>=$NumPresent";
			if ($NumAbsent>0) $WhereString.=" AND NumAbsent>=$NumAbsent";
//			else $WhereString.=" AND NumPresent>0";
				
			$FromStringHasOrganismInfo=true;
		}
				
		if ($ProjectID>0) // can either have a project
		{
			$FromString.=
				"INNER JOIN $REL_SpatialGriddedToArea ".
					"ON $REL_SpatialGriddedToArea.SpatialGriddedID=$TBL_SpatialGridded.ID ";
			
			TBL_DBTables::AddWhereClause($WhereString,"$REL_SpatialGriddedToArea.ProjectID=$ProjectID ");
				
			$FromStringHasArea=true;
		}
		else if ($AreaID>0) // or an area
		{
			$FromString.=
				"INNER JOIN $REL_SpatialGriddedToArea ".
					"ON $REL_SpatialGriddedToArea.SpatialGriddedID=$TBL_SpatialGridded.ID ";

			TBL_DBTables::AddWhereClause($WhereString,"$REL_SpatialGriddedToArea.AreaID=$AreaID ");

			$FromStringHasArea=true;

		}
		else if ($InsertLogID>0) // or an area
		{
			$FromString.=
				"INNER JOIN $REL_SpatialGriddedToArea ".
					"ON $REL_SpatialGriddedToArea.SpatialGriddedID=$TBL_SpatialGridded.ID ";

			TBL_DBTables::AddWhereClause($WhereString,"$REL_SpatialGriddedToArea.InsertLogID=$InsertLogID ");

			$FromStringHasArea=true;

		}
		else if ($AreaSubtypeID>0) // or an areasubtype
		{
			$TempString="SELECT ID ".
				"FROM [invasive].[dbo].[TBL_Areas] ".
				"WHERE AreaSubtypeID=$AreaSubtypeID "; // jjg - not the fastest, but works for now

			$FromString.=
				"INNER JOIN $REL_SpatialGriddedToArea ".
					"ON $REL_SpatialGriddedToArea.SpatialGriddedID=$TBL_SpatialGridded.ID ";
					
			TBL_DBTables::AddWhereClause($WhereString,"$REL_SpatialGriddedToArea.AreaID IN ($TempString) ");
				
			$FromStringHasArea=true;
			
		}
		else if ($RouteID>0) // 
		{
			$TempString="SELECT AreaID ".
				"FROM [invasive].[dbo].[REL_AreaToRoute] ".
				"WHERE RouteID=$RouteID "; // jjg - not the fastest, but works for now

			$FromString.=
				"INNER JOIN $REL_SpatialGriddedToArea ".
					"ON $REL_SpatialGriddedToArea.SpatialGriddedID=$TBL_SpatialGridded.ID ";
					
			$WhereString.="AND $REL_SpatialGriddedToArea.AreaID IN ($TempString) ";
				
			$FromStringHasArea=true;
			
		}
		else if ($AreaAttributeValueID>0) // or an areasubtype
		{
			$TempString="SELECT AreaID ".
				"FROM [invasive].[dbo].[TBL_AttributeData] ".
				"WHERE AttributeValueID=$AreaAttributeValueID "; // jjg - not the fastest, but works for now

			$FromString.=
				"INNER JOIN $REL_SpatialGriddedToArea ".
					"ON $REL_SpatialGriddedToArea.SpatialGriddedID=$TBL_SpatialGridded.ID ";
					
			$WhereString.="AND $REL_SpatialGriddedToArea.AreaID IN ($TempString) ";
				
			$FromStringHasArea=true;
			
		}
		
		if ($ZoomLevel>=8) // need to add the sensitive query
		{
//			$UserID=(int)0;
			
			if (($UserID!==null)&&($UserID!=="")&&($UserID!=0)) // user is logged in
			{
				$UserID=(int)$UserID;
				
				if ($FromStringHasArea==false) // add REL_SpatialGriddedToArea
				{
					$FromString=
						$FromString." ".
							"INNER JOIN $REL_SpatialGriddedToArea ".
								"ON $REL_SpatialGriddedToArea.SpatialGriddedID=$TBL_SpatialGridded.ID ";
				}
				
				// create string to select the sensitive data in this user's project
				
				$SelectString="SELECT $TBL_SpatialGridded.ID ".
					"FROM $TBL_SpatialGridded ".$FromString." ".
						"INNER JOIN [invasive].[dbo].[TBL_Areas] ON [invasive].[dbo].[TBL_Areas].ID=$REL_SpatialGriddedToArea.AreaID ".
						"INNER JOIN [invasive].[dbo].[REL_PersonToProject] ".
							"ON [invasive].[dbo].[REL_PersonToProject].ProjectID=[invasive].[dbo].[TBL_Areas].ProjectID ".
					$WhereString." ".
						"AND (NumNonsensitive=0 OR NumNonsensitive IS NULL) ".
						"AND [invasive].[dbo].[REL_PersonToProject].PersonID=$UserID ";
				
				TBL_DBTables::AddWhereClause($WhereString,"(NumNonsensitive>0 OR $TBL_SpatialGridded.ID IN ($SelectString)) ");
			
//				$WhereString.="(NumNonsensitive>0 OR $TBL_SpatialGridded.ID IN ($SelectString)) ";
				
//				DebugWriteln("SelectString=$SelectString");
				
			}
			else // user not logged in, only select nonsensitive data
			{
				TBL_DBTables::AddWhereClause($WhereString,"NumNonsensitive>0 ");
			
//				$WhereString.="NumNonsensitive>0 ";
			}
		}
		
		$MiddleString="FROM $TBL_SpatialGridded ".$FromString.$WhereString;
		
//		DebugWriteln("MiddleString2=$MiddleString");
		
		return($MiddleString);
	}

	
	public static function InsertClusteredData($Database,$Database_SpatialData,$GeographicGridID,$AreaID,
		&$NumPolygonIgnored,&$NumPolygons,&$NumPoints,&$NumClustered,&$NumPolygonCells)
	//
	// Called to add new data to the google maps clustered data.  If the AreaID was preexisiting, it's data should have been
	// removed and then this public static function called to add it.
	//
	{
		$ErrorString=null;
		
		if (DEBUGGING_SPATIAL_DATA_TILED) DebugWriteln("InsertClusteredData: AreaID=$AreaID");
		
		// get the geometry type
		
//		$AreaSet=TBL_Areas::GetSetFromID($Database,$AreaID);
		
		// get the original high-resolution, geographic data
		
		$GeometryString=TBL_SpatialGridded::GetHighResGeometry($Database,$GeographicGridID,$AreaID);
//		DebugWriteln("GeometryString=$GeometryString");
		
		// determine the spatial data type based on the vector data (jjg - Should be in TBL_Areas)
		
		if ($GeometryString!=null)
		{
			$ErrorString=BlueSpray::GetBoundsFromGeometry($GeometryString,$RefX,$RefY,$RefWidth,$RefHeight);
//			DebugWriteln("ErrorString=$ErrorString");
			
			if ($ErrorString==null)
			{
//				DebugWriteln("Geometry Bounds: RefX=$RefX, RefY=$RefY, RefWidth=$RefWidth, RefHeight=$RefHeight");
				
		//		$SpatialDataType=SPATIAL_INT_UNKNOWN;
				
				$ErrorString=BlueSpray::GetGeometryType($GeometryString,$Type); // Point, MultiPolygon, MultiLineString
//				DebugWriteln("Type=$Type");
				
				$SpatialDataType=SPATIAL_INT_POINT;
				if ($Type=="MultiPolygon") $SpatialDataType=SPATIAL_INT_POLYGON;
				if ($Type=="MultiLineString") $SpatialDataType=SPATIAL_INT_POLYLINE;
				
				// add the tiles from the highest resolution zoom (15) to the lowest (1)
				
				$ZoomLevelMin=ZOOM_LEVEL_MIN; // ZOOM_LEVEL_MIN
				$ZoomLevelMax=ZOOM_LEVEL_MAX; //ZOOM_LEVEL_MAX
				
				for ($ZoomLevel=$ZoomLevelMax;($ZoomLevel>=$ZoomLevelMin)&&($ErrorString==null);$ZoomLevel--)
				{
//					DebugWriteln("******************* GeometryString=$GeometryString");
					$MaxZoom=pow(2,$ZoomLevel+6)*0.0000001;
			
					$Temp=$MaxZoom;
					if ($ZoomLevel==0) $Temp=0;
					
					$Tolerance=(1/$MaxZoom)*12; //6;
					
					$MaxWidth=(1/$MaxZoom)*CELL_PIXEL_WIDTH*2;
					
					if (DEBUGGING_SPATIAL_DATA_TILED) DebugWriteln("Level=$ZoomLevel, Zoom=$MaxZoom, GridZoom=$Temp, 1/MaxZoom=".(1/$MaxZoom).", Tolerance=$Tolerance, MaxWidth=$MaxWidth");
					
					// generalize the data to this zoom level
					
			//		Generalize($NumCoordinates,$NewCoordinates,(1/$MaxZoom));
						
					BlueSpray::GetBoundsFromGeometry($GeometryString,$RefX,$RefY,$RefWidth,$RefHeight);
					
					if (DEBUGGING_SPATIAL_DATA_TILED) DebugWriteln("Original AreaID=$AreaID, RefX=$RefX, RefY=$RefY, RefWidth=$RefWidth, RefHeight=$RefHeight");
						
					$AbsRefWidth=$RefWidth;
					if ($AbsRefWidth<0) $AbsRefWidth=-$AbsRefWidth;
					
					$AbsRefHeight=$RefHeight;
					if ($AbsRefHeight<0) $AbsRefHeight=-$AbsRefHeight;
					
					if (DEBUGGING_SPATIAL_DATA_TILED) DebugWriteln("AbsRefWidth=$AbsRefWidth, AbsRefHeight=$AbsRefHeight, ");
					
					//
		
					$TreatAsPoints=false;
		
//					DebugWriteln("ZoomLevel=$ZoomLevel");
						
					if ((($Type=="MultiPolygon")||($Type=="Polygon"))&&
						(($AbsRefWidth>$MaxWidth)&&($AbsRefHeight>$MaxWidth))) // ignore because it is too big
					{
						if ($NumPolygonIgnored!=NULL) $NumPolygonIgnored[$ZoomLevel]++;
//						DebugWriteln("- Ignored Big Polygon");
					}
					else if (($AbsRefWidth>$Tolerance)||($AbsRefHeight>$Tolerance)) // keep as a polygon or polyline
					{
						$Exp=(15-$ZoomLevel);
						
	//					DebugWriteln("Exp=$Exp");
						
						$Tolerance=pow(2.1,$Exp); // 2.2 is too big, 2.1 is ok if we don't do recursive simplification
						
//						DebugWriteln("Tolerance=$Tolerance");
						
						$GeometryString2=$GeometryString; // save the original value by making a copy
						$ErrorString=BlueSpray::SimpifyGeometry($GeometryString2,$Tolerance,false);
						if (DEBUGGING_SPATIAL_DATA_TILED) DebugWriteln("Simplified GeometryString2=$GeometryString2");

						if ($GeometryString2=="POLYGON EMPTY") // simplified it out of existance
						{
							$TreatAsPoints=true;
	//						DebugWriteln("HIHIHIHIHI");
						}
						else 
						{
							$SpatialGriddedIDs=TBL_SpatialGridded::Insert($Database_SpatialData,$SpatialDataType,
								$RefX,$RefY,$RefWidth,$RefHeight,
								COORDINATE_SYSTEM_GOOGLE_MAPS,$ZoomLevel,$GeometryString2,$AreaID);
								
	//						DebugWriteln("-------------- SpatialGriddedIDs=$SpatialGriddedIDs");
							
							// insert the relationships
							
							foreach($SpatialGriddedIDs as $SpatialGriddedID) // may be none if the polygon is out of GoogleMaps bounds
							{
								// insert the area relationships
								
								REL_SpatialGriddedToArea::Insert($Database,$Database_SpatialData,$ZoomLevel,$SpatialGriddedID,$AreaID);
							}
								
							if ($NumPolygons!=NULL) $NumPolygons[$ZoomLevel]++;
							if ($NumPolygonCells!=NULL) $NumPolygonCells[$ZoomLevel]+=count($SpatialGriddedIDs);
	//						if (DEBUGGING_SPATIAL_DATA_TILED) DebugWriteln("Added Polygon");
//							DebugWriteln("- Added Polygon");
						}
					}
					else $TreatAsPoints=true;
					
					if ($TreatAsPoints) // treat as points
					{
			//			DebugWriteln("Original OriginalDataID=$OriginalDataID, AreaID=$AreaID, RefX=$RefX, RefY=$RefY");
						
						$MinRefX=$RefX-$Tolerance;
						$MaxRefX=$RefX+$Tolerance;
						$MinRefY=$RefY-$Tolerance;
						$MaxRefY=$RefY+$Tolerance;
					
						// see if we already have it in the database
						
						$SelectString="SELECT ID,RefX,RefY ".
							"FROM TBL_SpatialGridded_".$ZoomLevel." ".
							"WHERE RefWidth=0 ".
								"AND (RefX>($MinRefX)) ".
								"AND (RefX<($MaxRefX)) ".
								"AND (RefY>($MinRefY)) ".
								"AND (RefY<($MaxRefY)) ";
						
//						if (DEBUGGING_SPATIAL_DATA_TILED) DebugWriteln("SelectString=$SelectString");
						
						$ClusterSet=$Database_SpatialData->Execute($SelectString);
						
						if ($ClusterSet->FetchRow()==FALSE) // did not find a cluster so add it
						{
							$CenterRefX=$RefX+$RefWidth/2;
							$CenterRefY=$RefY+$RefHeight/2;

							$PointString="POINT( $CenterRefX $CenterRefY)";
//							DebugWriteln("PointString=$PointString");
							
							$SpatialGriddedIDs=TBL_SpatialGridded::Insert($Database_SpatialData,
								SPATIAL_INT_POINT,$CenterRefX,$CenterRefY,0,0,
								COORDINATE_SYSTEM_GOOGLE_MAPS,$ZoomLevel,$PointString,$AreaID);
							//DumpArray($SpatialGriddedIDs); ************** GJN
							
							if (count($SpatialGriddedIDs)>0) 
							{
								$SpatialGriddedID=$SpatialGriddedIDs[0];
								
								if ($NumPoints!=NULL) $NumPoints[$ZoomLevel]++;
//								DebugWriteln("- Added Point");
							}
							else
							{
								if (DEBUGGING_SPATIAL_DATA_TILED) DebugWriteln("- Point outside GoogleMaps bounds");
							}
	//						if (DEBUGGING_SPATIAL_DATA_TILED) DebugWriteln("Added Point");
						}
						else // just add the relationships
						{
							$SpatialGriddedID=$ClusterSet->Field("ID");
							
							if ($NumClustered!=NULL) $NumClustered[$ZoomLevel]++;
//							DebugWriteln("Added Clustering with relationship");
	//						if (DEBUGGING_SPATIAL_DATA_TILED) DebugWriteln("- Added Clustering with relationship");
					 	}
	//	DebugWriteln("3");				
						if ($ErrorString==null) // insert the area relationships
						{
							REL_SpatialGriddedToArea::Insert($Database,$Database_SpatialData,$ZoomLevel,$SpatialGriddedID,$AreaID);
					
							if (DEBUGGING_SPATIAL_DATA_TILED) DebugWriteln("After REL_SpatialGriddedToArea::Insert");
						}
					}
					
					if (DEBUGGING_SPATIAL_DATA_TILED) DebugWriteln();
				}
			}
		}	
//		DebugWriteln("CountNew=$CountNew, COuntExisting=$CountExisting");
		if (DEBUGGING_SPATIAL_DATA_TILED) DebugWriteln();
		
		return($ErrorString);
	}
	//**************************************************************************************
	public static function RenderTile($Database,$ZoomLevel,$ColumnIndex,$RowIndex,
		$OrganismInfoID,$ProjectID,$AreaID,$InsertLogID,
		$UserID,$AreaSubtypeID,$RouteID,$AreaAttributeValueID,
		$NumPresent=null,$NumAbsent=null,
		$DoCounts=false,&$SetupPoints,&$SetupVectors,&$SaveTime,&$PointCount,&$VectorCount,$Debugging=false,$IconNumber=0)
	//
	// OrganismInfoID - >0 if we are rendering organism layers
	// ProjectID >0 if we rendering project layers
	//
	{
    	$TBL_SpatialGridded="TBL_SpatialGridded_".$ZoomLevel;
		
		$Database_SpatialData=new DB_Connection();
		$Database_SpatialData->Connect("SpatialData_GoogleMaps","sa","cheatgrass");
		
//$OrganismInfoID=0; // shows all data in map
//DebugWriteln("NumPresent 2=$NumPresent");
//DebugWriteln("NumAbsent 2=$NumAbsent");
		TBL_SpatialGridded::GetTilePaths($ZoomLevel,$ColumnIndex,$RowIndex,
			$OrganismInfoID,$NumPresent,$NumAbsent,
			$ProjectID,$AreaID,$InsertLogID,$AreaSubtypeID,$RouteID,$AreaAttributeValueID,
			$IconNumber,
			$RootPath,$WebPath);
//			DebugWriteln("RootPath=$RootPath");
//			DebugWriteln("WebPath=$WebPath");
		
		// if the file exists, just return it
		
		if ((file_exists($RootPath)==FALSE)||$ZoomLevel>=8) // second term forces tiles to always be regenerated
		{
			if ($DoCounts)
			{
				$SetupPoints=0;
				$SetupVectors=0;
				$SaveTime=0;
				$PointCount=0;
				$VectorCount=0;
			}
			
			$StartTime=GetMicrotime();
			
			// rendering parameters
			
			$DoPoints=true;
			$DoVectors=true;
			$OutputTiming=0;
			
			// setup the scene
			
//			DebugWriteln("SPATIAL_Y_FACTOR=".SPATIAL_Y_FACTOR);
//			DebugWriteln("ZoomLevel=$ZoomLevel, ColumnIndex=$ColumnIndex, RowIndex=$RowIndex ");
//			DebugWriteln("TBL_SpatialGriddeds_PixelHeights=".TBL_SpatialGridded::$TBL_SpatialGriddeds_PixelHeights[$ZoomLevel]);
			
//			DebugWriteln("Thing=".(TBL_SpatialGridded::$TBL_SpatialGriddeds_PixelHeights[$ZoomLevel]/SPATIAL_Y_FACTOR));
			
			$CellWidth=TBL_SpatialGridded::$TBL_SpatialGriddeds_CellWidths[$ZoomLevel];
			$CellHeight=TBL_SpatialGridded::$TBL_SpatialGriddeds_CellHeights[$ZoomLevel]/SPATIAL_Y_FACTOR;
	//		$CellWidth=$TBL_SpatialGriddeds_CellWidths[$ZoomLevel]*2;
			$Zoom=1/TBL_SpatialGridded::$TBL_SpatialGriddeds_PixelWidths[$ZoomLevel];
			$Zoom=1/(TBL_SpatialGridded::$TBL_SpatialGriddeds_PixelHeights[$ZoomLevel]/SPATIAL_Y_FACTOR);
			
//			$RefX=($CellWidth*$ColumnIndex)-20000000-22285;
//			$RefY=20000000-($CellWidth*$RowIndex)-17107;
			
			$RefX=($CellWidth*$ColumnIndex)-SPATIAL_X_OFFSET;
			$RefY=SPATIAL_Y_OFFSET-($CellHeight*$RowIndex);
			
//			DebugWriteln("RefX=$RefX, RefY=$RefY ");
			
			// setup the basic query
//DebugWriteln("2");
			
			$MiddleString=TBL_SpatialGridded::GetMiddleString($ZoomLevel,
				$OrganismInfoID,$ProjectID,$AreaID,$InsertLogID,$AreaSubtypeID,$RouteID,$AreaAttributeValueID,
				$UserID,$NumPresent,$NumAbsent);
//			DebugWriteln("MiddleString=$MiddleString");
//			if ($Debugging) DebugWriteln("MiddleString=$MiddleString");
//DebugWriteln("2.2");
		
			TBL_DBTables::AddWhereClause($MiddleString,"RowIndex=$RowIndex ");
			TBL_DBTables::AddWhereClause($MiddleString,"ColumnIndex=$ColumnIndex");
					
			if ($Debugging) DebugWriteln("Miding string for tile spatial data=$MiddleString");

			$SelectString="SELECT TOP 1 $TBL_SpatialGridded.ID ".
				$MiddleString;
//					"AND RowIndex=$RowIndex ".
//					"AND ColumnIndex=$ColumnIndex ";
					
			if ($Debugging) DebugWriteln("See if at least 1 spatial data SelectString=$SelectString");
//DebugWriteln("See if at least 1 spatial data SelectString=$SelectString");

			$Set=$Database_SpatialData->Execute($SelectString);
//DebugWriteln("2.5");
					
//			$TotalCount=$Set->Field(1);
	
			if (($Set->FetchRow())) // have some data in the database for this cell, render a new tile
			{
//				DebugWriteln("hi");
				$Time=GetMicrotime();
			
//				$Layer=new STTileLayer(1,$RefX,$RefY,$Zoom,-$Zoom,STPIXEL_PALETTE,STPALETTE_STANDARD_SYMETRIC,$IconNumber);
				
				$SetupSceneDuration=GetMicrotime()-$Time;
//DebugWriteln("3");
				
				//
				
				if ($DoCounts)
				{
					$SelectString="SELECT COUNT(DISTINCT $TBL_SpatialGridded.ID) ".
						$MiddleString;
//							"AND RowIndex=$RowIndex ".
//							"AND ColumnIndex=$ColumnIndex ";
					
//					DebugWriteln("******** SelectString=$SelectString");
					
					$Set=$Database_SpatialData->Execute($SelectString);
					
					$PointCount=$Set->Field(1);
				}
				
				//
//DebugWriteln("4");
				
				$Time=GetMicrotime();
				
				$SelectString="SELECT GeometryData.STAsBinary() ".
						$MiddleString;
//							"AND RowIndex=$RowIndex ".
//							"AND ColumnIndex=$ColumnIndex ";
				
//				DebugWriteln("********* SelectString=$SelectString");
				
				if ($DoCounts) $SetupPoints=GetMicrotime()-$Time;				
			
				// create the scene
				
				$Time=GetMicrotime();
				
	//			if ($WebPath==null) $WebPath="/cwis438/temp/test_".$ColumnIndex."_".$RowIndex.".png";
				
//DebugWriteln("RootPath=$RootPath");
				$FolderPath=GetFolderPathFromFilePath($RootPath);
	
//DebugWriteln("FolderPath=$FolderPath");
				MakeSurePathExists($FolderPath);
				
				BlueSpray::RenderTile($RefX,$RefY,$CellWidth,-$CellHeight,$SelectString,$RootPath,$IconNumber);
				
//				$Layer->Render($SelectString,$RootPath);									
			
//				$Scene->Save($RootPath);
				
				if ($DoCounts) $SaveTime=GetMicrotime()-$Time;
				
				if (($DoCounts)&&($SetupPoints<0.0001)) $SetupPoints=0;
			}
			else 
			{
				$WebPath="/GODMTiles/Empty/Cell_Empty.png";
			}
		}
//		DebugWriteln("WebPath=".$WebPath);
//		DebugWriteln("SetupPoints=".$SetupPoints);
//		DebugWriteln("SetupVectors=".$SetupVectors);
	//	DebugWriteln("VectorLoadFromDatabase=".$VectorLoadFromDatabase);
//		DebugWriteln("SaveTime=".$SaveTime);
//		DebugWriteln("Duration=".(GetMicrotime()-$StartTime));
//	*/
//		DebugWriteln("WebPath=".$WebPath);
		return($WebPath);
	}

}

?>
