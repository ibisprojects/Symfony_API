<?php

namespace Classes\Utilities;

class FileUtil {
    public static function DeleteFolder($FolderPath)
    //
    // 	WARNING: Use this function with great care!  This function will delete all the folders and
    //	files below the specified folder and then deletes the folder
    //
    {
        if (!is_dir($FolderPath) || strlen($FolderPath) < 36) {
            return false; // incorrect path
        }

        $DirectoryHandle = opendir($FolderPath);

        while (false !== ($FileName = readdir($DirectoryHandle))) {
            if ($FileName == "." || $FileName == "..") {
                continue;
            }

            $FilePath = "$FolderPath/$FileName";

            if (is_dir($FilePath)) {
                self::DeleteFolder($FilePath);
            } else if (file_exists($FilePath)) {
                unlink($FilePath);
            }
        }

        closedir($DirectoryHandle);

        // remove the top level directory
        return rmdir($FolderPath);
    }
}
