<?php
include("includes/core.php");

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

$tpl->assign("content", $content);
$tpl->display();
?>