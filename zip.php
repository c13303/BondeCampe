<?php

//require('include.php');
/*
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
 * 
 */

$name = filter_input(INPUT_GET,'dir',FILTER_SANITIZE_STRING);
if(!$name){
    die();
}

$zipname = 'archive/' . $name . '.zip';
// Get real path for our folder
$rootPath = realpath('audio/' . $name);


require('include.php');



if (!file_exists($zipname) ) {
   

    // echo $rootPath.'<br/>';
// Initialize archive object
    $zip = new ZipArchive();
    if (!$zip->open($zipname, ZipArchive::CREATE | ZipArchive::OVERWRITE)) {
        die("Failed to create archive\n");
    }




// Create recursive directory iterator
    /** @var SplFileInfo[] $files */
    $files = new RecursiveIteratorIterator(            
            new RecursiveDirectoryIterator($rootPath), RecursiveIteratorIterator::LEAVES_ONLY
    );
  
    
    foreach ($files as $name => $file) {
        // Skip directories (they would be added automatically)
        if (!$file->isDir()) {
            // Get real and relative path for current file
            $filePath = $file->getRealPath();
            echo $filePath . '<br/>';
            $relativePath = substr($filePath, strlen($rootPath) + 1);
            
            if (!strstr($filePath, '.rar')) {
                // Add current file to archive
                if (!$u = $zip->addFile($filePath, $relativePath)) {
                    die("Failed to add to archive\n");
                } else {
                    // echo '> ADDED <br/>';
                }
            }
        }
    }

// Zip archive will be created only after closing object
    var_dump($zip);
    $zip->close();
}

sleep(5);
header("Pragma: public");
header("Expires: 0");
header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
header('Content-Type: application/zip');
header('Content-Disposition: attachment; filename=charlestorris-' . $name . '.zip');
readfile($zipname);


logIt('ZIP-'.$name);

  

?>