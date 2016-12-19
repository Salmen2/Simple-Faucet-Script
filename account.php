<?php
include("includes/core.php");

$content = "";

if($user){
	$content .= "<h1>Account</h1>";

	$content .= "<h3>Address</h3>";
	$content .= $user['address'];
	$content .= "<h3>Balance</h3>";
	$content .= toSatoshi($user['balance'])." Satoshi<br /><br />";

	if(toSatoshi($user['balance']) >= 1){
		if($_GET['pt'] == 1){
			if(!isset($_POST['token']) || $_POST['token'] !== $_SESSION['token']) {
			unset($_SESSION['token']);
			$_SESSION['token'] = md5(md5(uniqid().uniqid().mt_rand()));
			exit;
			}
			unset($_SESSION['token']);
			$_SESSION['token'] = md5(md5(uniqid().uniqid().mt_rand()));

			$api_key = $mysqli->query("SELECT * FROM faucet_settings WHERE id = '10' LIMIT 1")->fetch_assoc()['value'];
			$currency = "BTC";
			$faucethub = new FaucetHub($api_key, $currency);
			$result = $faucethub->send($user['address'], toSatoshi($user['balance']), $realIpAddressUser);
			if($result["success"] === true){
				$timestamp = time();
				$mysqli->query("UPDATE faucet_user_list Set balance = '0' WHERE id = '{$user['id']}'");
				$mysqli->query("INSERT INTO faucet_transactions (userid, type, amount, timestamp) VALUES ('{$user['id']}', 'Withdraw', '{$user['balance']}', '$timestamp')");
			    $content .= $result["html"];
			} else {
			    $content .= $result["html"];
			}
		}

		$content .= "<form method='post' action='?pt=1'>
		<input type='hidden' name='token' value='".$_SESSION['token']."'/><button type='submit' class='btn btn-primary'>Withdraw to Faucethub</button></form>";
	} else {
		$content .= "<a href='#' class='btn btn-danger'>Withdraw is not avaible.</a>";
	}
	$content .= "<br /><br />";

	// Total Stats

	$TotalClaims = $mysqli->query("SELECT COUNT(id) FROM faucet_transactions WHERE type = 'Payout' AND userid = '{$user['id']}'")->fetch_row()[0];
	$TotalClaimed = $mysqli->query("SELECT SUM(amount) FROM faucet_transactions WHERE type = 'Payout' AND userid = '{$user['id']}'")->fetch_row()[0];
	$TotalClaimed = $TotalClaimed ? $TotalClaimed : 0;

	// 24 Hours stats

	$Last24Hours = time() - 86400;
	$Last24HoursClaims = $mysqli->query("SELECT COUNT(id) FROM faucet_transactions WHERE type = 'Payout' AND userid = '{$user['id']}' AND timestamp > '$Last24Hours'")->fetch_row()[0];
	$Last24HoursClaimed = $mysqli->query("SELECT SUM(amount) FROM faucet_transactions WHERE type = 'Payout' AND userid = '{$user['id']}' AND timestamp > '$Last24Hours'")->fetch_row()[0];
	$Last24HoursClaimed = $Last24HoursClaimed ? $Last24HoursClaimed : 0;

	// Referral

	$TotalReferralPayout = $mysqli->query("SELECT SUM(amount) FROM faucet_transactions WHERE type = 'Referral Payout' AND userid = '{$user['id']}'")->fetch_row()[0];
	$TotalReferralPayout = $TotalReferralPayout ? $TotalReferralPayout : 0;

	$Last24HoursReferralPayout = $mysqli->query("SELECT SUM(amount) FROM faucet_transactions WHERE type = 'Referral Payout' AND userid = '{$user['id']}' AND timestamp > '$Last24Hours'")->fetch_row()[0];
	$Last24HoursReferralPayout = $Last24HoursReferralPayout ? $Last24HoursReferralPayout : 0;


	$content .= "<h3>Stats</h3>
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
		<h4>Total Referral Payout</h4>
		<b>".toSatoshi($Last24HoursReferralPayout)."</b><br />Satoshi
	</div>
	</div>";

	$headTable = "<h3>Last 15 Transactions</h3><center><table class='table' style='text-align: center; width: 400px;'border='0' cellpadding='2' cellspacing='2'>
	  <thead>
	    <tr>
	      <td>Type</td>
	      <td>Time</td>
	      <td>Amount</td>
	    </tr>
	    </thead>";

	$bodyTable = "<tbody>";

	$UserTransactions = $mysqli->query("SELECT * FROM faucet_transactions WHERE userid = '{$user['id']}' ORDER BY id DESC LIMIT 15");
	while($Tx = $UserTransactions->fetch_assoc()){
		$bodyTable .= "<tr>
						<td>".$Tx['type']."</td>
						<td>".findTimeAgo($Tx['timestamp'])."</td>
						<td>".$Tx['amount']."</td>
					</tr>";
	}

	$footerTable = "</tbody></table></center>";

	$content .= $headTable.$bodyTable.$footerTable;

} else {
	header("Location: index.php");
	exit;
}

$tpl->assign("content", $content);
$tpl->display();
?>
