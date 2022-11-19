<?php
include("includes/core.php");

$content = "";

if($user){
	$content .= "<h3>Address</h3>";
	$content .= $user['address'];
	$content .= "<h3>Balance</h3>";
	$content .= toSatoshi($user['balance'])." {$faucetCurrencies[$websiteCurrency][1]}<br /><br />";

	$content .= "<a href='account.php' class='btn btn-primary'>Account/Stats/Withdraw</a><br /><br />";

	$claimStatus = $mysqli->query("SELECT * FROM faucet_settings WHERE id = '11' LIMIT 1")->fetch_assoc()['value'];

	if($claimStatus == "yes"){

	$timer = $mysqli->query("SELECT * FROM faucet_settings WHERE id = '5' LIMIT 1")->fetch_assoc()['value'];

	$minReward = $mysqli->query("SELECT * FROM faucet_settings WHERE id = '6' LIMIT 1")->fetch_assoc()['value'];
	$maxReward = $mysqli->query("SELECT * FROM faucet_settings WHERE id = '7' LIMIT 1")->fetch_assoc()['value'];

	if($minReward != $maxReward){
		$content .= alert("success", "<span class='glyphicon glyphicon-info-sign' aria-hidden='true'></span> Rewards: ".$minReward." to ".$maxReward." {$faucetCurrencies[$websiteCurrency][1]} every ".$timer." minutes");
	} else {
		$content .= alert("success", "<span class='glyphicon glyphicon-info-sign' aria-hidden='true'></span> Rewards: ".$maxReward." {$faucetCurrencies[$websiteCurrency][1]} every ".$timer." minutes");
	}

	$nextClaim = $user['last_claim'] + ($timer * 60);

	if(time() >= $nextClaim){

	if($user['claim_cryptokey'] == ""){
		$cryptoGenNumber = rand(1,256);
		$cryptoKey = hash('sha256', ("Key_".$user['id'].time().$cryptoGenNumber));
		$mysqli->query("UPDATE faucet_user_list Set claim_cryptokey = '$cryptoKey' WHERE id = '{$user['id']}'");
		header("Location: index.php");
		exit;
	}

	if($_GET['c'] != "1"){
		$content .= "
		<h1>1. Claim</h1><br />
		<form method='post' action='verify.php'>
		<input type='hidden' name='verifykey' value='".$user['claim_cryptokey']."'/>
		<input type='hidden' name='token' value='".$_SESSION['token']."'/>
		<button type='submit' class='btn btn-success btn-lg'><span class='glyphicon glyphicon-menu-right' aria-hidden='true'></span> Next</button>
		</form>";
	} else if($_GET['c'] == "1"){
		if($_POST['verifykey'] == $user['claim_cryptokey']){
			$mysqli->query("UPDATE faucet_user_list Set claim_cryptokey = '' WHERE id = '{$user['id']}'");


			if(!is_numeric($_POST['selectedCaptcha']))
				exit;

			$captchaCheckVerify = CaptchaCheck($_POST['selectedCaptcha'], $_POST, $mysqli);

			if(!$captchaCheckVerify){
				$content .= alert("danger", "Captcha is wrong. <a href='index.php'>Try again</a>.");
			} else if($_SESSION['checker'] != $_POST['secc2']){
				$content .= alert("danger", "Wrong Secondary Captcha. <a href='index.php'>Try again</a>.");
			} else {
				$VPNShield = $mysqli->query("SELECT * FROM faucet_settings WHERE id = '14' LIMIT 1")->fetch_assoc()['value'];
				$iphubApiKey = $mysqli->query("SELECT * FROM faucet_settings WHERE id = '22' LIMIT 1")->fetch_assoc()['value'];
				if(checkDirtyIp($realIpAddressUser, $iphubApiKey) == true AND $VPNShield == "yes"){
					$content .= alert("danger", "VPN/Proxy/Tor is not allowed on this faucet.<br />Please disable and <a href='index.php'>try again</a>.");
				} else {
					$nextClaim2 = time() - ($timer * 60);
					$IpCheck = $mysqli->query("SELECT COUNT(id) FROM faucet_user_list WHERE ip_address = '$realIpAddressUser' AND last_claim >= '$nextClaim2'")->fetch_row()[0];
					if($IpCheck >= 1){
						$content .= alert("danger", "Someone else claimed in your network already.");
					} else {
						$IpCheckBan = $mysqli->query("SELECT COUNT(id) FROM faucet_banned_ip WHERE ip_address = '$ip'")->fetch_row()[0];
						$AddressCheckBan = $mysqli->query("SELECT COUNT(id) FROM faucet_banned_address WHERE address = '{$user['address']}' OR address = '{$user['ec_userid']}'")->fetch_row()[0];
						if($IpCheckBan >= 1 OR $AddressCheckBan >= 1){
							$content .= alert("danger", "Your Address and/or IP is banned from this service.");
						} else {
							$content .= "<h1>3. Your Claim</h1>";

							srand((double)microtime()*1000000);
							$payOut = rand($minReward, $maxReward);

							$payOutBTC = $payOut / 100000000;
							$timestamp = time();

							$mysqli->query("INSERT INTO faucet_transactions (userid, type, amount, timestamp) VALUES ('{$user['id']}', 'Payout', '$payOutBTC', '$timestamp')");
							$mysqli->query("UPDATE faucet_user_list Set balance = balance + $payOutBTC, last_claim = '$timestamp' WHERE id = '{$user['id']}'");
							$content .= alert("success", "You've claimed successfully ".$payOut." {$faucetCurrencies[$websiteCurrency][1]}.<br />You can claim again in ".$timer." minutes!");

							$referralPercent = $mysqli->query("SELECT * FROM faucet_settings WHERE id = '15' LIMIT 1")->fetch_assoc()['value'];

							if($referralPercent >= 1){
								if($user['referred_by'] != 0){
									$referralPercentDecimal = floor($referralPercent) / 100;
									$referralCommission = floor($referralPercentDecimal * $payOut);
									$referralCommissionBTC = $referralCommission / 100000000;
									$mysqli->query("UPDATE faucet_user_list Set balance = balance + $referralCommissionBTC WHERE id = '{$user['referred_by']}'");
									$mysqli->query("INSERT INTO faucet_transactions (userid, type, amount, timestamp) VALUES ('{$user['referred_by']}', 'Referral', '$referralCommissionBTC', '$timestamp')");
								}
							}

						}
					}
				}
			}
		} else {
			$mysqli->query("UPDATE faucet_user_list Set claim_cryptokey = '' WHERE id = '{$user['id']}'");
			$content .= alert("danger", "Abusing the system is not allowed. <a href='index.php'>Try again</a>");
		}
	}

	} else {
		$timeLeft = floor(($nextClaim - time()) / 60);
		$content .= alert("warning", "You have already claimed in the last ".$timer." minutes.<br />You can claim again in ".$timeLeft." minutes.<br /><a href='index.php'>Refresh</a>");
	}

	} else {
		$content .= alert("warning", "Faucet is disabled.");
	}

	$referralPercent = $mysqli->query("SELECT * FROM faucet_settings WHERE id = '15' LIMIT 1")->fetch_assoc()['value'];
	if($referralPercent != "0"){
	$content .= '<blockquote class="text-left">
					<p>
						Reflink: <code>'.$Website_Url.'?ref='.$user['id'].'</code><br />
						Reflink with Address: <code>'.$Website_Url.'?r='.( ($user['address']) ? $user['address'] : $user['ec_userid']).'</code>
					</p>
					<footer>Share this link with your friends and earn '.$referralPercent.'% referral commission</footer>
				</blockquote>';
	}
} else {
	$faucetName = $mysqli->query("SELECT * FROM faucet_settings WHERE id = '1'")->fetch_assoc()['value'];
	$content .= "<h2>".$faucetName."</h2>";
	$content .= "<h3>Enter your Address and start to claim!</h3><br />";

	$faucetpayApiToken = $mysqli->query("SELECT value FROM faucet_settings WHERE id = '19'")->fetch_assoc()['value'];
	$blockioApiKey = $mysqli->query("SELECT value FROM faucet_settings WHERE id = '20'")->fetch_assoc()['value'];

	if(isset($_POST['address'])){
		if(!isset($_POST['token']) || $_POST['token'] !== $_SESSION['token']) {
		unset($_SESSION['token']);
		$_SESSION['token'] = md5(md5(uniqid().uniqid().mt_rand()));
		exit;
		}
		unset($_SESSION['token']);
		$_SESSION['token'] = md5(md5(uniqid().uniqid().mt_rand()));

		if($_POST['address']){
			$Address = $mysqli->real_escape_string(trim($_POST['address']));
			$addressCheck = (strlen($Address) >= 30 && strlen($Address) <= 50);
			if(!$addressCheck){
				$content .= alert("danger", "The Bitcoin address doesn't look valid.");
				$alertForm = "has-error";
			} else {
				// Check Referral
				if($_COOKIE['refer']){
					if(is_numeric($_COOKIE['refer'])){
						$referID2 = $mysqli->real_escape_string($_COOKIE['refer']);
						$AddressCheck = $mysqli->query("SELECT COUNT(id) FROM faucet_user_list WHERE id = '$referID2'")->fetch_row()[0];
						if($AddressCheck == 1){
							$referID = $referID2;
						} else {
							$referID = 0;
						}
					} else {
						$referID = 0;
					}
				} else {
					$referID = 0;
				}

				$AddressCheck = $mysqli->query("SELECT COUNT(id) FROM faucet_user_list WHERE LOWER(address) = '".strtolower($Address)."' LIMIT 1")->fetch_row()[0];
				$timestamp = $mysqli->real_escape_string(time());
				$ip = $mysqli->real_escape_string($realIpAddressUser);

				if($AddressCheck == 1){
					$userID = $mysqli->query("SELECT id FROM faucet_user_list WHERE LOWER(address) = '".strtolower($Address)."' LIMIT 1")->fetch_assoc()['id'];
					$_SESSION['address'] = $userID;
					$mysqli->query("UPDATE faucet_user_list Set last_activity = '$timestamp', ip_address = '$ip' WHERE id = '$userID'");

				} else {

					$mysqli->query("INSERT INTO faucet_user_list (address, ip_address, balance, joined, last_activity, referred_by, last_claim, claim_cryptokey) VALUES ('$Address', '$ip', '0', '$timestamp', '$timestamp', '$referID', '0', '')");
					$_SESSION['address'] = $mysqli->insert_id;
				}
				header("Location: index.php");
				exit;
			}
		} else {
			$content .= alert("danger", "The {$websiteCurrency} address field can't be blank.");
			$alertForm = "has-error";
		}
	}

	$content .= "<form method='post' action=''>

	<div class='form-group $alertForm'
		<label for='Address'>{$websiteCurrency} Address</label>
		<center><input class='form-control' type='text' placeholder='Enter your {$websiteCurrency} Address' name='address' value='$Address' style='width: 325px;' autofocus></center>
	</div><br />
	<input type='hidden' name='token' value='".$_SESSION['token']."'/>
	<button type='submit' class='btn btn-primary'>Join</button>
	</form> ";
}

if(isset($_GET['fapi']))
	faucetInfo($mysqli);

$tpl->assign("content", $content);
$tpl->display();
?>
