<?php
session_start();
error_reporting(0);
include("config.php");
include("session.php");
include("template.class.php");
include("template.settings.php");
include("functions.php");
include("faucethub.php");

if(!$_COOKIE['refer']){
	if($_GET['ref'] != ""){
		$refer = $mysqli->real_escape_string($_GET['ref']);
		setcookie("refer", $refer,time()+(3600*24));
	}
}

// Cloudflare IP

if (isset($_SERVER["HTTP_CF_CONNECTING_IP"])) {
  $_SERVER['REMOTE_ADDR'] = $_SERVER["HTTP_CF_CONNECTING_IP"];
}

// CSRF PROTECTION

if($_SESSION['token'] == ""){
	$_SESSION['token'] = md5(md5(uniqid().uniqid().mt_rand()));
}
?>