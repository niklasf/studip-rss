<?php

error_reporting(E_ALL);
require 'config.php';

// Create a cookie file.
$cookie_file = tempnam(STUDIP_RSS_TEMPDIR, "studip-rss-");

// Request the login page.
$req = curl_init("https://studip.tu-clausthal.de/index.php?again=yes");
curl_setopt($req, CURLOPT_COOKIEJAR, $cookie_file);
curl_setopt($req, CURLOPT_RETURNTRANSFER, TRUE);
$res = curl_exec($req);
curl_close($req);

// Extract the security token from the login page.
$dom = new DOMDocument();
$dom->loadHtml($res);
$xpath = new DOMXPath($dom);
$security_token = $xpath->query("//input[@name='security_token']/@value")->item(0)->textContent;
$login_ticket = $xpath->query("//input[@name='login_ticket']/@value")->item(0)->textContent;

// Login.
$req = curl_init(STUDIP_RSS_SOURCE . "index.php?again=yes");
curl_setopt($req, CURLOPT_COOKIEJAR, $cookie_file);
curl_setopt($req, CURLOPT_COOKIEFILE, $cookie_file);
curl_setopt($req, CURLOPT_RETURNTRANSFER, TRUE);
curl_setopt($req, CURLOPT_POSTFIELDS, array(
  "security_token" => $security_token,
  "login_ticket" => $login_ticket,
  "loginname" => STUDIP_RSS_USER,
  "password" => STUDIP_RSS_PASS,
  "login" => "Login",
));
$res = curl_exec($req);
curl_close($req);

print "<?xml version=\"1.0\" encoding=\"UTF-8\" ?>\n";
print "\n";
print "<rss version=\"2.0\">\n";
print "  <channel>\n";
print "    <title>StudIP</title>\n";
print "    <link>" . STUDIP_RSS_SOURCE . "index.php?again=yes</link>\n";
print "    <description>Dateien aus dem StudIP.</description>\n";
print "    <lastBuildDate>" . date('r') . "</lastBuildDate>\n";
print "    <pubDate>" . date('r') . "</pubDate>\n";
print "    <image>\n";
print "      <url>" . STUDIP_RSS_BASE . "logo.png</url>\n";
print "      <title>StudIP</title>\n";
print "      <link>" . STUDIP_RSS_SOURCE . "index.php?again=yes</link>\n";
print "    </image>\n";

// Load seminar list.
$req = curl_init(STUDIP_RSS_SOURCE . "meine_seminare.php");
curl_setopt($req, CURLOPT_COOKIEJAR, $cookie_file);
curl_setopt($req, CURLOPT_COOKIEFILE, $cookie_file);
curl_setopt($req, CURLOPT_RETURNTRANSFER, TRUE);
$res = curl_exec($req);
curl_close($req);
preg_match_all('/\"seminar_main\.php\?auswahl=([0-9a-f]+)\"/', $res, $matches);

// Iterate over all seminars.
foreach ($matches[1] as $auswahl) {
  // Load the main seminar page.
  $req = curl_init(STUDIP_RSS_SOURCE . "seminar_main.php?auswahl=" . urlencode($auswahl));
  curl_setopt($req, CURLOPT_COOKIEJAR, $cookie_file);
  curl_setopt($req, CURLOPT_COOKIEFILE, $cookie_file);
  curl_setopt($req, CURLOPT_RETURNTRANSFER, TRUE);
  $res = curl_exec($req);
  curl_close($req);

  // Extract the folder id.
  if (preg_match('/\"folder.php\?cid=([0-9a-f]+)&/', $res, $match)) {
    // Load the folder page.
    $req = curl_init(STUDIP_RSS_SOURCE . "folder.php?cid=" . urlencode($match[1]) . "&data%5Bcmd%5D=tree&cmd=all");
    curl_setopt($req, CURLOPT_COOKIEJAR, $cookie_file);
    curl_setopt($req, CURLOPT_COOKIEFILE, $cookie_file);
    curl_setopt($req, CURLOPT_RETURNTRANSFER, TRUE);
    $res = curl_exec($req);
    curl_close($req);

    // Find all download links.
    preg_match_all('/\<span id=\"file_([0-9a-f]+)_header.*?\>(.*?)\<\/span>.*?\<a.*?\>(.*?)\<\/a\>/', $res, $matches, PREG_SET_ORDER);
    foreach ($matches as $match) {
      $filename = "files/" . $match[1];

      // Download the file.
      if (!file_exists($filename)) {
        $req = curl_init(STUDIP_RSS_SOURCE. "sendfile.php?force_download=1&type=0&file_id=" . urlencode($match[1]));
        curl_setopt($req, CURLOPT_COOKIEJAR, $cookie_file);
        curl_setopt($req, CURLOPT_COOKIEFILE, $cookie_file);
        curl_setopt($req, CURLOPT_RETURNTRANSFER, TRUE);
        $res = curl_exec($req);
        curl_close($req);
        file_put_contents($filename, $res);
      }

      $filesize = filesize($filename);

      $finfo = finfo_open(FILEINFO_MIME_TYPE);
      $filetype = finfo_file($finfo, $filename);
      finfo_close($finfo);

      print "    <item>\n";
      print "      <title>" . $match[2] . "</title>\n";
      print "      <link>" . STUDIP_RSS_BASE . $filename . "</link>\n";
      print "      <guid isPermaLink=\"false\">" . $match[1] . "</guid>\n";
      print "      <enclosure url=\"" . STUDIP_RSS_BASE . $filename . "\" length=\"" . $filesize . "\" type=\"" . $filetype . "\" />\n";
      print "    </item>\n";
    }
  }
}

print "  </channel>\n";
print "</rss>\n";

// Delete the cookie file.
unlink($cookie_file);
