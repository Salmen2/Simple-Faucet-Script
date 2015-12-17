<?php
include("includes/core.php");

$content = "";

if($user){
	$nextClaim = $user['last_claim'] + ($timer * 60);
	if(time() < $nextClaim){
		header("Location: index.php");
		exit;
	}
	if(!isset($_POST['token']) || $_POST['token'] !== $_SESSION['token']) {
	unset($_SESSION['token']);
	$_SESSION['token'] = md5(md5(uniqid().uniqid().mt_rand()));
	exit;
	}
	unset($_SESSION['token']);
	$_SESSION['token'] = md5(md5(uniqid().uniqid().mt_rand()));

	if(isset($_POST['verifykey'])){
		if($_POST['verifykey'] != $user['claim_cryptokey']){
			$content .= alert("danger", "Claim failed. <a href='index.php'>Go back</a>");
		} else {
			$reCaptchaPubKey = $mysqli->query("SELECT * FROM faucet_settings WHERE id = '9' LIMIT 1")->fetch_assoc()['value'];
			$content .= "<h1>2. Solve Captcha</h1><br />
			<form method='post' action='index.php?c=1'>
			<div class='form-group'>
				<center><div class='g-recaptcha' data-sitekey='".$reCaptchaPubKey."'></div></center>
			</div>
			<input type='hidden' name='verifykey' value='".$user['claim_cryptokey']."'/>
			<input type='hidden' name='token' value='".$_SESSION['token']."'/>
			<button type='submit' class='btn btn-success'>Claim</button>
			</form>";
		}
	} else {
		$content .= alert("danger", "Abusing the system is not allowed. <a href='index.php'>Go back</a>");
	}
} else {
	header("Location: index.php");
	exit;
}

$tpl->assign("content", $content);
$tpl->display();
?>