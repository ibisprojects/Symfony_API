<?php

//**************************************************************************************
// FileName: Date.php
//
//	This class was created basically because the PHP date/time is based on a Timestamp
//	that is the number of seconds from some day in 1970.  Since we have dates before
//	this date we had to create another complete system for managing dates.
//	This was taken from the earlier work in ASP.
//
//	Creating a new Date object saves the current time into the class variables.
//	To refresh the current time after initial creation, call $Object->Date().
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

define("DATE_UNKNOWN",0);
define("DATE_SQL",1);
define("DATE_US",2);

function GetPrintDateFromSQLServerDate($SQLServerDate)
{
	$DateString="";

	if ($SQLServerDate!=null)
	{
		$Date=new Date();
		$Date->SetDateFromSQLString($SQLServerDate);
		$DateString=$Date->GetPrintDate();
	}
	return($DateString);
}

function GetPrintDateTimeFromSQLServerDate($SQLServerDate)
{
	$Date=new Date();
	$Date->SetDateFromSQLString($SQLServerDate);
	return($Date->GetPrintDate()." ".$Date->GetPrintTime());
}

function GetHoursMinutesSecondsFromSeconds(&$Hours,&$Minutes,&$Seconds)
{
//	DebugWriteln("Seconds=$Seconds");

	$Minutes=(int)((double)$Seconds/60.0);
	$Seconds=(int)($Seconds-($Minutes*60));

	$Hours=(int)((double)$Minutes/60.0);
	$Minutes=$Minutes-($Hours*60);
}


//**************************************************************************************
// Class Definition
//**************************************************************************************
class Date
{
	// these are constant arrays

	var $MonthStrings=array("","January","February","March","April","May","June",
		"July","August","September","October","November","December");

	var $DayStrings=array("0","1st","2nd","3rd","4th","5th","6th","7th","8th","9th",
		"10th","11th","12th","13th","14th","15th","16th","17th","18th","19th",
		"20th","21st","22nd","23th","24th","25th","26th","27th","28th","29th",
		"30th","31st");

	var $HourStrings=array("12:00am","1:00am","2:00am","3:00am","4:00am","5:00am","6:00am",
		"7:00am","8:00am","9:00am","10:00am","11:00am","12:00pm",
		"1:00pm","2:00pm","3:00pm","4:00pm","5:00pm","6:00pm",
		"7:00pm","8:00pm","9:00pm","10:00pm","11:00pm","12:00am");

	var $DOWStrings=array("Sunday","Monday","Tuesday","Wednesday","Thursday","Friday","Saturday");

	// attributes

	var $Year=0; // just regular year values, 1970, 2000, etc.
	var $Month=0; // 1=Jan
	var $Day=0; // 1=1st day of the month, etc.

	var $Hour=0; // 24-hour clock, 0=12:00am
	var $Minute=0;
	var $Second=0;
	var $Millisecond=0;

	// constructors

	function Date($NewYear=null,$NewMonth=null,$NewDay=null,$NewHour=null,$NewMinute=null,
		$NewSecond=null,$Millisecond=null)
	{
//		DebugWriteln("In constructor NewYear=$NewYear");

		$TimeStamp=(float)time();
//		$Microtime=GetMicrotime();

    	list($usec, $sec)=explode(" ", microtime());
  		$Microtime=(float)$usec+(float)$sec;
// 		DebugWriteln("TimeStamp=$TimeStamp");
//		DebugWriteln("Microtime=$Microtime");
		$Temp=(int)(($Microtime-$TimeStamp)*1000);
//		DebugWriteln("Millisecond=".$Temp);

		$this->SetDateFromTimeStamp($TimeStamp);

		$this->Millisecond=$Temp;

		if ($this->Millisecond<0) $this->Millisecond=0;
		if ($this->Millisecond>999) $this->Millisecond=999;

//		DebugWriteln("Millisecond=".$this->Millisecond);

		if ($NewYear!==null) $this->Year=(int)$NewYear;
		if ($NewMonth!==null) $this->Month=(int)$NewMonth;
		if ($NewDay!==null) $this->Day=(int)$NewDay;
		if ($NewHour!==null) $this->Hour=(int)$NewHour;
		if ($NewMinute!==null) $this->Minute=(int)$NewMinute;
		if ($NewSecond!==null) $this->Second=(int)$NewSecond;
		if ($Millisecond!==null) $this->Millisecond=(int)$Millisecond;
//		DebugWriteln("2 Millisecond=".$this->Millisecond);

//		DebugWriteln("In constructor Year=$this->Year");
	}

	// public functions

