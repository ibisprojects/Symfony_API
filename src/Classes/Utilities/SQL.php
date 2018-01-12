<?php

//**************************************************************************************
//  SQL.php - static wrapper class for SQL utilities
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

namespace Classes\Utilities;

define("NOT_SPECIFIED"," --- @@ Not Specified @@ --- ");

class SQL
{
	//**************************************************************************************
	// functions to get values that will be inserted into INSERT or UPDATE SQL strings
	// These functions insure SQL injection errors are blocked and NULLs are handled correctly
	//	(i.e. a PHP null is converted into a SQL NULL)
	//**************************************************************************************
   	public static function GetBit($Value)
    {
    	if ($Value!==NOT_SPECIFIED)
    	{
	    	if ($Value) $Value=1;
	    	else $Value=0;

	     	if ($Value===null) $Value="NULL";
	    	else $Value="'".$Value."'";
    	}
    	return($Value);
    }

   	public static function GetInt($Value)
    {
     	if ($Value!==NOT_SPECIFIED)
    	{
		   	$Value=SQL::SafeInt($Value);

	     	if ($Value===null) $Value="NULL";
	    	else $Value="'".$Value."'";
    	}
    	return($Value);
    }

    public static function GetFloat($Value)
    {
      	if ($Value!==NOT_SPECIFIED)
    	{
		   	$Value=SafeFloat($Value);

	     	if ($Value===null) $Value="NULL";
	    	else $Value="'".$Value."'";
    	}
    	return($Value);
    }

    public static function GetString($Value)
    {
      	if ($Value!==NOT_SPECIFIED)
    	{
	    	$Value=SafeString($Value);

	     	if ($Value===null) $Value="NULL";
	    	else $Value="'".$Value."'";
    	}
    	return($Value);
    }

    public static function GetDate($Value)
    {
      	if ($Value!==NOT_SPECIFIED)
    	{
	    	$Value=SafeDate($Value);

	     	if ($Value===null) $Value="NULL";
	    	else $Value="'".$Value."'";
    	}
    	return($Value);
    }

    //**************************************************************************************
    // Functions to make values safe to put in SQL Strings
    // These functions strip values of any possible illegal instructions
    //**************************************************************************************
    public static function SafeInt($Value)
    {
        if ($Value!==null)
        {
            if (is_array($Value))
            {
                $Temp=sscanf($Value[0],"%d");
            } else {
                $Temp=sscanf($Value,"%d");
            }
            $Temp2=(int)$Temp[0];
        }
        else
        {
            $Temp2=$Value;
        }
        return($Temp2);
    }

    public static function SafeFloat($Value)
    {
        if ($Value!==null)
        {
            $Temp=sscanf($Value,"%f");

            $Temp2=(float)$Temp[0];
        }
        else
        {
            $Temp2=$Value;
        }
        return($Temp2);
    }

    public static function SafeString($Value)
        // escapes ', ", \, and NUL unless magic_quotes_gpc is ON in php.ini
        // 2/23/2005 - MS-SQL works differently than other databases - the backslash does not
        // escape special characters.  To enter a single quote in a string, use two single
        // quotes together.  Both backslash and double quote are accepted without problem.  Ray.
        //
    {
        if ($Value!==null)
        {
            //	DebugWriteln("Value1=$Value");

            $Value=str_replace("\"","'",$Value);

            $Value=UnsafeString($Value); // first make sure all occurances of N quotes are reduced to 1
            //	DebugWriteln("Value2=$Value");

            //	if (get_magic_quotes_gpc())
            //		$Temp=$Value;
            //	else
            //		$Temp=addslashes($Value);

            $Value=str_replace("'","''",$Value);
        }
        return($Value);
    }

    public static function SafeDate($Value)
        // input must be in raw database date format; numerical values are extracted
        // and reassembled into the same format, leaving all else behind.
    {
        if ($Value!==null)
        {
            $Temp=sscanf($Value,"%4d-%2d-%2d %2d:%2d:%2d.%3d");
            $Out=sprintf("%04d-%02d-%02d %02d:%02d:%02d", $Temp[0],$Temp[1],$Temp[2],$Temp[3],$Temp[4],$Temp[5]);
            //	$Out=$Temp[0]."-".$Temp[1]."-".$Temp[2]." ".$Temp[3].":".$Temp[4].":".$Temp[5];
            if ($Temp[6])
                $Out.=sprintf(".%03d",$Temp[6]);
        }
        else
        {
            $Out=$Value;
        }
        return($Out);
    }

    public static function UnsafeString($Value)
        // Reverses single-quote escaping done by SafeString - use it just before displaying
        // a string in a form that was taken from the database.
        // NEVER USE THIS FUNCTION ON VALUES GOING INTO THE DATABASE
    {
        $Count=0;

        do
        {
            //		DebugWriteln("UnsafeString() Value3=$Value");
            $Value=str_replace("''","'",$Value,$Count);
            //		DebugWriteln("UnsafeString() Value4=$Value, Count=$Count");
        }
        while ($Count>0);

        return($Value);
    }
}

?>
