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
$req = curl_init("https://studip.tu-clausthal.de/index.php?again=yes");
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

// Load seminar list.
$req = curl_init("https://studip.tu-clausthal.de/meine_seminare.php");
curl_setopt($req, CURLOPT_COOKIEJAR, $cookie_file);
curl_setopt($req, CURLOPT_COOKIEFILE, $cookie_file);
curl_setopt($req, CURLOPT_RETURNTRANSFER, TRUE);
$res = curl_exec($req);
curl_close($req);
preg_match_all('/\"seminar_main\.php\?auswahl=([0-9a-f]+)\"/', $res, $matches);

// Iterate over all seminars.
$skip = true;
foreach ($matches[1] as $auswahl) {
  // TODO: Do not skip the first entry.
  if ($skip) {
    $skip = false;
    continue;
  }

  // Load the main seminar page.
  $req = curl_init("https://studip.tu-clausthal.de/seminar_main.php?auswahl=" . urlencode($auswahl));
  curl_setopt($req, CURLOPT_COOKIEJAR, $cookie_file);
  curl_setopt($req, CURLOPT_COOKIEFILE, $cookie_file);
  curl_setopt($req, CURLOPT_RETURNTRANSFER, TRUE);
  $res = curl_exec($req);
  curl_close($req);

  // Extract the folder id.
  if (preg_match('/\"folder.php\?cid=([0-9a-f]+)&/', $res, $match)) {
    print_r($match);
  }
  break;
}

// Delete the cookie file.
unlink($cookie_file);