	function SetDate($NewYear=0,$NewMonth=1,$NewDay=1,$NewHour=0,$NewMinute=0,$NewSecond=0,
		$Millisecond=0)
	{
		$this->Year=(int)$NewYear;
		$this->Month=(int)$NewMonth;
		$this->Day=(int)$NewDay;
		$this->Hour=(int)$NewHour;
		$this->Minute=(int)$NewMinute;
		$this->Second=(int)$NewSecond;
		$this->Millisecond=(int)$Millisecond;

		//DebugWriteln("SetDate Year=$this->Year");
		//DebugWriteln("SetDate Month=$this->Month");
		//DebugWriteln("SetDate Day=$this->Day");
		//DebugWriteln("SetDate Hour=$this->Hour");
		//DebugWriteln("SetDate Minute=$this->Minute");
		//DebugWriteln("SetDate Second=$this->Second");
	}

	//**********************************************************************
	//	Gets
	//**********************************************************************

	function GetMonthStrings() { return($this->MonthStrings); }
	function GetDayStrings() { return($this->DayStrings); }
	function GetDOWStrings() { return($this->DOWStrings); }
	function GetHourStrings() { return($this->HourStrings); }

	function GetMonthString() { return($this->MonthStrings[$this->Month]); }
	function GetDayString() { return($this->DayStrings[$this->Day]); }
	function GetHourString() { return($this->HourStrings[$this->Hour]); }
	function GetDOWString($DOW) { return($this->DOWStrings[$DOW]); }

	function Get12Hour()
	// Returns the hour in a 12-hour format
	{
		$NewHour=$this->Hour;

		if ($NewHour>12) $NewHour-=12;

		if ($NewHour==0) $NewHour=12; // it's 12:00am in the morning, and 12:00pm at night

		return($NewHour);
	}

	function GetAMPM()
	// Returns am or pm for based on the hour
	{
		if ($this->Hour>=12) return("pm");
		else return("am");
	}

	//**********************************************************************
	// timestamp functions
	//**********************************************************************

	function GetTimeStamp()
	{
//		DebugWriteln("1-----------------------------------------");
//		DebugWriteln("Hour=".$this->Hour);
//		DebugWriteln("Minute=".$this->Minute);
//		DebugWriteln("Second=".$this->Second);
//		DebugWriteln("Month=".$this->Month);
//		DebugWriteln("Day=".$this->Day);
//		DebugWriteln("Year=".$this->Year);

		$Timestamp=mktime($this->Hour,$this->Minute,$this->Second,$this->Month,$this->Day,$this->Year);
//		$Timestamp=gmmktime($this->Hour,$this->Minute,$this->Second,$this->Month,$this->Day,$this->Year);
//		DebugWriteln("Timestamp=".$Timestamp);
//		$DateArray=getdate($Timestamp);
//		DumpArray($DateArray);
//		DebugWriteln("1-----------------------------------------");

		$Timestamp+=($this->Millisecond/1000);

		return($Timestamp);
	}

	function SetDateFromTimeStamp($TimeStamp)
	{
//		DebugWriteln("SetDateFromTimeStamp Timestamp=$Timestamp");

		$DateArray=getdate($TimeStamp);
//		DumpArray($DateArray);

		$Millisecond=($TimeStamp%1);

		$this->SetDate($DateArray["year"],$DateArray["mon"],$DateArray["mday"],
			$DateArray["hours"],$DateArray["minutes"],$DateArray["seconds"],$Millisecond);
	}

	function GetDOW()
	{
		$TimeStamp=$this->GetTimeStamp();

		$DateArray=getdate($TimeStamp);

		$DOW=$DateArray["wday"];

		return($DOW);
	}

	//**********************************************************************
	// SQL String functions
	//**********************************************************************

	function SetDateFromSQLString($SQLString)
	//
	//	"YY-MM-DD HH:MM:SS is returned from SQLServer
	//
	{
//		DebugWriteln("SQLString=".$SQLString);
		if (($SQLString==null)||($SQLString==""))
		{
			$this->SetDate(0,0,0,0,0,0);
		}
		else
		{
			$Parts=explode(" ",$SQLString);

	//		DumpArray($Parts);
//			DebugWriteln("count=".count($Parts));

			if ((count($Parts)==2))
			{
				$Date=explode("-",$Parts[0]);

				$Time=explode(":",$Parts[1]);

				if ((count($Date)==3)||(count($Time)==3))
				{
					$this->SetDate($Date[0],$Date[1],$Date[2],// Year,Month,Day
						$Time[0],$Time[1],$Time[2]);
				}
				else
				{
					echo("<b>* Error: Date::SetDateFromSQLString(): Invalid SQL String Format2</b>");
				}
			}
			else if ((count($Parts)==1)) // just the date
			{
				$Date=explode("-",$Parts[0]);

				if ((count($Date)==3))
				{
					$this->SetDate($Date[0],$Date[1],$Date[2],// Year,Month,Day
						0,0,0);
				}
				else
				{
					echo("<b>* Error: Date::SetDateFromSQLString(): Invalid SQL String Format2</b>");
				}
			}
			else
			{
				echo("<b>* Error: Date::SetDateFromSQLString(): Invalid SQL String Format1</b>");
			}
		}
	}

