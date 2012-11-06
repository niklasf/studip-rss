<?php

header("Content-Type: application/rss+xml");

if (!file_exists('feed.xml') || filemtime('feed.xml') < time() - 60 * 5) {
  ob_start();
  require 'cron.php';
  file_put_contents('feed.xml', ob_get_contents());
  ob_end_flush();
}
else {
  print file_get_contents('feed.xml');
}
