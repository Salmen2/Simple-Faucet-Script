<?php
session_start();
error_reporting(0);
include("config.php");
include("session.php");
include("template.class.php");
include("template.settings.php");
include("functions.php");
include("expresscrypto.library.php");
include("faucetpay.library.php");
include("blockio.library.php");
include("rc/autoload.php");

if(!$_COOKIE['refer']){
	if($_GET['ref'] != ""){
		$refer = $mysqli->real_escape_string($_GET['ref']);
		setcookie("refer", $refer,time()+(3600*24));
	} else if($_GET['r'] != ""){
		$addyRefer = $mysqli->real_escape_string($_GET['r']);
		$checkReferID = $mysqli->query("SELECT id FROM faucet_user_list WHERE address = '$addyRefer' OR ec_userid = '$addyRefer'")->fetch_assoc()['id'];
		if($checkReferID){
			setcookie("refer", $checkReferID,time()+(3600*24));
		}
	}
}

// Cloudflare IP

$reverseProxy = $mysqli->query("SELECT * FROM faucet_settings WHERE id = '16'")->fetch_assoc()['value'];

if($reverseProxy == "yes"){
	// check whether IP is Cloudflare

	$cloudFlareIpList = array("173.245.48.0", "103.21.244.0", "103.22.200.0", "103.31.4.0", "141.101.64.0", "108.162.192.0", "190.93.240.0", "188.114.96.0", "197.234.240.0", "198.41.128.0", "162.158.0.0", "104.16.0.0", "172.64.0.0", "131.0.72.0");

	if(in_array($_SERVER['REMOTE_ADDR'], $cloudFlareIpList)){
		if(filter_var($_SERVER["HTTP_CF_CONNECTING_IP"], FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
	        $realIpAddressUser = $_SERVER["HTTP_CF_CONNECTING_IP"];
	    } else {
	        $realIpAddressUser = $_SERVER['REMOTE_ADDR'];
	    }
	} else {
		echo "Warning: We only support Cloudflare as reverse proxy.";
		$realIpAddressUser = $_SERVER['REMOTE_ADDR'];
	}
} else {
	$realIpAddressUser = $_SERVER['REMOTE_ADDR'];
}

// CSRF PROTECTION

if($_SESSION['token'] == ""){
	$_SESSION['token'] = md5(md5(uniqid().uniqid().mt_rand()));
}
?>