<?php

namespace Classes\DBTable;

//**************************************************************************************
// FileName: Upload.php
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

//******************************************************************************
// Definitions
//******************************************************************************
// "Search For" options
use Classes\TBLDBTables;

//******************************************************************************
// Class
//******************************************************************************

class Upload  {
    function FileOKToUpload($Index,$NumFiles,$MaxSize)
    //
    // This function is only called by MoveUploadedFiles() below.
    //
    {
        //$Result=RESULT_FILE_UNSAFE;

        //DebugWriteln("MaxSize=".$MaxSize);
        //DebugWriteln("NumFiles=".$NumFiles);

        if (gettype($_FILES['userfile']['name'])!="array")
        {
                $FileName=$_FILES['userfile']['name'];
				$FileName = str_replace(' ', '', $FileName);
                $FileSize=$_FILES['userfile']['size'];
        }
        else
        {
                $FileName=$_FILES['userfile']['name'][$Index];
				$FileName = str_replace(' ', '', $FileName);
                $FileSize=$_FILES['userfile']['size'][$Index];
        }

        if ($FileSize==0) $Result="File too large"; // jjg - PHP does this, RESULT_FILE_ZERO_SIZE;
        else
        {
            if ($FileSize>$MaxSize) $Result="File too large";
            else
            {
                $Extension=strrchr($FileName,".");

                $Extension=substr($Extension,1);

                $Extension=strtolower($Extension);

                    if (($Extension=='jpg')||
                        ($Extension=='png')||
                        ($Extension=='jpeg')||
                        ($Extension=='gif')||
                        ($Extension=='tif'))
                {
                    $Result="RESULT_OKAY";
                }
                else
                {
                    $Result="RESULT_FILE_UNSAFE";
                }
            }
        }
        return($Result);
    }


    //****************************************************************************
    static function MoveUploadedFiles($NumFiles,$DestinPath,$MaxSize,$FileArray=null, $loggerService=null)
    //
    //	Moves files from the upload temporary folder to the specified destination
    //	folder.
    //		$NumFiles - Number of files being uploaded (only checked against 1)
    //		$DestinPath - Where the file goes after we do a quick security check
    //
    //	Returns the first error that occurred.
    //
    {
        $Count=0;
        $Result="RESULT_OKAY";

		// Set up log file for debugging
        $logger = $loggerService->logger;

        $logger->info("--- Upload File Image Processing Start (N=$NumFiles) ---");

		//$Image1=$_FILES['userfile']['name'][0];
		//$Image2=$_FILES['userfile']['name'][1];
		//file_put_contents($LogFile,"ImageName1=$Image1,ImageName2=$Image2\r\n",FILE_APPEND);

        // get the parameters from the upload

        if ($NumFiles>0)
        {
            do
            {
                $FileName="";

                if (gettype($_FILES['userfile']['name'])!="array")
                {
                    $logger->info("Image sent as single image - not array");

                    $FileName=$_FILES['userfile']['name'];
                    $FileName = str_replace(' ', '', $FileName);
                    $MimeType=$_FILES['userfile']['type'];
                    $FileSize=$_FILES['userfile']['size'];
                    $Error=$_FILES['userfile']['error'];
                    $TempFileName=$_FILES['userfile']['tmp_name'];
                    $TempFileName = str_replace(' ', '', $TempFileName);

                    $logger->info("Processing Image (FileName=$FileName)");
                }
                else
                {
                    $logger->info("Image sent as array");

                    $FileName=$_FILES['userfile']['name'][$Count];
                    $FileName = str_replace(' ', '', $FileName);
                    $MimeType=$_FILES['userfile']['type'][$Count];
                    $FileSize=$_FILES['userfile']['size'][$Count];
                    $Error=$_FILES['userfile']['error'][$Count];
                    $TempFileName=$_FILES['userfile']['tmp_name'][$Count];
                    $TempFileName = str_replace(' ', '', $TempFileName);

                    $logger->info("***Processing Image (n=$Count, FileName=$FileName, TempFileName=$TempFileName, Error=$Error)***");
                }

                if (($FileName!="")&&($Count<$NumFiles))
                {
                    Upload::FileOKToUpload($Count,$NumFiles,$MaxSize);

                    //DebugWriteln("Result2=".$Result);

                    if ($Result=="RESULT_OKAY")
                    {
                        // move the file to the projects directory

                        $FullDestinPath = $DestinPath . $FileName;

                        $logger->info("Image Full DestinPath = $FullDestinPath");

                        if (move_uploaded_file($TempFileName, $FullDestinPath))  // file successfully moved
                        {
                            $logger->info("Image (Name=$FileName) was moved successfully to: $FullDestinPath");

                            if (is_array($FileArray))
                            {
                                    $FileArray[$Count]=$FileName;
                            }
                            $Count++;
                        }
                        else
                        {
                            $logger->info("Image (Name=$FileName) was not moved successfully.");

                            $FileName=""; // causes us to exit
                        }
                    }
                }
            }
            while (($FileName!="")&&($Result=="RESULT_OKAY")&&($Count<$NumFiles));
        }
        return($Result);
    }
}

?>
