<?php

// Database Configuration

$dbHost = "localhost";
$dbUser = "root";
$dbPW = "wani8segu";
$dbName = "faucetscript";

// Establish connection

$mysqli = mysqli_connect($dbHost, $dbUser, $dbPW, $dbName);

// Check connection
if(mysqli_connect_errno()){
 	echo "Failed to connect to MySQL: " . mysqli_connect_error();
}

// Website

$Website_Url = "";

?>