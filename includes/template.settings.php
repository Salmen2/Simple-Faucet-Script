<?php

$tpl = new Template();
$tpl->load("index.tpl");


$Faucetname = $mysqli->query("SELECT * FROM faucet_settings WHERE id = '1'")->fetch_assoc()['value'];
$tpl->assign("faucetname", $Faucetname);

$Spacetop = $mysqli->query("SELECT * FROM faucet_settings WHERE id = '2'")->fetch_assoc()['value'];
$tpl->assign("spacetop", $Spacetop);

$Spaceleft = $mysqli->query("SELECT * FROM faucet_settings WHERE id = '3'")->fetch_assoc()['value'];
$tpl->assign("spaceleft", $Spaceleft);

$Spaceright = $mysqli->query("SELECT * FROM faucet_settings WHERE id = '4'")->fetch_assoc()['value'];
$tpl->assign("spaceright", $Spaceright);

// Navbar

$navLinks = [["Faucet", "index.php"]];

$PageQuery = $mysqli->query("SELECT * FROM faucet_pages");

while($NavLinks = $PageQuery->fetch_assoc()){
	$Link = "page.php?pid=".$NavLinks['id'];
	$navLinks[] = [$NavLinks['name'], $Link];
}

foreach($navLinks as $elem){
	$navBarLinks .= "<li><a href='{$elem[1]}'>{$elem[0]}</a></li>";
}

$tpl->assign("navBar", $navBarLinks);

?>