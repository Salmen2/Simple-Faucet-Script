<?php
include("includes/core.php");

$content .= "<h1>Admin</h1>";

if($_SESSION['admin']){
	$AdminSessionKey = $_SESSION['admin'];
	$UserDB = $mysqli->query("SELECT * FROM faucet_settings WHERE id = '12' LIMIT 1")->fetch_assoc()['value'];
	$PwDB = $mysqli->query("SELECT * FROM faucet_settings WHERE id = '13' LIMIT 1")->fetch_assoc()['value'];
	$DatabaseAdminKey = "Admin_".$UserDB."_Password_".$PwDB;
	if($AdminSessionKey != $DatabaseAdminKey){ unset($_SESSION['admin']); header("Location: admin.php"); die; }

	switch($_GET['p']){
		default:
		// Total Stats

		$TotalClaims = $mysqli->query("SELECT COUNT(id) FROM faucet_transactions WHERE type = 'Payout'")->fetch_row()[0];
		$TotalClaimed = $mysqli->query("SELECT SUM(amount) FROM faucet_transactions WHERE type = 'Payout'")->fetch_row()[0];
		$TotalClaimed = $TotalClaimed ? $TotalClaimed : 0;

		// 24 Hours stats

		$Last24Hours = time() - 86400;
		$Last24HoursClaims = $mysqli->query("SELECT COUNT(id) FROM faucet_transactions WHERE type = 'Payout' AND timestamp > '$Last24Hours'")->fetch_row()[0];
		$Last24HoursClaimed = $mysqli->query("SELECT SUM(amount) FROM faucet_transactions WHERE type = 'Payout' AND timestamp > '$Last24Hours'")->fetch_row()[0];
		$Last24HoursClaimed = $Last24HoursClaimed ? $Last24HoursClaimed : 0;

		// Referral

		$TotalReferralPayout = $mysqli->query("SELECT SUM(amount) FROM faucet_transactions WHERE type = 'Referral Payout'")->fetch_row()[0];
		$TotalReferralPayout = $TotalReferralPayout ? $TotalReferralPayout : 0;

		$Last24HoursReferralPayout = $mysqli->query("SELECT SUM(amount) FROM faucet_transactions WHERE type = 'Referral Payout' AND timestamp > '$Last24Hours'")->fetch_row()[0];
		$Last24HoursReferralPayout = $Last24HoursReferralPayout ? $Last24HoursReferralPayout : 0;
		$content .= "<h2>Stats</h2>
		<div class='row'>
		<div class='col-md-12'>
			<h3>All time</h3>
		</div>
		<div class='col-md-4'>
			<h4>Total Claims</h4>
			<b>".$TotalClaims."</b>
		</div>
		<div class='col-md-4'>
			<h4>Total Claimed</h4>
			<b>".toSatoshi($TotalClaimed)."</b><br />Satoshi
		</div>
		<div class='col-md-4'>
			<h4>Total Referral Payout</h4>
			<b>".toSatoshi($TotalReferralPayout)."</b><br />Satoshi
		</div>
		<div class='col-md-12'>
			<h3>Last 24 Hours</h3>
		</div>
		<div class='col-md-4'>
			<h4>Claims</h4>
			<b>".$Last24HoursClaims."</b>
		</div>
		<div class='col-md-4'>
			<h4>Claimed</h4>
			<b>".toSatoshi($Last24HoursClaimed)."</b><br />Satoshi
		</div>
		<div class='col-md-4'>
			<h4>Referral Payout</h4>
			<b>".toSatoshi($Last24HoursReferralPayout)."</b><br />Satoshi
		</div>
		</div><br /><h2>Configuration</h2>
		<a class='btn btn-default' href='?p=as'>Standard settings</a><br />
		<a class='btn btn-default' href='?p=ps'>Page settings</a><br />
		<a class='btn btn-default' href='?p=ads'>Advertising settings</a><br />

		<hr />

		<a class='btn btn-info' href='?p=bip'>Ban IPs</a><br />
		<a class='btn btn-info' href='?p=bad'>Ban Address</a><br />";
		break;

		case("as"):
		$content .= "<a href='admin.php'>Back</a><br>
		<h3>Admin Settings</h3><h4>Change Admin login datas</h4>";
		
		$Username = $mysqli->query("SELECT value FROM faucet_settings WHERE id = '12' LIMIT 1")->fetch_assoc()['value'];

		if($_GET['c'] == 1){
		if(isset($_POST['username']) AND isset($_POST['password'])){
			if($_POST['username'] AND $_POST['password']){
				$username = $mysqli->real_escape_string($_POST['username']);
				$password = $mysqli->real_escape_string(hash("sha256", $_POST['password']));
				$mysqli->query("UPDATE faucet_settings Set value = '$username' WHERE id = '12'");
				$mysqli->query("UPDATE faucet_settings Set value = '$password' WHERE id = '13'");
				$content .= alert("success", "Username and Password was changed successfully.");
			} else if($_POST['username']){
				$content .= alert("danger", "Please fill all forms.");
			}
		}
		}

		$content .= "<form method='post' action='?p=as&c=1'>
		<div class='form-group'>
			<label>Username</label>
			<center><input class='form-control' type='text' name='username' style='width: 225px;' value='$Username' placeholder='Username ...'></center>
		</div>

		<div class='form-group'>
			<label>Password</label>
			<center><input class='form-control' type='password' name='password' style='width: 225px;' placeholder='Password ...'></center>
			<span class='help-block'>Can't be shown because it's encoded.</span>
		</div>

		<button type='submit' class='btn btn-primary'>Change</button>
		</form><br />";

		$content .= "<h3>Faucet settings</h3><h4>Change Faucet name</h4>";

		$Faucetname = $mysqli->query("SELECT value FROM faucet_settings WHERE id = '1' LIMIT 1")->fetch_assoc()['value'];
		
		if($_GET['c'] == 2){
			if(!$_POST['faucetname']){
				$content .= alert("danger", "Faucetname can't be blank.");
			} else {
				$Faucetname = $mysqli->real_escape_string($_POST['faucetname']);
				$mysqli->query("UPDATE faucet_settings Set value = '$Faucetname' WHERE id = '1'");
				$content .= alert("success", "Faucetname was changed successfully.");
			}
		}

		$content .= "<form method='post' action='?p=as&c=2'>
		<div class='form-group'>
			<label>Faucetname</label>
			<center><input class='form-control' type='text' name='faucetname' style='width: 225px;' value='$Faucetname' placeholder='Faucetname ...'></center>
		</div>
		<button type='submit' class='btn btn-primary'>Change</button>
		</form><br />";

		$content .= "<h4>Change Rewards</h4>";
		
		$minReward = $mysqli->query("SELECT * FROM faucet_settings WHERE id = '6' LIMIT 1")->fetch_assoc()['value'];
		$maxReward = $mysqli->query("SELECT * FROM faucet_settings WHERE id = '7' LIMIT 1")->fetch_assoc()['value'];
		
		if($_GET['c'] == 3){
			if(!$_POST['minreward'] OR !$_POST['maxreward']){
				$content .= alert("danger", "Mininum and Maximum reward can't be blank.");
			} else {
				if(!is_numeric($_POST['minreward']) OR !is_numeric($_POST['maxreward'])){
					$content .= alert("danger", "Reward must be numeric.");
				} else {
					$minreward5 = $mysqli->real_escape_string($_POST['minreward']);
					$maxreward5 = $mysqli->real_escape_string($_POST['maxreward']);
					$mysqli->query("UPDATE faucet_settings Set value = '$minreward5' WHERE id = '6'");
					$mysqli->query("UPDATE faucet_settings Set value = '$maxreward5' WHERE id = '7'");
					$content .= alert("success", "Rewards was changed successfully.");
					$minReward = $minreward5;
					$maxReward = $maxreward5;
				}
			}
		}

		$content .= "<form method='post' action='?p=as&c=3'>
		<div class='form-group'>
			<label>Mininum Reward (Satoshi)</label>
			<center><input class='form-control' type='number' name='minreward' style='width: 225px;' value='$minReward' placeholder='Mininum Reward'></center>
		</div>
		<div class='form-group'>
			<label>Maximum Reward (Satoshi)</label>
			<center><input class='form-control' type='number' name='maxreward' style='width: 225px;' value='$maxReward' placeholder='Maximum Reward'></center>
		</div>
		<button type='submit' class='btn btn-primary'>Change</button>
		</form><br />";


		$content .= "<h4>Timer</h4>";

		$timer = $mysqli->query("SELECT * FROM faucet_settings WHERE id = '5' LIMIT 1")->fetch_assoc()['value'];
		if($_GET['c'] == 4){
			if(!$_POST['timer']){
				$content .= alert("danger", "Timer can't be blank.");
			} else {
				if(!is_numeric($_POST['timer'])){
					$content .= alert("danger", "Timer must be numeric.");
				} else {
					$timer5 = $mysqli->real_escape_string($_POST['timer']);

					$mysqli->query("UPDATE faucet_settings Set value = '$timer5' WHERE id = '5'");
					$content .= alert("success", "Timer was changed successfully.");
				}
			}
		}

		$content .= "<form method='post' action='?p=as&c=4'>
		<div class='form-group'>
			<label>Timer (minutes)</label>
			<center><input class='form-control' type='number' name='timer' style='width: 225px;' value='$timer' placeholder='Timer'></center>
		</div>
		<button type='submit' class='btn btn-primary'>Change</button>
		</form><br />";

		$content .= "<h4>Referral Program</h4>";

		$referralPercent = $mysqli->query("SELECT * FROM faucet_settings WHERE id = '15' LIMIT 1")->fetch_assoc()['value'];

		if($_GET['c'] == "r"){
			if(!$_POST['referral']){
				$content .= alert("danger", "Commission can't be blank.");
			} else {
				if(!is_numeric($_POST['referral'])){
					$content .= alert("danger", "Commission must be numeric");
				} else {
					$referralPercent = $mysqli->real_escape_string($_POST['referral']);

					$mysqli->query("UPDATE faucet_settings Set value = '$referralPercent' WHERE id = '15'");
					$content .= alert("success", "Referral Program Commission was changed successfully.");
				}
			}
		}

		$content .= "<form method='post' action='?p=as&c=r'>
		<div class='form-group'>
			<label>Commission in %</label>
			<center><input class='form-control' type='number' name='referral' style='width: 225px;' value='$referralPercent' placeholder='25'></center>
			<span class='help-block'>Enter without percent. Example: 10<br />To disable Referral Program enter 0
		</div>
		<button type='submit' class='btn btn-primary'>Change</button>
		</form><br />";

		$content .= "<h3>Captcha Preference</h3><p>Enable more than one captcha, to simplify the user's experience.<br />We recommend BitCaptcha, to profit from the Captcha itself.</p><br /><br />";

		if($_GET['c'] == 55){
			if(!is_numeric($_POST['captchaselect']) OR ($_POST['captchaselect'] < 1 AND $_POST['captchaselect'] > 3)){
				$content .= alert("danger", "Please select your captcha.");
			} else {
				$captchaSelection = $mysqli->real_escape_string($_POST['captchaselect']);
				$mysqli->query("UPDATE faucet_settings Set value = '$captchaSelection' WHERE id = '23'");
				$content .= alert("success", "Your preference has been saved.");
				$captchaSelect = $captchaSelection;
			}
		}

		$captchaSelect = $mysqli->query("SELECT value FROM faucet_settings WHERE id = '23'")->fetch_assoc()['value'];

		$content .= "<form method='post' action='?p=as&c=55'>
		<div class='form-group'>
			<label class='radio-inline' for='radios-0'>
				<input type='radio' ".(($captchaSelect == 1) ? 'checked=checked' : '')." id='radios-0' name='captchaselect' value='1'>
				reCaptcha
			</label>
			<label class='radio-inline' for='radios-1'>
				<input type='radio' ".(($captchaSelect == 2) ? 'checked=checked' : '')." id='radios-1' name='captchaselect' value='2'>
				BitCaptcha
			</label>
			<label class='radio-inline' for='radios-2'>
				<input type='radio' ".(($captchaSelect == 3) ? 'checked=checked' : '')." id='radios-2' name='captchaselect' value='3'>
				Both Captchas
			</label><br />
		</div>
		<button type='submit' class='btn btn-primary'>Change</button>
		</form><br /><br />";

		$content .= "<h3>Keys settings</h3><h4>Faucethub Key</h4>";

		$faucethubkey = $mysqli->query("SELECT * FROM faucet_settings WHERE id = '10' LIMIT 1")->fetch_assoc()['value'];

		if($_GET['c'] == 5){
			if(!$_POST['faucethubkey']){
				$content .= alert("danger", "Key can't be blank.");
			} else {
				$faucethubkey5 = $mysqli->real_escape_string($_POST['faucethubkey']);

				$mysqli->query("UPDATE faucet_settings Set value = '$faucethubkey5' WHERE id = '10'");
				$content .= alert("success", "Faucethub Key was changed successfully.");
				$faucethubkey = $faucethubkey5;
			}
		}

		$content .= "<form method='post' action='?p=as&c=5'>
		<div class='form-group'>
			<label>Faucethub Key</label>
			<center><input class='form-control' type='text' name='faucethubkey' style='width: 275px;' value='$faucethubkey' placeholder='FaucetHub Key'></center>
		</div>
		<button type='submit' class='btn btn-primary'>Change</button>
		</form><br />";

		$content .= "<h4>reCaptcha Keys</h4>";

		$reCaptcha_privkey = $mysqli->query("SELECT * FROM faucet_settings WHERE id = '8' LIMIT 1")->fetch_assoc()['value'];
		$reCaptcha_pubkey = $mysqli->query("SELECT * FROM faucet_settings WHERE id = '9' LIMIT 1")->fetch_assoc()['value'];
		
		if($_GET['c'] == 6){
			if(!$_POST['recaptcha_pubkey'] OR !$_POST['recaptcha_privkey']){
				$content .= alert("danger", "reCaptcha Keys can't be blank.");
			} else {
				$reCaptcha_privkey5 = $mysqli->real_escape_string($_POST['recaptcha_privkey']);
				$reCaptcha_pubkey5 = $mysqli->real_escape_string($_POST['recaptcha_pubkey']);
				$mysqli->query("UPDATE faucet_settings Set value = '$reCaptcha_privkey5' WHERE id = '8'");
				$mysqli->query("UPDATE faucet_settings Set value = '$reCaptcha_pubkey5' WHERE id = '9'");
				$content .= alert("success", "reCaptcha Keys was changed successfully.");
				$reCaptcha_privkey = $mysqli->real_escape_string($_POST['recaptcha_privkey']);
				$reCaptcha_pubkey = $mysqli->real_escape_string($_POST['recaptcha_pubkey']);
			}
		}

		$content .= "<form method='post' action='?p=as&c=6'>
		<div class='form-group'>
			<label>reCaptcha Private Key</label>
			<center><input class='form-control' type='text' value='".$reCaptcha_privkey."' name='recaptcha_privkey' style='width: 375px;' placeholder='reCaptcha Private Key'></center>
		</div>
		<div class='form-group'>
			<label>reCaptcha Public Key</label>
			<center><input class='form-control' type='text' value='".$reCaptcha_pubkey."' name='recaptcha_pubkey' style='width: 375px;' placeholder='reCaptcha Public Key'></center>
		</div>
		<button type='submit' class='btn btn-primary'>Change</button>
		</form><br />";

		$content .= "<h4>BitCaptcha Keys</h4>";

		$bitCaptchaID1 = $mysqli->query("SELECT * FROM faucet_settings WHERE id = '19' LIMIT 1")->fetch_assoc()['value'];
		$bitCaptchaPriKey1 = $mysqli->query("SELECT * FROM faucet_settings WHERE id = '20' LIMIT 1")->fetch_assoc()['value'];
		$bitCaptchaID2 = $mysqli->query("SELECT * FROM faucet_settings WHERE id = '21' LIMIT 1")->fetch_assoc()['value'];
		$bitCaptchaPriKey2 = $mysqli->query("SELECT * FROM faucet_settings WHERE id = '22' LIMIT 1")->fetch_assoc()['value'];
		
		if($_GET['c'] == 7){
			if(!$_POST['bitcaptchaid1'] AND !$_POST['bitCaptchaID2']){
				$content .= alert("danger", "BitCaptcha Keys cannot be blank.");
			} else {
				$bitCaptchaID1 = $mysqli->real_escape_string($_POST['bitcaptchaid1']);
				$bitCaptchaPriKey1 = $mysqli->real_escape_string($_POST['bitcaptchaprikey1']);
				$bitCaptchaID2 = $mysqli->real_escape_string($_POST['bitcaptchaid2']);
				$bitCaptchaPriKey2 = $mysqli->real_escape_string($_POST['bitcaptchaprikey2']);

				$mysqli->query("UPDATE faucet_settings Set value = '$bitCaptchaID1' WHERE id = '19'");
				$mysqli->query("UPDATE faucet_settings Set value = '$bitCaptchaPriKey1' WHERE id = '20'");
				$mysqli->query("UPDATE faucet_settings Set value = '$bitCaptchaID2' WHERE id = '21'");
				$mysqli->query("UPDATE faucet_settings Set value = '$bitCaptchaPriKey2' WHERE id = '22'");
				$content .= alert("success", "BitCaptcha Keys has been changed successfully.");
			}
		}

		$content .= "<form method='post' action='?p=as&c=7'>
		<div class='form-group'>
			<label>BitCaptcha ID (Non-WWW)</label>
			<center><input class='form-control' type='text' value='".$bitCaptchaID1."' name='bitcaptchaid1' style='width: 375px;' placeholder='BitCaptcha ID (Non-WWW) ...'></center>
		</div>
		<div class='form-group'>
			<label>BitCaptcha Private Key (Non-WWW)</label>
			<center><input class='form-control' type='text' value='".$bitCaptchaPriKey1."' name='bitcaptchaprikey1' style='width: 375px;' placeholder='BitCaptcha Private Key (Non-WWW) ...'></center>
		</div>
		<div class='form-group'>
			<label>BitCaptcha ID (WWW)</label>
			<center><input class='form-control' type='text' value='".$bitCaptchaID2."' name='bitcaptchaid2' style='width: 375px;' placeholder='BitCaptcha ID (WWW) ...'></center>
		</div>
		<div class='form-group'>
			<label>BitCaptcha Private Key (WWW)</label>
			<center><input class='form-control' type='text' value='".$bitCaptchaPriKey2."' name='bitcaptchaprikey2' style='width: 375px;' placeholder='BitCaptcha Private Key (Non-WWw) ...'></center>
		</div>
		<button type='submit' class='btn btn-primary'>Change</button>
		</form><br />";

		$content .= "<h3>Claim settings</h3><h4>Claim</h4>";

		$claimStatus = $mysqli->query("SELECT * FROM faucet_settings WHERE id = '11' LIMIT 1")->fetch_assoc()['value'];

		if($claimStatus == "yes"){
			if($_GET['eb'] == "n"){
				$mysqli->query("UPDATE faucet_settings Set value = 'no' WHERE id = '11'");
				$content .= alert("success", "Claiming from Faucet is disabled.");
				$content .= "<a href='?p=as&eb=y' class='btn btn-default'>Enable claim</a>";
			} else {
				$content .= "<a href='?p=as&eb=n' class='btn btn-default'>Disable claim</a>";
			}
		} else if($claimStatus == "no"){
			if($_GET['eb'] == "y"){
				$mysqli->query("UPDATE faucet_settings Set value = 'yes' WHERE id = '11'");
				$content .= alert("success", "Claiming from Faucet is enabled.");
				$content .= "<a href='?p=as&eb=n' class='btn btn-default'>Disable claim</a>";
			} else {
				$content .= "<a href='?p=as&eb=y' class='btn btn-default'>Enable claim</a>";
			}
		}

		$content .= "<h4>VPN/Proxy</h4>
		<p>Enable or disable claiming from faucet using VPN or Proxy</p>";

		$shieldStatus = $mysqli->query("SELECT * FROM faucet_settings WHERE id = '14' LIMIT 1")->fetch_assoc()['value'];

		if($shieldStatus == "yes"){
			if($_GET['sp'] == "n"){
				$mysqli->query("UPDATE faucet_settings Set value = 'no' WHERE id = '14'");
				$content .= alert("success", "VPN/Proxy Shield is disabled.");
				$content .= "<a href='?p=as&sp=y' class='btn btn-default'>Enable Shield</a>";
			} else {
				$content .= "<a href='?p=as&sp=n' class='btn btn-default'>Disable Shield</a>";
			}
		} else if($shieldStatus == "no"){
			if($_GET['sp'] == "y"){
				$mysqli->query("UPDATE faucet_settings Set value = 'yes' WHERE id = '14'");
				$content .= alert("success", "VPN/Proxy Shield is enabled.");
				$content .= "<a href='?p=as&sp=n' class='btn btn-default'>Disable Shield</a>";
			} else {
				$content .= "<a href='?p=as&sp=y' class='btn btn-default'>Enable Shield</a>";
			}
		}

		// Auto Withdraw

		$content .= "<h4>Auto Withdraw</h4>
		<p>Enable this feature for auto withdrawal after payout to Faucethub</p>";

		$reverseProxyStatus = $mysqli->query("SELECT * FROM faucet_settings WHERE id = '18' LIMIT 1")->fetch_assoc()['value'];

		if($reverseProxyStatus == "yes"){
			if($_GET['auwi'] == "n"){
				$mysqli->query("UPDATE faucet_settings Set value = 'no' WHERE id = '18'");
				$content .= alert("success", "Auto Withdraw is disabled.");
				$content .= "<a href='?p=as&auwi=y' class='btn btn-default'>Enable Auto Withdraw</a>";
			} else {
				$content .= "<a href='?p=as&auwi=n' class='btn btn-default'>Disable Auto Withdraw</a>";
			}
		} else if($reverseProxyStatus == "no"){
			if($_GET['auwi'] == "y"){
				$mysqli->query("UPDATE faucet_settings Set value = 'yes' WHERE id = '18'");
				$content .= alert("success", "Auto Withdraw is enabled.");
				$content .= "<a href='?p=as&auwi=n' class='btn btn-default'>Disable Auto Withdraw</a>";
			} else {
				$content .= "<a href='?p=as&auwi=y' class='btn btn-default'>Enable Auto Withdraw</a>";
			}
		}


		// Reverse proxy

		$content .= "<h4>Reverse Proxy</h4>
		<p>If you have CloudFlare enabled, you need to activate this feature</p>";

		$reverseProxyStatus = $mysqli->query("SELECT * FROM faucet_settings WHERE id = '16' LIMIT 1")->fetch_assoc()['value'];

		if($reverseProxyStatus == "yes"){
			if($_GET['rvp'] == "n"){
				$mysqli->query("UPDATE faucet_settings Set value = 'no' WHERE id = '16'");
				$content .= alert("success", "Reverse Proxy is disabled.");
				$content .= "<a href='?p=as&rvp=y' class='btn btn-default'>Enable Reverse Proxy Feature</a>";
			} else {
				$content .= "<a href='?p=as&rvp=n' class='btn btn-default'>Disable Reverse Proxy Feature</a>";
			}
		} else if($reverseProxyStatus == "no"){
			if($_GET['rvp'] == "y"){
				$mysqli->query("UPDATE faucet_settings Set value = 'yes' WHERE id = '16'");
				$content .= alert("success", "Reverse Proxy is enabled.");
				$content .= "<a href='?p=as&rvp=n' class='btn btn-default'>Disable Reverse Proxy Feature</a>";
			} else {
				$content .= "<a href='?p=as&rvp=y' class='btn btn-default'>Enable Reverse Proxy Feature</a>";
			}
		}

		break;

		case("ps"):
		$content .= "<h3>Page settings</h3><h4>Create new Page</h4>";

		if($_GET['cr'] == "y"){
			if(!$_POST['name']){
				$content .= alert("danger", "Pagename can't be blank.");
			} else {
				$name = $mysqli->real_escape_string($_POST['name']);
				$timestamp = time();
				$mysqli->query("INSERT INTO faucet_pages (name, content, timestamp_created) VALUES ('$name', '', '$timestamp')");
				$content .= alert("success", "Page was successfully created.");
			}
		}

		$content .= "<form method='post' action='?p=ps&cr=y'>
		<div class='form-group'>
			<label>Name</label>
			<center><input type='text' name='name' style='width:225px;' class='form-control' placeholder='Name ...'></center>
		</div>

		<button type='submit' class='btn btn-primary'>Add Page</button>
		</form><br /><h4>Change Pages</h4>";

		$content .= "<script type='text/javascript'>
		$('#myTabs a').click(function (e) {
		  e.preventDefault()
		  $(this).tab('show')
		});
		</script>";

		if(isset($_GET['ch'])){
			if(!$_GET['ch'] OR !is_numeric($_GET['ch']) OR !$_POST['content']){
				$content .= alert("danger", "Please fill all forms.");
			} else {
				$pageContent = $mysqli->real_escape_string($_POST['content']);
				$pageID = $mysqli->real_escape_string($_GET['ch']);
				$mysqli->query("UPDATE faucet_pages Set content = '$pageContent' WHERE id = '$pageID'");
				$content .= alert("success", "Content was changed successfully.");
			}
		}

		if(isset($_GET['del'])){
			if(!$_GET['del'] OR !is_numeric($_GET['del'])){
				$content .= alert("danger", "Please fill all forms.");
			} else {
				$delid = $mysqli->real_escape_string($_GET['del']);
				$mysqli->query("DELETE FROM faucet_pages WHERE id = '$delid'");
				$content .= alert("success", "Page was deleted successfully.");
			}
		}

		$Navtabs = "";

		$PageNameTabs = $mysqli->query("SELECT id, name FROM faucet_pages");

		while($Tab = $PageNameTabs->fetch_assoc()){
			$Navtabs .= "<li role=\"presentation\"><a href=\"#pn".$Tab['id']."\" aria-controls=\"pn".$Tab['id']."\" role=\"tab\" data-toggle=\"tab\">".$Tab['name']."</a></li>";
		}

		$PageConf = "";

		$PageConf1 = $mysqli->query("SELECT id, name, content FROM faucet_pages");

		while($PageConf2 = $PageConf1->fetch_assoc()){
			$PageConf .= "<div role=\"tabpanel\" class=\"tab-pane\"  id=\"pn".$PageConf2['id']."\">
			<form method='post' action='?p=ps&ch=".$PageConf2['id']."'>
			<textarea class='form-control' cols='65' rows='10' name='content'>".$PageConf2['content']."</textarea><br />
			<button type='submit' class='btn btn-success btn-lg'>Change</button>
			</form><br />
			<hr />
			<a href='?p=ps&del=".$PageConf2['id']."' class='btn btn-danger'>Delete Page</a>
			</div>";
		}

		

		$content .= "

		<div>

  <!-- Nav tabs -->
  <ul class=\"nav nav-tabs\" role=\"tablist\">
    $Navtabs
  </ul><br />

  <!-- Tab panes -->
  <div class=\"tab-content\">
    $PageConf
  </div>

</div>";
	break;

	case("ads"):

	$content .= "<a href='admin.php'>Back</a><br>
	<h3>Admin Settings</h3><h4>Advertising settings</h4>";

	$content .= "<h3>Space top</h4>";

	$Spacetop = $mysqli->query("SELECT value FROM faucet_settings WHERE id = '2' LIMIT 1")->fetch_assoc()['value'];
		
	if($_GET['c'] == 1){
		if(!isset($_POST['spacetop'])){
			$content .= alert("danger", "Error.");
		} else {
			$Spacetop = $mysqli->real_escape_string($_POST['spacetop']);
			$mysqli->query("UPDATE faucet_settings Set value = '$Spacetop' WHERE id = '2'");
			$content .= alert("success", "HTML Code 'Space top' changed successfully.");
			$Spacetop = $_POST['spacetop'];
		}
	}

	$content .= "<form method='post' action='?p=ads&c=1'>
	<textarea class='form-control' cols='65' rows='10' name='spacetop'>".$Spacetop."</textarea><br />
	<button type='submit' class='btn btn-success btn-lg'>Change</button>
	</form><br />";

	$content .= "<h3>Space left</h4>";

	$Spaceleft = $mysqli->query("SELECT value FROM faucet_settings WHERE id = '3' LIMIT 1")->fetch_assoc()['value'];
		
	if($_GET['c'] == 2){
		if(!isset($_POST['spaceleft'])){
			$content .= alert("danger", "Error.");
		} else {
			$Spaceleft = $mysqli->real_escape_string($_POST['spaceleft']);
			$mysqli->query("UPDATE faucet_settings Set value = '$Spaceleft' WHERE id = '3'");
			$content .= alert("success", "HTML Code 'Space left' changed successfully.");
			$Spaceleft = $_POST['spaceleft'];
		}
	}

	$content .= "<form method='post' action='?p=ads&c=2'>
	<textarea class='form-control' cols='65' rows='10' name='spaceleft'>".$Spaceleft."</textarea><br />
	<button type='submit' class='btn btn-success btn-lg'>Change</button>
	</form><br />";

	$content .= "<h3>Space right</h4>";

	$Spaceright = $mysqli->query("SELECT value FROM faucet_settings WHERE id = '4' LIMIT 1")->fetch_assoc()['value'];
		
	if($_GET['c'] == 3){
		if(!isset($_POST['spaceright'])){
			$content .= alert("danger", "Error.");
		} else {
			$Spaceright = $mysqli->real_escape_string($_POST['spaceright']);
			$mysqli->query("UPDATE faucet_settings Set value = '$Spaceright' WHERE id = '4'");
			$content .= alert("success", "HTML Code 'Space right' changed successfully.");
			$Spaceright = $_POST['spaceright'];
		}
	}

	$content .= "<form method='post' action='?p=ads&c=3'>
	<textarea class='form-control' cols='65' rows='10' name='spaceright'>".$Spaceright."</textarea><br />
	<button type='submit' class='btn btn-success btn-lg'>Change</button>
	</form><br />";

	break;

	case("bip"):
	$content .= "<a href='admin.php'>Back</a><br>
	<h3>Admin Settings</h3><h4>Ban IPs</h4>
	<p>Bots that drains your faucet is always hard to refuse.<br />
	With this feature you can ban IPs like known bots or VPN.</p><br /> 

	<h3>Add IPs to ban</h3>
	<p>Please enter for each line a IP address.</p>";

	if($_GET['c'] == 1){
		if(!$_POST['ips']){
			$content .= alert("danger", "Can't find IP Address.");
		} else {
			$ips = explode("\r\n", $_POST['ips']);
			foreach($ips as $banips) {
			    $banips2 = $mysqli->real_escape_string($banips);
			    $mysqli->query("INSERT INTO faucet_banned_ip (ip_address) VALUES ('$banips2')");
			}
			$content .= alert("success", "IP address added to the blacklist.");
		}
	}

	$content .= "<form method='post' action='?p=bip&c=1'>
	<textarea class='form-control' cols='65' rows='10' name='ips'></textarea><br />
	<button type='submit' class='btn btn-success btn-lg'>Add to the blacklist</button>
	</form><br />";

	$content .= "<h3>Show banned IPs/Remove IPs</h3>";

	if($_GET['delip']){
		$Ipid = $mysqli->real_escape_string($_GET['delip']);

		$Ip = $mysqli->query("SELECT * FROM faucet_banned_ip WHERE id = '$Ipid'");
		if($Ip->num_rows == 1){
			$mysqli->query("DELETE FROM faucet_banned_ip WHERE id = '$Ipid'");
			$content .= alert("success", "IP Address was removed from banlist.");
			$scroll = true;
		} else {
			$content .= alert("danger", "The IP Address/ID can't be found in the banlist.");
			$scroll = true;
		}
	}

	$headTable = "<table class='table' style='text-align: left; width: 100%;' cellpadding='2' cellspacing='2'>
	  <thead>
	    <tr>
	      <td>#</td>
	      <td>IP Address</td>
	      <td>Actions</td>
	    </tr>
	  </thead>";

	$bodyTable = "<tbody>";

	$BannedIPs = $mysqli->query("SELECT * FROM faucet_banned_ip");

	while($ShowBIP = $BannedIPs->fetch_assoc()){
		$bodyTable .= "<tr>
						<td>".$ShowBIP['id']."</td>
						<td>".$ShowBIP['ip_address']."</td>
						<td><a href='?p=bip&delip=".$ShowBIP['id']."'>Delete</a></td>
					   </tr>";
	}

	$footerTable = "</tbody></table>";
	$Table = $headTable.$bodyTable.$footerTable;

	$content .= "<div id='banlist'>
	$Table
	</div>";

	if($scroll){
		$content .= "<script type='text/javascript'>document.getElementById('banlist').scrollIntoView();</script>";
	}

	break;

	case("bad"):
	$content .= "<a href='admin.php'>Back</a><br>
	<h3>Admin Settings</h3><h4>Ban Address</h4>
	<p>Bots that drains your faucet is always hard to refuse.<br />
	With this feature you can ban Address.</p><br /> 

	<h3>Add Address to ban</h3>
	<p>Please enter for each line a Bitcoin Address.</p>";

	if($_GET['c'] == 1){
		if(!$_POST['addy']){
			$content .= alert("danger", "Can't find Bitcoin Address.");
		} else {
			$addy = explode("\r\n", $_POST['addy']);
			foreach($addy as $addy2) {
			    $banaddy = $mysqli->real_escape_string($addy2);
			    $mysqli->query("INSERT INTO faucet_banned_address (address) VALUES ('$banaddy')");
			}
			$content .= alert("success", "Bitcoin Address added to the blacklist.");
		}
	}

	$content .= "<form method='post' action='?p=bad&c=1'>
	<textarea class='form-control' cols='65' rows='10' name='addy'></textarea><br />
	<button type='submit' class='btn btn-success btn-lg'>Add to the blacklist</button>
	</form><br />";

	$content .= "<h3>Show banned Address/Remove Address</h3>";

	if($_GET['deladdy']){
		$Addyid = $mysqli->real_escape_string($_GET['deladdy']);

		$Addy = $mysqli->query("SELECT * FROM faucet_banned_address WHERE id = '$Addyid'");
		if($Ip->num_rows == 1){
			$mysqli->query("DELETE FROM faucet_banned_address WHERE id = '$Addyid'");
			$content .= alert("success", "Bitcoin Address was removed from banlist.");
			$scroll = true;
		} else {
			$content .= alert("danger", "The Bitcoin Address/ID can't be found in the banlist.");
			$scroll = true;
		}
	}

	$headTable = "<table class='table' style='text-align: left; width: 100%;' cellpadding='2' cellspacing='2'>
	  <thead>
	    <tr>
	      <td>#</td>
	      <td>Bitcoin Address</td>
	      <td>Actions</td>
	    </tr>
	  </thead>";

	$bodyTable = "<tbody>";

	$BannedAddy = $mysqli->query("SELECT * FROM faucet_banned_address");

	while($ShowBAD = $BannedAddy->fetch_assoc()){
		$bodyTable .= "<tr>
						<td>".$ShowBAD['id']."</td>
						<td>".$ShowBAD['address']."</td>
						<td><a href='?p=bad&deladdy=".$ShowBAD['id']."'>Delete</a></td>
					   </tr>";
	}

	$footerTable = "</tbody></table>";
	$Table = $headTable.$bodyTable.$footerTable;

	$content .= "<div id='banlist'>
	$Table
	</div>";

	if($scroll){
		$content .= "<script type='text/javascript'>document.getElementById('banlist').scrollIntoView();</script>";
	}

	break;
	}

} else {
	$content .= "<h3>Log In</h3>";

	if(isset($_POST['username']) AND isset($_POST['password'])){
		if(!isset($_POST['token']) || $_POST['token'] !== $_SESSION['token']) {
		unset($_SESSION['token']);
		$_SESSION['token'] = md5(md5(uniqid().uniqid().mt_rand()));
		exit;
		}
		unset($_SESSION['token']);
		$_SESSION['token'] = md5(md5(uniqid().uniqid().mt_rand()));

		if($_POST['username'] AND $_POST['password']){
			$username = $mysqli->real_escape_string($_POST['username']);
			$password = $mysqli->real_escape_string(hash("sha256", $_POST['password']));

			$UserDB = $mysqli->query("SELECT * FROM faucet_settings WHERE id = '12' LIMIT 1")->fetch_assoc()['value'];
			$PwDB = $mysqli->query("SELECT * FROM faucet_settings WHERE id = '13' LIMIT 1")->fetch_assoc()['value'];
			$loginAttempt = $mysqli->query("SELECT * FROM faucet_settings WHERE id = '17' LIMIT 1")->fetch_assoc()['value'];

			$lastLoginSecond = time() - $loginAttempt;

			$mysqli->query("UPDATE faucet_settings Set value = '".time()."' WHERE id = '17'");

			if($lastLoginSecond < 4){
				$content .= alert("danger", "You're trying to log in very fast.");
			} else {
				if($UserDB == $username){
					if($PwDB == $password){
						$_SESSION['admin'] = "Admin_".$username."_Password_".$password;
						header("Location: admin.php");
						exit;
					} else {
						$content .= alert("danger", "Password is wrong.");
					}
				} else {
					$content .= alert("danger", "Username is wrong.");
				}
			}
		} else if($_POST['username']){
			$content .= alert("Please fill all fields.");
		}
	}

	$content .= "
	<form method='post' action='?'>
	<div class='form-group'>
		<label>Username</label>
		<center><input class='form-control' type='text' name='username' style='width: 225px;' placeholder='Username ...'></center>
	</div>
	
	<div class='form-group'>
		<label>Password</label>
		<center><input class='form-control' type='password' name='password' style='width: 225px;' placeholder='Password ...'></center>
	</div>
	<input type='hidden' name='token' value='".$_SESSION['token']."'/>
	<button type='submit' class='btn btn-primary'>Log In</button>
	</form>";
}

$tpl->assign("content", $content);
$tpl->display();
?>
