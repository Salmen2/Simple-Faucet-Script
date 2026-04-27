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
	$_SESSION['token'] = bin2hex(random_bytes(32));
	header("Location: index.php");
	exit;
	}
	unset($_SESSION['token']);
	$_SESSION['token'] = bin2hex(random_bytes(32));

		if(isset($_POST['verifykey'])){
		if($_POST['verifykey'] != $user['claim_cryptokey']){
			$content .= alert("danger", "Claim failed. <a href='index.php'>Go back</a>");
		} else {


			$hCaptchaPubKey = $mysqli->query("SELECT * FROM faucet_settings WHERE name = 'hcaptcha_pub_key'")->fetch_assoc()['value'];

			if($hCaptchaPubKey){
				$linksCaptcha .= "<a href='#' onCLick='showCaptcha(3)'>hCaptcha</a>";
				$captchaContentBox .= "<div id='hcaptcha-box'><center><script src='https://www.hCaptcha.com/1/api.js?recaptchacompat=off' async defer></script>
				<div class=\"h-captcha\" data-sitekey=\"{$hCaptchaPubKey}\"></div></center></div>";
			}

			$captchaContent .= "<strong>".$linksCaptcha."</strong><br /><br />
			".$captchaContentBox."
			<input type='hidden' id='selectedCaptcha__' name='selectedCaptcha' value='3' /><br />
			<script>
			if(document.getElementById('hcaptcha-box')){
				showCaptcha(3);
			}
			function showCaptcha(captcha){
				hideCaptchaBoxes();
				if(captcha == 3){
					document.getElementById('hcaptcha-box').style.display = 'block';
					document.getElementById('selectedCaptcha__').value = '3';
				}
			}
			function hideCaptchaBoxes(){
				if(document.getElementById('hcaptcha-box')){
					document.getElementById('hcaptcha-box').style.display = 'none';
				}
			}
			</script>";

			if(!$hCaptchaPubKey){
				$captchaContent = alert("info", "Admin hasn't set up the captcha system.");
			}

			
			$content .= "<h1>2. Solve Captcha</h1><br />
			<form method='post' action='index.php?c=1'>
			<div class='form-group'>
				".$captchaContent."
			</div><br />
			<div class='form-group'>
				<label>How many <strong>black</strong> dots do you see?</label><br />
				<img src='captcha.php'><br />
				<center><input type='number' class='form-control' style='width:80px;' name='secc2'></center>
			</div><br />
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