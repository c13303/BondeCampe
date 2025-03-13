<?php
// Get directory parameter
$name = filter_input(INPUT_GET, 'dir', FILTER_SANITIZE_STRING);
if(!$name) {
    die('No directory specified');
}

// Define paths
$zipname = 'archive/' . $name . '.zip';
$rootPath = realpath('audio/' . $name);

// Include required files
require('include.php');

// Check if archive folder exists, create if not
if (!is_dir('archive') && !mkdir('archive', 0755, true)) {
    die("Error: Cannot create archive directory");
}

// Create ZIP file if it doesn't exist
if (!file_exists($zipname)) {
    // Initialize archive object
    $zip = new ZipArchive();
    if (!$zip->open($zipname, ZipArchive::CREATE | ZipArchive::OVERWRITE)) {
        die("Failed to create archive");
    }
    
    // Create recursive directory iterator
    $files = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($rootPath),
        RecursiveIteratorIterator::LEAVES_ONLY
    );
    
    // Add files to the zip
    foreach ($files as $name => $file) {
        // Skip directories
        if (!$file->isDir()) {
            $filePath = $file->getRealPath();
            $relativePath = substr($filePath, strlen($rootPath) + 1);
            
            // Skip .rar files
            if (!strstr($filePath, '.rar')) {
                $zip->addFile($filePath, $relativePath);
            }
        }
    }
    
    // Close and save the zip file
    $zip->close();
}

// Check if file was created successfully
if (!file_exists($zipname)) {
    die("Error: Failed to create zip file");
}

// Download the file
header("Pragma: public");
header("Expires: 0");
header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
header('Content-Type: application/zip');
header('Content-Disposition: attachment; filename=charlestorris-' . $name . '.zip');
readfile($zipname);

// Log the download
if (function_exists('logIt')) {
    logIt('ZIP-' . $name);
}
?>