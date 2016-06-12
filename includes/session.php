<?php
if($_SESSION['address']){
	$Address2 = $mysqli->real_escape_string($_SESSION['address']);
	$AddressCheck = $mysqli->query("SELECT * FROM faucet_user_list WHERE address = '$Address2'");
	if($AddressCheck->num_rows == 1){
		$user = $AddressCheck->fetch_assoc()['id'];
		$user = $mysqli->query("SELECT * FROM faucet_user_list WHERE id = '$user'")->fetch_assoc();
	} else {
		unset($_SESSION['address']);
		header("Location: index.php");
		exit;
	}
}
?>