	function GetSQLString($IncludeMilliseconds=false,$IncludeTime=true)
	//
	//	Either of the following forms works to insert a string into SQL Server
	//	We use the "YY-MM-DD HH:MM:SS format because this is the format that is returned from SQLServer
	//	Using sprintf to format output string with leading zeroes in each field.
	//
	{
		$Seconds=$this->Second;

		if ($IncludeMilliseconds==true)
		{
			$Seconds+=((float)$this->Millisecond/1000.0);

			$SQLDate=sprintf("%04d-%02d-%02d %02d:%02d:%02.3f", $this->Year,$this->Month,$this->Day,
				$this->Hour,$this->Minute,$Seconds);
		}
		else if ($IncludeTime)
		{
			$SQLDate=sprintf("%04d-%02d-%02d %02d:%02d:%02d", $this->Year,$this->Month,$this->Day,
				$this->Hour,$this->Minute,$Seconds);
		}
		else // just give the date
		{
			$SQLDate=sprintf("%04d-%02d-%02d", $this->Year,$this->Month,$this->Day);
		}

		return($SQLDate);
	}

	//**********************************************************************
	// General Date functions
	//**********************************************************************

	function GetDateFormat($Date)
	{
		$Result=DATE_UNKNOWN;

		// try a US-date

		$Year=-1;
		$Month=-1;
		$Day=-1;

		sscanf($Date,"%d/%d/%d",$Month,$Day,$Year);

		if (($Month>=1)&&($Month<=12)&&
			($Day>=1)&&($Day<=31)&&
			($Year>=1700)&&($Year<=2050))
		{
			$Result=DATE_US;
		}
		else
		{
			// try a SQL date

			sscanf($Date,"%d-%d-%d",$Year,$Month,$Day);

			if (($Month>=1)&&($Month<=12)&&
				($Day>=1)&&($Day<=31)&&
				($Year>=1700)&&($Year<=2050))
			{
				$Result=DATE_SQL;
			}
		}
		return($Result);
	}

	function SetDateFromString($DateString)
	{
		$Format=Date::GetDateFormat($DateString);

		switch ($Format)
		{
		case DATE_US:
//			DebugWriteln("DATE_US: ".$DateString);
			sscanf($DateString,"%d/%d/%d",$this->Month,$this->Day,$this->Year);
			break;
		case DATE_SQL:
//			DebugWriteln("SQL: ".$DateString);
			sscanf($DateString,"%d-%d-%d",$this->Year,$this->Month,$this->Day);
			break;
		}
//			DebugWriteln("Day=".$this->Day." Month=".$this->Month." Year=".$this->Year);
	}
	//**********************************************************************
	// Print String functions
	//**********************************************************************

	function GetPrintTime()
	{
		$NewTime=$this->Get12Hour($this->Hour);
		$NewTime.=":";

		if ($this->Minute<10) $NewTime.="0";

		$NewTime.=$this->Minute;

		$NewTime.=" ";
		$NewTime.=$this->GetAMPM($this->Hour);

		return($NewTime);
	}

	function GetPrintDate()
	{
		$NewDate="";

		$NewDate=$this->GetMonthString();
		$NewDate.=" ";
		$NewDate.=$this->GetDayString();
		$NewDate.=", ";
		$NewDate.=$this->Year;

		return($NewDate);
	}
	function GetPrintDateTime()
	{
		return($this->GetPrintDate()." ".$this->GetPrintTime());
	}

	//**********************************************************************
	// Functions to date stamp file names (uses underlines and spaces)
	//**********************************************************************

	function GetFileTime()
	{
		if ($this->Hour<10) $NewTime.="0";
		$NewTime=$this->Hour;
		$NewTime.="_";

		if ($this->Minute<10) $NewTime.="0";
		$NewTime.=$this->Minute;
		$NewTime.="_";

		if ($this->Second<10) $NewTime.="0";
		$NewTime.=$this->Second;

		return($NewTime);
	}

