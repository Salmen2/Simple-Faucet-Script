<?php
include("includes/config.php");

if(version_compare(phpversion(), '5.4', '>=')){
    $phpCheck = "<div class='text-success'>Proper PHP Version installed.</div>";
} else {
    $phpCheck = "<div class='text-danger'>Wrong PHP Version installed. Please use 5.4 or higher.</div>";
}

if(mysqli_connect_errno()){
    $mysqlConn = "<div class='text-danger'>Couldn't establish a MySQL connection. Please try again.</div>";
} else {
    $mysqlConn = "<div class='text-success'>MySQL connection established successfully.</div>";
}

if(function_exists('curl_init') === false){
    $curlCheck = "<div class='text-danger'>cURL is not installed. Please install PHP-cURL and cURL if you haven't done so yet.</div>";
} else {
    $curlCheck = "<div class='text-success'>cURL is installed.</div>";
}

if(function_exists('curl_init') === false){
    $curlCheck = "<div class='text-danger'>cURL is not installed. Please install PHP-cURL and cURL if you haven't done so yet.</div>";
} else {
    $curlCheck = "<div class='text-success'>cURL is installed.</div>";
}

if(extension_loaded('gmp') === false){
    $gmpCheck = "<div class='text-danger'>GMP is not installed. Please install PHP-GMP.</div>";
} else {
    $gmpCheck = "<div class='text-success'>GMP is installed.</div>";
}

?>
<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <!-- The above 3 meta tags *must* come first in the head; any other head content must come *after* these tags -->
    <title>Installation - Simple Faucet Script</title>

    <!-- Bootstrap -->
    <link href="css/bootstrap.min.css" rel="stylesheet">
    <link href="css/style.css" rel="stylesheet">

    <script src="https://ajax.googleapis.com/ajax/libs/jquery/1.11.3/jquery.min.js"></script>
    <script src="js/bootstrap.min.js"></script>
    <script src='https://www.google.com/recaptcha/api.js'></script>

    <!-- HTML5 shim and Respond.js for IE8 support of HTML5 elements and media queries -->
    <!-- WARNING: Respond.js doesn't work if you view the page via file:// -->
    <!--[if lt IE 9]>
      <script src="https://oss.maxcdn.com/html5shiv/3.7.2/html5shiv.min.js"></script>
      <script src="https://oss.maxcdn.com/respond/1.4.2/respond.min.js"></script>
    <![endif]-->
  </head>
  <body>
    <div class="container text-center">
      <h2>Installation Check</h2>

      <center><table class="table table-bordered" style="width:700px;">
        <tbody>
          <tr>
            <td>PHP Version</td>
            <td><?php echo $phpCheck; ?></td>
          </tr>
          <tr>
            <td>MySQL Connection</td>
            <td><?php echo $mysqlConn; ?></td>
          </tr>
          <tr>
            <td>PHP Extensions</td>
          </tr>
          <tr>
            <td>> cURL</td>
            <td><?php echo $curlCheck; ?></td>
          </tr>
          <tr>
            <td>> GMP</td>
            <td><?php echo $gmpCheck; ?></td>
          </tr>
        </tbody>
      </table></center><br /><br />

      <strong>Please remove this file (install.php) if everything is OK.</strong>
    </div>
  </body>
</html>