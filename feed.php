<?php

// Show all errors.
error_reporting(E_ALL);
ini_set('display_errors', TRUE);

// Set the content type header.
header("Content-Type: application/rss+xml");

if (!file_exists('feed.xml') || filemtime('feed.xml') < time() - 60 * 5) {
  // Refresh the cache.
  ob_start();
  require 'cron.inc';
  file_put_contents('feed.xml', ob_get_contents());
  ob_end_flush();
}
else {
  // Send the cached file.
  print file_get_contents('feed.xml');
}
