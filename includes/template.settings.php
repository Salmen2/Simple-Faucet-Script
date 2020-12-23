<?php

$tpl = new Template();
$tpl->load("index.tpl");

$bootsWatchStyles = array('Cerulean', 'Cosmo', 'Cyborg', 'Darkly', 'Flatly', 'Journal', 'Lumen', 'Paper', 'Readable', 'Sandstone', 'Simplex', 'Slate', 'Spacelab', 'Superhero', 'United', 'Yeti');

$selectedTheme = $mysqli->query("SELECT value FROM faucet_settings WHERE id = '25' LIMIT 1")->fetch_assoc()['value'];

if($selectedTheme == "")
		$tpl->assign("bootstrapStyle", "css/bootstrap.min.css");
	else
		$tpl->assign("bootstrapStyle", "https://maxcdn.bootstrapcdn.com/bootswatch/3.3.7/".(strtolower($selectedTheme))."/bootstrap.min.css");


$Faucetname = $mysqli->query("SELECT * FROM faucet_settings WHERE id = '1'")->fetch_assoc()['value'];
$tpl->assign("faucetname", $Faucetname);

$Spacetop = $mysqli->query("SELECT * FROM faucet_spaces WHERE id = '1'")->fetch_assoc()['space'];
$tpl->assign("spacetop", $Spacetop);

$Spaceleft = $mysqli->query("SELECT * FROM faucet_spaces WHERE id = '2'")->fetch_assoc()['space'];
$tpl->assign("spaceleft", $Spaceleft);

$Spaceright = $mysqli->query("SELECT * FROM faucet_spaces WHERE id = '3'")->fetch_assoc()['space'];
$tpl->assign("spaceright", $Spaceright);

// Navbar

$navLinks = [["Faucet", "index.php"]];

$addonListSQL = $mysqli->query("SELECT * FROM faucet_addon_list WHERE enabled = '1'");

while($addonRow = $addonListSQL->fetch_assoc())
	$navLinks[] = [$addonRow['name'], "page.php?p=".$addonRow['directory_name']];


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