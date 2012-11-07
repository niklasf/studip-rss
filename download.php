<?php

error_reporting(E_ALL);
ini_set('display_errors', TRUE);

if (!isset($_GET['file']) || !preg_match('/^[0-9a-h]+$/', $_GET['file']) || !file_exists('files/' . $_GET['file'])) {
  header("HTTP/1.0 404 Not Found");
  die('Not found');
}

$finfo = finfo_open(FILEINFO_MIME_TYPE);
$content_type = finfo_file($finfo, 'files/' . $_GET['file']);
finfo_close($finfo);

require 'vendor/ExtensionGuesserInterface.php';
require 'vendor/MimeTypeExtensionGuesser.php';
$guesser = new \Symfony\Component\HttpFoundation\File\MimeType\MimeTypeExtensionGuesser();
$file_name = $_GET['file'] . '.' . $guesser->guess($content_type);

$file_size = filesize('files/' . $_GET['file']);

header("Content-Description: File Transfer");
header("Content-Type: $content_type");
header("Content-Disposition: attachment; filename=$file_name");
header("Content-Transfer-Encoding: binary");
header("Content-Length: $file_size");

readfile('files/' . $_GET['file']);
