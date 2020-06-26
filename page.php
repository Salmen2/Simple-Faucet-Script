<?php
include("includes/core.php");

if($_GET['p']){

	define('ADDON_ACTIVE', 1);

	$addonList = array();
	$addonListSQL = $mysqli->query("SELECT * FROM faucet_addon_list WHERE enabled = '1'");

	while($addonRow = $addonListSQL->fetch_assoc())
		$addonList[] = $addonRow['directory_name'];

	switch($_GET['p']){
		case(""):
		default:
		header("Location: index.php");
		exit;
		break;

		case in_array($_GET['p'], $addonList):
			include("addons/".$_GET['p']."/__page.php");
		break;
	}

} else {
	$pageID = $mysqli->real_escape_string($_GET['pid']);
	if(!is_numeric($pageID)){
		header("Location: index.php");
		exit;
	}
	$pageContent = $mysqli->query("SELECT * FROM faucet_pages WHERE id = '$pageID'");
	if($pageContent->num_rows == 1){
		$pageContent = $mysqli->query("SELECT * FROM faucet_pages WHERE id = '$pageID'")->fetch_assoc();
		$content = $pageContent['content'];
	}
}

$tpl->assign("content", $content);
$tpl->display();
?>