	function GetFileDate()
	{
		$NewDate="";

		$NewDate=$this->Year;
		$NewDate.="_";
		$NewDate.=$this->Month;
		$NewDate.="_";
		$NewDate.=$this->Day;

		return($NewDate);
	}
	function GetFileDateTime()
	{
		return($this->GetFileDate()." ".$this->GetFileTime());
	}
	//**********************************************************************
	// QC functions
	//**********************************************************************

	function CheckDate()
	//
	//	Returns NULL if this is a valid date, and error string otherwise
	//	Invalid dates include ones that are in the future or contain invalid
	//	values such as a day of 0 or a second of 60 (range is 0 to 59)
	//
	{
		$Error=null;

		$Today=new Date();

		if ($this->GreaterThan($Today))
		{
			$Error="Date is in the future";
		}
		else
		{
//			DebugWriteln("this->Millisecond=$this->Millisecond");

			if (($this->Year<1000)||($this->Year>2100)) $Error="Year is out of range";
			else if (($this->Month<1)||($this->Month>31)) $Error="Month is out of range";
			else if (($this->Day<1)||($this->Day>31)) $Error="Day is out of range";
			else if (($this->Hour<0)||($this->Hour>23)) $Error="Hour is out of range";
			else if (($this->Minute<0)||($this->Minute>59)) $Error="Minute is out of range";
			else if (($this->Second<0)||($this->Second>59)) $Error="Second is out of range";
			else if (($this->Millisecond<0)||($this->Millisecond>999)) $Error="Millisecond is out of range";
		}
		return($Error);
	}

	//**********************************************************************
	// Static functions
	//**********************************************************************

	function GetPrintDateFromSQLDate($SQLDate)
	{
		$Date=new Date();

		$Date->SetDateFromSQLString($SQLDate);

		return($Date->GetPrintDate());
	}

		//**********************************************************************
	// Compare functions
	//**********************************************************************

	function Equal($Date)
	{
		$Flag=false;
		if (($this->Year==$Date->Year)&&
			($this->Month==$Date->Month)&&
			($this->Day==$Date->Day)&&
			($this->Hour==$Date->Hour)&&
			($this->Minute==$Date->Minute)&&
			($this->Second==$Date->Second))
		{
			$Flag=true;
		}

		return($Flag);
	}

	function GreaterThan($Date)
	{
		$Flag=false;
		if ($this->Year>$Date->Year)
		{
			$Flag=true;
		}
		elseif ($this->Year==$Date->Year)
		{
			if ($this->Month>$Date->Month)
			{
				$Flag=true;
			}
			elseif ($this->Month==$Date->Month)
			{
				if ($this->Day>$Date->Day)
				{
					$Flag=true;
				}
				elseif ($this->Day==$Date->Day)
				{
					if ($this->Hour>$Date->Hour)
					{
						$Flag=true;
					}
					elseif ($this->Hour==$Date->Hour)
					{
						if	($this->Minute>$Date->Minute)
						{
							$Flag=true;
						}
						elseif ($this->Minute==$Date->Minute)
						{
							if ($this->Second>$Date->Second)
							{
								$Flag=true;
							}
							elseif ($this->Second==$Date->Second)
							{
								if ($this->Millisecond>$Date->Millisecond)
								{
									$Flag=true;
								}
							}
						}
					}
				}
			}
		}

		return($Flag);
	}

	function LessThan($Date)
	{
		$Flag=false;
		if ($this->Year<$Date->Year)
		{
			$Flag=true;
		}
		elseif ($this->Year==$Date->Year)
		{
			if ($this->Month<$Date->Month)
			{
				$Flag=true;
			}
			elseif ($this->Month==$Date->Month)
			{
				if ($this->Day<$Date->Day)
				{
					$Flag=true;
				}
				elseif ($this->Day==$Date->Day)
				{
					if ($this->Hour<$Date->Hour)
					{
						$Flag=true;
					}
					elseif ($this->Hour==$Date->Hour)
					{
						if	($this->Minute<$Date->Minute)
						{
							$Flag=true;
						}
						elseif ($this->Minute==$Date->Minute)
						{
							if ($this->Second<$Date->Second)
							{
								$Flag=true;
							}
						}
					}
				}
			}
		}

		return($Flag);
	}
	public function GetCalender()
	{
		$Calender="<div class='Calendar'>". // style='float:left;width:100px;background-color:#888888' class='BrowserBackground'
			"<center>".
			"<font size=2>".$this->GetDOWString($this->GetDOW())."</font><br>".
			"<font size=6>".$this->Day."</font><br>".
			"<font size=2>".$this->GetMonthString()."</font><br>".
			"<font size=3>".$this->Year."</font><br>".
			"</center></div>";

		return($Calender);
	}
}

?>
