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
	header("Location: index.php");
	exit;
	}
	unset($_SESSION['token']);
	$_SESSION['token'] = md5(md5(uniqid().uniqid().mt_rand()));

	if(isset($_POST['verifykey'])){
		if($_POST['verifykey'] != $user['claim_cryptokey']){
			$content .= alert("danger", "Claim failed. <a href='index.php'>Go back</a>");
		} else {


			$reCaptchaPubKey = $mysqli->query("SELECT * FROM faucet_settings WHERE id = '9' LIMIT 1")->fetch_assoc()['value'];

			if($reCaptchaPubKey){
				$linksCaptcha .= "<a href='#' onClick='showCaptcha(1)'>reCaptcha</a> ";
				$captchaContentBox .= "<div id='recaptcha-box'><center><div class='g-recaptcha' data-sitekey='".$reCaptchaPubKey."'></div></div>";
			}

			$solveMediaChallengeKey = $mysqli->query("SELECT * FROM faucet_settings WHERE id = '2' LIMIT 1")->fetch_assoc()['value'];

			if($solveMediaChallengeKey){
				$linksCaptcha .= "<a href='#' onCLick='showCaptcha(2)'>SolveMedia</a>";
				$captchaContentBox .= "<div id='solvemedia-box'><center><script type=\"text/javascript\" src=\"http://api.solvemedia.com/papi/challenge.script?k=".$solveMediaChallengeKey."\"> </script> <noscript> <iframe src=\"http://api.solvemedia.com/papi/challenge.noscript?k=".$solveMediaChallengeKey."\" height=\"300\" width=\"500\" frameborder=\"0\"></iframe><br/> <textarea name=\"adcopy_challenge\" rows=\"3\" cols=\"40\"> </textarea> <input type=\"hidden\" name=\"adcopy_response\" value=\"manual_challenge\"/> </noscript></center></div>";
			}

			$captchaContent .= "<strong>".$linksCaptcha."</strong><br /><br />
			".$captchaContentBox."
			<input type='hidden' id='selectedCaptcha__' name='selectedCaptcha' value='1' /><br />
			<script>
			if(document.getElementById('recaptcha-box'))
				document.getElementById('solvemedia-box').style.display = 'none';
			function showCaptcha(captcha){
				if(captcha == 1){
					hideCaptchaBoxes();
					document.getElementById('recaptcha-box').style.display = 'block';
					document.getElementById('selectedCaptcha__').value = '1';
				} else if(captcha == 2){
					hideCaptchaBoxes();
					document.getElementById('solvemedia-box').style.display = 'block';
					document.getElementById('selectedCaptcha__').value = '2';
				}
			}
			function hideCaptchaBoxes(){
				if(document.getElementById('recaptcha-box')){
					document.getElementById('recaptcha-box').style.display = 'none';
				}
				if(document.getElementById('solvemedia-box')){
					document.getElementById('solvemedia-box').style.display = 'none';
				}
			}
			</script>";

			if(!$reCaptchaPubKey AND !$solveMediaChallengeKey){
				$captchaContent = alert("info", "Admin hasn't set up the captcha system.");
			}

			
			$content .= "<h1>2. Solve Captcha</h1><br />
			<form method='post' action='index.php?c=1'>
			<div class='form-group'>
				".$captchaContent."
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