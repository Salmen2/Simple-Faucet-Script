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
			$captchaSelect = $mysqli->query("SELECT value FROM faucet_settings WHERE id = '23'")->fetch_assoc()['value'];
			if($captchaSelect == 1){
				$reCaptchaPubKey = $mysqli->query("SELECT * FROM faucet_settings WHERE id = '9' LIMIT 1")->fetch_assoc()['value'];
				$captchaContent = "<center><div class='g-recaptcha' data-sitekey='".$reCaptchaPubKey."'></div><input type='hidden' name='captchaType' value='1'></center>";
			} else if($captchaSelect == 2){
				$bitCaptchaID1 = $mysqli->query("SELECT * FROM faucet_settings WHERE id = '19' LIMIT 1")->fetch_assoc()['value'];
				$bitCaptchaID2 = $mysqli->query("SELECT * FROM faucet_settings WHERE id = '21' LIMIT 1")->fetch_assoc()['value'];
				$sqnId  = ((strpos($_SERVER['HTTP_HOST'],'www.')>0)?$bitCaptchaID1:$bitCaptchaID2);
				$captchaContent = "<center><button id='submit-btn' class='form-control' style='width: 310px;height: 36px;'>Solve Captcha</button>
				<script src='//static.shenqiniao.net/sqn.js?id=".$sqnId."&btn=submit-btn' type='text/javascript'></script></center>";
			} else if($captchaSelect == 3){
				$reCaptchaPubKey = $mysqli->query("SELECT * FROM faucet_settings WHERE id = '9' LIMIT 1")->fetch_assoc()['value'];
				$bitCaptchaID1 = $mysqli->query("SELECT * FROM faucet_settings WHERE id = '19' LIMIT 1")->fetch_assoc()['value'];
				$bitCaptchaID2 = $mysqli->query("SELECT * FROM faucet_settings WHERE id = '21' LIMIT 1")->fetch_assoc()['value'];
				$sqnId  = ((strpos($_SERVER['HTTP_HOST'],'ww.')>0)?$bitCaptchaID2:$bitCaptchaID1);
				$captchaContent = "<center>
				<div class='btn-group'>
				  <button type='button' class='btn btn-default'>Select CAPTCHA</button>
				  <button type='button' class='btn btn-default dropdown-toggle' data-toggle='dropdown' aria-haspopup='true' aria-expanded='false'>
				    <span class='caret'></span>
				    <span class='sr-only'>Toggle Dropdown</span>
				  </button>
				  <ul class='dropdown-menu'>
				    <li><a class='sct1' href='#'>reCaptcha</a></li>
				    <li><a class='sct2' href='#'>BitCaptcha</a></li>
				  </ul>
				</div><br/><br />
				<script>
				$( document ).ready(function() {
					$('#captcha-1').hide();
				    $('input[name=\"captchaType\"]').val(2);

					$('.sct1').click(function(){
						$('#captcha-2').hide();
						$('#captcha-1').show();
						$('input[name=\"captchaType\"]').val(1);
					});
					$('.sct2').click(function(){
						$('#captcha-1').hide();
						$('#captcha-2').show();
						$('input[name=\"captchaType\"]').val(2);
					});
				});
				</script>
		        <div id='captcha-1'><div class='g-recaptcha' data-sitekey='".$reCaptchaPubKey."'></div></div>
		      	<div id='captcha-2'>
					 <div id=\"SQNView\"  style=\"width: 300px;margin: 0 auto\">
					    <div id=\"SQNContainer\" sqn-height=\"40\">
					        <div id=\"SQN-load-bg\"></div>
					        <div class=\"SQN-init\">
					            <a href=\"https://www.shenqiniao.com/\" target=\"_blank\"><img src=\"//static.shenqiniao.net/loading.gif\"/></a>
					            <span class=\"vaptcha-text\">Load...</span>
					        </div>
					    </div>
					    <a class=\"SQN-tips\" href=\"http://bitcaptcha.io/help.html\" title=\"Help\" target=\"_blank\"><img src=\"//static.shenqiniao.net/t.png\"/></a>
					</div>
					<script src='//static.shenqiniao.net/sqn.js?id=".$sqnId."&btn=' type='text/javascript'></script><br /><br /><br /><br /><br /><br />
		      	 </div>
		        <input type='hidden' name='captchaType' value=''>
				</center><br /><br />";
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