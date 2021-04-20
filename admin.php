<?php
include("includes/core.php");

$content .= "<h1>Admin</h1>";

if($_SESSION['admin']){
	$AdminSessionKey = $_SESSION['admin'];
	$UserDB = $mysqli->query("SELECT * FROM faucet_settings WHERE id = '12' LIMIT 1")->fetch_assoc()['value'];
	$PwDB = $mysqli->query("SELECT * FROM faucet_settings WHERE id = '13' LIMIT 1")->fetch_assoc()['value'];
	$DatabaseAdminKey = "Admin_".$UserDB."_Password_".$PwDB;
	if($AdminSessionKey != $DatabaseAdminKey){ unset($_SESSION['admin']); header("Location: admin.php"); die; }

	// Addon list - Start

	$directories = array_diff(scandir('addons'), array('..', '.'));

	$addonList = array();

	foreach($directories AS $directoryName){
		if(file_exists("addons/".$directoryName."/__acp.php") == true AND file_exists("addons/".$directoryName."/__page.php") == true){
			$addonList[] = $directoryName;
			$btnContent .= "<a class='btn btn-default' href='?p=".$directoryName."'>".ucfirst($directoryName)." settings</a><br />";
		}
	}

	define('ADDON_ACTIVE', 1);

	// Addon list - End

	switch($_GET['p']){
		case(""):
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

		$TotalReferralPayout = $mysqli->query("SELECT SUM(amount) FROM faucet_transactions WHERE type = 'Referral'")->fetch_row()[0];
		$TotalReferralPayout = $TotalReferralPayout ? $TotalReferralPayout : 0;

		$Last24HoursReferralPayout = $mysqli->query("SELECT SUM(amount) FROM faucet_transactions WHERE type = 'Referral' AND timestamp > '$Last24Hours'")->fetch_row()[0];
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
		<a class='btn btn-default' href='?p=wds'>Withdrawal settings</a><br />
		<a class='btn btn-default' href='?p=ps'>Page settings</a><br />
		<a class='btn btn-default' href='?p=ads'>Advertising settings</a><br />
		<a class='btn btn-default' href='?p=adds'>Addon settings</a><br />
		".$btnContent."

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

		$content .= "<h3>Style Settings</h3><h4>Bootswatch</h4>";

		$selectedTheme = $mysqli->query("SELECT value FROM faucet_settings WHERE id = '25' LIMIT 1")->fetch_assoc()['value'];

		if($_GET['c'] == "st"){
			if($_POST['selected_style']){
				if($_POST['selected_style'] == "Default"){
					$mysqli->query("UPDATE faucet_settings Set value = '' WHERE id = '25'");
					$content .= alert("success", "Theme changed to Default.");
					$selectedTheme = "";
				} else {
					$pSelectedStyle = $_POST['selected_style'];
					if(in_array($pSelectedStyle, $bootsWatchStyles) == false){
						$content .= alert("danger", "Invalid Bootswatch theme.");
					} else {
						$mysqli->query("UPDATE faucet_settings Set value = '$pSelectedStyle' WHERE id = '25'");
						$content .= alert("success", "Theme changed to ".$pSelectedStyle.".");
						$selectedTheme = $pSelectedStyle;
					}
				}
			}
		}

		foreach ($bootsWatchStyles as $themeName) {
			$optionsBootsWatch .= "<option ".(($themeName == $selectedTheme) ? 'selected' : '').">".$themeName."</option>";
		}


		$content .= "<form method='post' action='?p=as&c=st'>
		<div class='form-group'>
		  <label class='control-label'>Bootswatch Theme</label>
		  <center>
		    <select style='width: 150px;' name='selected_style' class='form-control bootswatchInput'>
		      <option>Default</option>
		      ".$optionsBootsWatch."
		    </select>
		    <span class='help-block demoBootsWatch'><a target='_blank' href='".(($selectedTheme != "Default") ? 'https://bootswatch.com/3/'.(strtolower($selectedTheme)).'/' : '#')."'>Demo</a></span>
		    <script>
		    $(document).ready(function(){
		    	$('.bootswatchInput').change(function(){
		    		var selectedTheme = $(this).val();
		    		selectedTheme = selectedTheme.toLowerCase();
		    		if(selectedTheme == 'default'){
		    			$('.demoBootsWatch').html('<a target=\"_blank\" href=\"\">Demo</a>');
		    		} else {
		    			$('.demoBootsWatch').html('<a target=\"_blank\" href=\"https://bootswatch.com/3/' + selectedTheme + '/\">Demo</a>');
		    		}
		    	});
		    });
		    </script>
		  </center>
		</div>
		<button type='submit' class='btn btn-primary'>Change</button>
		</form><br />";

		$content .= "<h3>Keys settings</h3><h4>reCaptcha Keys</h4>";

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
				$content .= alert("success", "reCaptcha Keys have been changed successfully.");
				$reCaptcha_privkey = $mysqli->real_escape_string($_POST['recaptcha_privkey']);
				$reCaptcha_pubkey = $mysqli->real_escape_string($_POST['recaptcha_pubkey']);
			}
		}

		$content .= "<form method='post' action='?p=as&c=6'>
		<div class='form-group'>
			<label>reCaptcha Public Key</label>
			<center><input class='form-control' type='text' value='".$reCaptcha_pubkey."' name='recaptcha_pubkey' style='width: 375px;' placeholder='reCaptcha Public Key'></center>
		</div>
		<div class='form-group'>
			<label>reCaptcha Private Key</label>
			<center><input class='form-control' type='text' value='".$reCaptcha_privkey."' name='recaptcha_privkey' style='width: 375px;' placeholder='reCaptcha Private Key'></center>
		</div>
		<button type='submit' class='btn btn-primary'>Change</button>
		</form><br />";


		$content .= "<h4>SolveMedia Keys</h4>";

		$solvemedia_ckey = $mysqli->query("SELECT * FROM faucet_settings WHERE id = '2' LIMIT 1")->fetch_assoc()['value'];
		$solvemedia_vkey = $mysqli->query("SELECT * FROM faucet_settings WHERE id = '3' LIMIT 1")->fetch_assoc()['value'];
		$solvemedia_hkey = $mysqli->query("SELECT * FROM faucet_settings WHERE id = '4' LIMIT 1")->fetch_assoc()['value'];
		
		if($_GET['c'] == 7){
			if(!$_POST['solvemedia_ckey'] OR !$_POST['solvemedia_vkey'] OR !$_POST['solvemedia_hkey']){
				$content .= alert("danger", "SolveMedia Keys can't be blank.");
			} else {
				$solvemedia_ckey = $mysqli->real_escape_string($_POST['solvemedia_ckey']);
				$solvemedia_vkey = $mysqli->real_escape_string($_POST['solvemedia_vkey']);
				$solvemedia_hkey = $mysqli->real_escape_string($_POST['solvemedia_hkey']);

				$mysqli->query("UPDATE faucet_settings Set value = '$solvemedia_ckey' WHERE id = '2'");
				$mysqli->query("UPDATE faucet_settings Set value = '$solvemedia_vkey' WHERE id = '3'");
				$mysqli->query("UPDATE faucet_settings Set value = '$solvemedia_hkey' WHERE id = '4'");
				$content .= alert("success", "SolveMedia Keys have been changed successfully.");
			}
		}

		$content .= "<form method='post' action='?p=as&c=7'>
		<div class='form-group'>
			<label>SolveMedia Challenge Key (C-Key)</label>
			<center><input class='form-control' type='text' value='".$solvemedia_ckey."' name='solvemedia_ckey' style='width: 375px;' placeholder='SolveMedia C-Key'></center>
		</div>
		<div class='form-group'>
			<label>SolveMedia Verification Key (V-Key)</label>
			<center><input class='form-control' type='text' value='".$solvemedia_vkey."' name='solvemedia_vkey' style='width: 375px;' placeholder='SolveMedia V-Key'></center>
		</div>
		<div class='form-group'>
			<label>SolveMedia Authentication Hash Key (H-Key)</label>
			<center><input class='form-control' type='text' value='".$solvemedia_hkey."' name='solvemedia_hkey' style='width: 375px;' placeholder='SolveMedia H-Key'></center>
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

		$content .= "<br /><br /><h4>VPN/Proxy</h4>
		<p>Enable or disable claiming from faucet using VPN or Proxy</p>";

		$shieldStatus = $mysqli->query("SELECT * FROM faucet_settings WHERE id = '14' LIMIT 1")->fetch_assoc()['value'];
		$iphubApiKey = $mysqli->query("SELECT * FROM faucet_settings WHERE id = '22' LIMIT 1")->fetch_assoc()['value'];

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
				if(!$iphubApiKey){
					$content .= alert("warning", "Please enter firstly the IPHub API Key below.");
				} else {
					$mysqli->query("UPDATE faucet_settings Set value = 'yes' WHERE id = '14'");
					$content .= alert("success", "VPN/Proxy Shield is enabled.");
					$content .= "<a href='?p=as&sp=n' class='btn btn-default'>Disable Shield</a>";
				}
			} else {
				$content .= "<a href='?p=as&sp=y' class='btn btn-default'>Enable Shield</a>";
			}
		}


		if($_GET['c'] == 7){
			if(isset($_POST['iphub_apikey'])){
				$iphubApiKey = $mysqli->real_escape_string($_POST['iphub_apikey']);
				$mysqli->query("UPDATE faucet_settings Set value = '$iphubApiKey' WHERE id = '22'");
				$content .= alert("success", "The API has been changed successfully.");
			}
		}

		$content .= "<br /><p>The VPN/Proxy shield requires an API Key of IPHub.info.</p><br /><form method='post' action='?p=as&c=7'>
		<div class='form-group'>
			<label>IPHub API Key</label>
			<center><input class='form-control' type='text' value='".$iphubApiKey."' name='iphub_apikey' style='width: 375px;' placeholder='IPHub API Key'></center>
		</div>
		<button type='submit' class='btn btn-primary'>Change</button>
		</form><br />";


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
		$content .= "<a href='admin.php'>Back</a><br><h3>Page settings</h3><h4>Create new Page</h4>";

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

	case("wds"):

	$content .= "<a href='admin.php'>Back</a><br>
	<h3>Withdrawal settings</h3><br /><p><strong>Note:</strong> If you leave the fields blank for a particular withdrawal method, then that option won't appear  on the Account page.</p><br />
	<script type='text/javascript'>
	$('#myTabs a').click(function (e) {
	  e.preventDefault()
	  $(this).tab('show')
	});
	</script>";

	$expressCryptoApiToken = $mysqli->query("SELECT value FROM faucet_settings WHERE id = '10'")->fetch_assoc()['value'];
	$expressCryptoUserToken = $mysqli->query("SELECT value FROM faucet_settings WHERE id = '18'")->fetch_assoc()['value'];
	$faucetpayApiToken = $mysqli->query("SELECT value FROM faucet_settings WHERE id = '19'")->fetch_assoc()['value'];
	$blockioApiKey = $mysqli->query("SELECT value FROM faucet_settings WHERE id = '20'")->fetch_assoc()['value'];
	$blockioPin = $mysqli->query("SELECT value FROM faucet_settings WHERE id = '21'")->fetch_assoc()['value'];

	if($_POST['withdrawal_method']){
		if($_POST['withdrawal_method'] == 1){
			if($_POST['api_key'] != $expressCryptoApiToken){
				$expressCryptoApiToken = $mysqli->real_escape_string($_POST['api_key']);
				$mysqli->query("UPDATE faucet_settings Set value = '$expressCryptoApiToken' WHERE id = '10'");
				$alertForm .= alert("success", "ExpressCrypto API Key has been changed.");
			}

			if($_POST['user_token'] != $expressCryptoUserToken){
				$expressCryptoUserToken = $mysqli->real_escape_string($_POST['user_token']);
				$mysqli->query("UPDATE faucet_settings Set value = '$expressCryptoUserToken' WHERE id = '18'");
				$alertForm .= alert("success", "ExpressCrypto User Token has been changed.");
			}
		} else if($_POST['withdrawal_method'] == 2){
			if($_POST['api_key'] != $faucetpayApiToken){
				$faucetpayApiToken = $mysqli->real_escape_string($_POST['api_key']);
				$mysqli->query("UPDATE faucet_settings Set value = '$faucetpayApiToken' WHERE id = '19'");
				$alertForm .= alert("success", "FaucetPay API Key has been changed.");
			}
		} else if($_POST['withdrawal_method'] == 3){
			if($_POST['api_key'] != $blockioApiKey){
				$blockioApiKey = $mysqli->real_escape_string($_POST['api_key']);
				$mysqli->query("UPDATE faucet_settings Set value = '$blockioApiKey' WHERE id = '20'");
				$alertForm .= alert("success", "Block.io API Key has been changed.");
			}

			if($_POST['pin'] != $blockioPin){
				$blockioPin = $mysqli->real_escape_string($_POST['pin']);
				$mysqli->query("UPDATE faucet_settings Set value = '$blockioPin' WHERE id = '21'");
				$alertForm .= alert("success", "Block.io PIN has been changed.");
			}
		}
	}

	$thresholdGateway = $mysqli->query("SELECT value FROM faucet_settings WHERE id = '23'")->fetch_assoc()['value'];
	$thresholdDirect = $mysqli->query("SELECT value FROM faucet_settings WHERE id = '24'")->fetch_assoc()['value'];


	if($_POST['threshold_gateway']){
		if($_POST['threshold_gateway'] != $thresholdGateway OR $_POST['threshold_direct'] != $thresholdDirect){
			$pThreSholdGateway = $mysqli->real_escape_string($_POST['threshold_gateway']);
			$pThreSholdDirect = $mysqli->real_escape_string($_POST['threshold_direct']);

			if(!is_numeric($pThreSholdGateway) OR !is_numeric($pThreSholdDirect)){
				$alertFormThreshold = alert("danger", "Please enter numeric values.");
			} else {
				$mysqli->query("UPDATE faucet_settings Set value = '$pThreSholdGateway' WHERE id = '23'");
				$mysqli->query("UPDATE faucet_settings Set value = '$pThreSholdDirect' WHERE id = '24'");
				$alertFormThreshold = alert("success", "Withdrawal threshold saved.");

				$thresholdGateway = $pThreSholdGateway;
				$thresholdDirect = $pThreSholdDirect;
			}
		}
	}


	$content .= $alertForm."

			<div>

	  <!-- Nav tabs -->
	  <ul class=\"nav nav-tabs\" role=\"tablist\">
	    <li role=\"presentation\" class=\"active\"><a href=\"#expresscryptotab\" aria-controls=\"expresscryptotab\" role=\"tab\" data-toggle=\"tab\">ExpressCrypto</a></li>
	    <li role=\"presentation\"><a href=\"#faucetpaytab\" aria-controls=\"faucetpaytab\" role=\"tab\" data-toggle=\"tab\">FaucetPay</a></li>
	    <li role=\"presentation\"><a href=\"#blockiotab\" aria-controls=\"blockiotab\" role=\"tab\" data-toggle=\"tab\">Block.io</a></li>
	  </ul><br />

	  <!-- Tab panes -->
	  <div class=\"tab-content\">

		<div role=\"tabpanel\" class=\"tab-pane active\" id=\"expresscryptotab\">
			<form method='post' class='form-horizontal' action='?p=wds'>

				<div class='form-group'>
					<label class='col-md-3 control-label'>API Key</label>
					<div class='col-md-8'>
						<input type='text' class='form-control' name='api_key' value='".$expressCryptoApiToken."' placeholder='API Key ...' />
					</div>
				</div><br />

				<div class='form-group'>
					<label class='col-md-3 control-label'>User Token</label>
					<div class='col-md-8'>
						<input type='text' class='form-control' name='user_token' value='".$expressCryptoUserToken."' placeholder='User Token ...' />
					</div>
				</div><br />

				<p>You can generate an API Key at <a href='https://expresscrypto.io/' target='_blank'>Expresscrypto.io</a>.</p><br />

				<input type='hidden' name='withdrawal_method' value='1' />
				<button type='submit' class='btn btn-success'>Save</button>
			</form>
		</div>

		<div role=\"tabpanel\" class=\"tab-pane\" id=\"faucetpaytab\">
			<form method='post' class='form-horizontal' action='?p=wds'>

				<div class='form-group'>
					<label class='col-md-3 control-label'>API Key</label>
					<div class='col-md-8'>
						<input type='text' class='form-control' name='api_key' value='".$faucetpayApiToken."' placeholder='API Key ...' />
					</div>
				</div><br />

				<p>You can generate an API Key at <a href='https://faucetpay.io/' target='_blank'>Faucetpay.io</a>.</p><br />

				<input type='hidden' name='withdrawal_method' value='2' />
				<button type='submit' class='btn btn-success'>Save</button>
			</form>
		</div>

		<div role=\"tabpanel\" class=\"tab-pane\" id=\"blockiotab\">
			<form method='post' class='form-horizontal' action='?p=wds'>

				<div class='form-group'>
					<label class='col-md-3 control-label'>API Key</label>
					<div class='col-md-8'>
						<input type='text' class='form-control' name='api_key' value='".$blockioApiKey."' placeholder='API Key ...' />
					</div>
				</div><br />

				<div class='form-group'>
					<label class='col-md-3 control-label'>PIN</label>
					<div class='col-md-8'>
						<input type='text' class='form-control' name='pin' value='".$blockioPin."' placeholder='PIN ...' />
					</div>
				</div><br />

				<p>You can generate an API Key at <a href='https://block.io/' target='_blank'>Block.io</a>.</p><br />

				<input type='hidden' name='withdrawal_method' value='3' />
				<button type='submit' class='btn btn-success'>Save</button>
			</form>
		</div><br /><br />

		<h4>Withdrawal Thresholds</h4><br />

		".$alertFormThreshold."<br />

		<form method='post' class='form-horizontal' action='?p=wds'>

				<div class='form-group'>
					<label class='col-md-5 control-label'>Withdrawal Threshold (Payment Provider)</label>
					<div class='col-md-6'>
						<input type='number' class='form-control' name='threshold_gateway' value='".$thresholdGateway."' placeholder='...' />
						<span class='help-block'>Withdrawal thresholds for payments over ExpressCrypto and FaucetPay</span>
					</div>
				</div><br />

				<div class='form-group'>
					<label class='col-md-5 control-label'>Withdrawal Threshold (Direct)</label>
					<div class='col-md-6'>
						<input type='number' class='form-control' name='threshold_direct' value='".$thresholdDirect."' placeholder='...' />
						<span class='help-block'>Withdrawal thresholds for direct payments using Block.io</span>
					</div>
				</div><br />

				<button type='submit' class='btn btn-success'>Save</button>
		</form>



	  </div>
	</div>";

	break;

	case("ads"):

	$content .= "<a href='admin.php'>Back</a><br>
	<h3>Advertising settings</h3>";

	$content .= "<h3>Space top</h4>";

	$Spacetop = $mysqli->query("SELECT space FROM faucet_spaces WHERE id = '1' LIMIT 1")->fetch_assoc()['space'];
		
	if($_GET['c'] == 1){
		if(!isset($_POST['spacetop'])){
			$content .= alert("danger", "Error.");
		} else {
			$Spacetop = $mysqli->real_escape_string($_POST['spacetop']);
			$mysqli->query("UPDATE faucet_spaces Set space = '$Spacetop' WHERE id = '1'");
			$content .= alert("success", "HTML Code 'Space top' changed successfully. Change will take in effect on reload.");
			$Spacetop = $_POST['spacetop'];
		}
	}

	$content .= "<form method='post' action='?p=ads&c=1'>
	<textarea class='form-control' cols='65' rows='10' name='spacetop'>".$Spacetop."</textarea><br />
	<button type='submit' class='btn btn-success btn-lg'>Change</button>
	</form><br />";

	$content .= "<h3>Space left</h4>";

	$Spaceleft = $mysqli->query("SELECT space FROM faucet_spaces WHERE id = '2' LIMIT 1")->fetch_assoc()['space'];
		
	if($_GET['c'] == 2){
		if(!isset($_POST['spaceleft'])){
			$content .= alert("danger", "Error.");
		} else {
			$Spaceleft = $mysqli->real_escape_string($_POST['spaceleft']);
			$mysqli->query("UPDATE faucet_spaces Set space = '$Spaceleft' WHERE id = '2'");
			$content .= alert("success", "HTML Code 'Space left' changed successfully. Change will take in effect on reload.");
			$Spaceleft = $_POST['spaceleft'];
		}
	}

	$content .= "<form method='post' action='?p=ads&c=2'>
	<textarea class='form-control' cols='65' rows='10' name='spaceleft'>".$Spaceleft."</textarea><br />
	<button type='submit' class='btn btn-success btn-lg'>Change</button>
	</form><br />";

	$content .= "<h3>Space right</h4>";

	$Spaceright = $mysqli->query("SELECT space FROM faucet_spaces WHERE id = '3' LIMIT 1")->fetch_assoc()['space'];
		
	if($_GET['c'] == 3){
		if(!isset($_POST['spaceright'])){
			$content .= alert("danger", "Error.");
		} else {
			$Spaceright = $mysqli->real_escape_string($_POST['spaceright']);
			$mysqli->query("UPDATE faucet_spaces Set space = '$Spaceright' WHERE id = '3'");
			$content .= alert("success", "HTML Code 'Space right' changed successfully. Change will take in effect on reload.");
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
	<p>Please enter for each line a Bitcoin Address or EC-UserID.</p>";

	if($_GET['c'] == 1){
		if(!$_POST['addy']){
			$content .= alert("danger", "Can't find Bitcoin Address or EC-UserID.");
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
			$content .= alert("danger", "The Bitcoin Address/EC-UserID can't be found in the banlist.");
			$scroll = true;
		}
	}

	$headTable = "<table class='table' style='text-align: left; width: 100%;' cellpadding='2' cellspacing='2'>
	  <thead>
	    <tr>
	      <td>#</td>
	      <td>Bitcoin Address/EC-ID</td>
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

	case("adds"):

	$content .= "<a href='admin.php'>Back</a><br>
	<h3>Addon Settings</h3>
	<p>Enable or disable installed Addons below. Disabled addons will disappear for the users.</p>";

	if(isset($_GET['sw'])){
		$pSWID = $mysqli->real_escape_string($_GET['sw']);
		$checkAddon = $mysqli->query("SELECT * FROM faucet_addon_list WHERE id = '$pSWID'");
		if($checkAddon->num_rows == 1){
			$addonData = $checkAddon->fetch_assoc();
			if($addonData['enabled'] == 1){
				$mysqli->query("UPDATE faucet_addon_list Set enabled = '0' WHERE id = '$pSWID'");
				$content .= alert("info", "Addon '".$addonData['name']."' has been disabled.");
			} else {
				$mysqli->query("UPDATE faucet_addon_list Set enabled = '1' WHERE id = '$pSWID'");
				$content .= alert("info", "Addon '".$addonData['name']."' has been enabled.");
			}
		}
	}


	$addonList = $mysqli->query("SELECT * FROM faucet_addon_list");

	$content .= "<table class='table table-bordered' style='text-align: center; width: 100%;' border='0'>
	  <thead>
	    <tr>
	      <td>Name</td>
	      <td>Actions</td>
	    </tr>
	  </thead><tbody>";

	while($addonRow = $addonList->fetch_assoc()){
		if($addonRow['enabled'] == 1)
				$status = "<span style='color:green;'>Enabled</span> <a href='?p=adds&sw=".$addonRow['id']."' class='btn btn-primary' role='button'>Disable</a>";
			else
				$status = "<span style='color:red;'>Disabled</span> <a href='?p=adds&sw=".$addonRow['id']."' class='btn btn-primary' role='button'>Enable</a>";
		
		$content .= "<tr><td>".$addonRow['name']."</td><td>".$status."</td></tr>";
	}

	$content .= "</tbody></table>";

	break;


	case(in_array($_GET['p'], $addonList)):
		include("addons/".$_GET['p']."/__acp.php");
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
