<?php
require_once("solvemedia.library.php");

function alert($type, $content){
	$alert = "<div class='alert alert-".$type."' role='alert'>".$content."</div>";
	return $alert;
}

function toSatoshi($amount){
	$satoshi = $amount * 100000000;
	return $satoshi;
}

function checkDirtyIp($ip, $apiKey){
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_TIMEOUT, "10");
		curl_setopt($ch, CURLOPT_URL, "http://v2.api.iphub.info/ip/".$ip);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array('X-Key: '.$apiKey));
		$response=curl_exec($ch);
		curl_close($ch);
	  $iphub = json_decode($response);
		if($iphub->block >= 1){
			return true;
		} else {
			return false;
		}
}

function findTimeAgo($past) {
    $secondsPerMinute = 60;
    $secondsPerHour = 3600;
    $secondsPerDay = 86400;
    $secondsPerMonth = 2592000;
    $secondsPerYear = 31104000;

    $past = $past;
    $now = time();

    $timeAgo = "";

    $timeDifference = $now - $past;

    if($timeDifference <= 29) {
      $timeAgo = "less than a minute";
    }

    else if($timeDifference > 29 && $timeDifference <= 89) {
      $timeAgo = "1 minute";
    }

    else if($timeDifference > 89 &&
      $timeDifference <= (($secondsPerMinute * 44) + 29)
    ) {
      $minutes = floor($timeDifference / $secondsPerMinute);
      $timeAgo = $minutes." minutes";
    }

    else if(
      $timeDifference > (($secondsPerMinute * 44) + 29)
      &&
      $timeDifference < (($secondsPerMinute * 89) + 29)
    ) {
      $timeAgo = "about 1 hour";
    }

    else if(
      $timeDifference > (
        ($secondsPerMinute * 89) +
        29
      )
      &&
      $timeDifference <= (
        ($secondsPerHour * 23) +
        ($secondsPerMinute * 59) +
        29
      )
    ) {
      $hours = floor($timeDifference / $secondsPerHour);
      $timeAgo = $hours." hours";
    }

    else if(
      $timeDifference > (
        ($secondsPerHour * 23) +
        ($secondsPerMinute * 59) +
        29
      )
      &&
      $timeDifference <= (
        ($secondsPerHour * 47) +
        ($secondsPerMinute * 59) +
        29
      )
    ) {
      $timeAgo = "1 day";
    }

    else if(
      $timeDifference > (
        ($secondsPerHour * 47) +
        ($secondsPerMinute * 59) +
        29
      )
      &&
      $timeDifference <= (
        ($secondsPerDay * 29) +
        ($secondsPerHour * 23) +
        ($secondsPerMinute * 59) +
        29
      )
    ) {
      $days = floor($timeDifference / $secondsPerDay);
      $timeAgo = $days." days";
    }

    else if(
      $timeDifference > (
        ($secondsPerDay * 29) +
        ($secondsPerHour * 23) +
        ($secondsPerMinute * 59) +
        29
      )
      &&
      $timeDifference <= (
        ($secondsPerDay * 59) +
        ($secondsPerHour * 23) +
        ($secondsPerMinute * 59) +
        29
      )
    ) {
      $timeAgo = "about 1 month";
    }

    else if(
      $timeDifference > (
        ($secondsPerDay * 59) + 
        ($secondsPerHour * 23) +
        ($secondsPerMinute * 59) +
        29
      )
      &&
      $timeDifference < $secondsPerYear
    ) {
      $months = round($timeDifference / $secondsPerMonth);

      if($months == 1) {
        $months = 2;
      }
      
      $timeAgo = $months." months";
    }

    else if(
      $timeDifference >= $secondsPerYear
      &&
      $timeDifference < ($secondsPerYear * 2)
    ) {
      $timeAgo = "about 1 year";
    }

    else {
      $years = floor($timeDifference / $secondsPerYear);
      $timeAgo = "over ".$years." years";
    }

    return $timeAgo." ago";
  }

function faucetInfo($mysqli){
  $jsonArray = array();

  $jsonArray['api_version'] = 1;
  $jsonArray['script'] = 1;
  $jsonArray['site_name'] = $mysqli->query("SELECT value FROM faucet_settings WHERE id = '1'")->fetch_assoc()['value'];
  $jsonArray['site_url'] = $Website_Url;
  $jsonArray['rewards']['minimum'] = $mysqli->query("SELECT value FROM faucet_settings WHERE id = '6'")->fetch_assoc()['value'];
  $jsonArray['rewards']['maximum'] = $mysqli->query("SELECT value FROM faucet_settings WHERE id = '7'")->fetch_assoc()['value'];
  $jsonArray['timer'] = $mysqli->query("SELECT value FROM faucet_settings WHERE id = '5'")->fetch_assoc()['value'];


  $claimAvail1 = $mysqli->query("SELECT value FROM faucet_settings WHERE id = '11'")->fetch_assoc()['value'];
  if($claimAvail1 == "yes")
      $jsonArray['claim_available'][0] = true;
    else
      $jsonArray['claim_available'][0] = false;

  $claimAvail2 = $mysqli->query("SELECT COUNT(id) FROM faucet_transactions WHERE type = 'Withdraw'")->fetch_row()[0];
  if($claimAvail2 >= 1)
      $jsonArray['claim_available'][1] = true;
    else
      $jsonArray['claim_available'][1] = false;


  $jsonArray['referral_commission'] = $mysqli->query("SELECT value FROM faucet_settings WHERE id = '15'")->fetch_assoc()['value'];

  $expressCryptoApiToken = $mysqli->query("SELECT value FROM faucet_settings WHERE id = '10'")->fetch_assoc()['value'];
  $expressCryptoUserToken = $mysqli->query("SELECT value FROM faucet_settings WHERE id = '18'")->fetch_assoc()['value'];
  $faucetpayApiToken = $mysqli->query("SELECT value FROM faucet_settings WHERE id = '19'")->fetch_assoc()['value'];
  $blockioApiKey = $mysqli->query("SELECT value FROM faucet_settings WHERE id = '20'")->fetch_assoc()['value'];
  $blockioPin = $mysqli->query("SELECT value FROM faucet_settings WHERE id = '21'")->fetch_assoc()['value'];

  if($expressCryptoApiToken AND $expressCryptoUserToken)
    $availableWithdrawalMethods .= "ec,";

  if($faucetpayApiToken)
    $availableWithdrawalMethods .= "fp,";

  if($blockioApiKey AND $blockioPin)
    $availableWithdrawalMethods .= "direct,";

  $jsonArray['withdrawal_methods'] = rtrim($availableWithdrawalMethods, ",");

  header('Content-Type: application/json');
  echo json_encode($jsonArray, JSON_PRETTY_PRINT);
  exit;
}
function CaptchaCheck($selectedCaptcha, $captchaData, $mysqli){
  if($selectedCaptcha == 1){
    $reCaptcha_privKey = $mysqli->query("SELECT * FROM faucet_settings WHERE id = '8' LIMIT 1")->fetch_assoc()['value'];
    if(!$reCaptcha_privKey){
      return false;
    } else {
      $recaptcha = new \ReCaptcha\ReCaptcha($reCaptcha_privKey);

      $respCaptcha = $recaptcha->verify($captchaData['g-recaptcha-response']);
      return $respCaptcha->isSuccess();
    }
  } else if($selectedCaptcha == 2){
    $sovleMediaVerificationKey = $mysqli->query("SELECT * FROM faucet_settings WHERE id = '3' LIMIT 1")->fetch_assoc()['value'];
    $sovleMediaAuthKey = $mysqli->query("SELECT * FROM faucet_settings WHERE id = '4' LIMIT 1")->fetch_assoc()['value'];
    if(!$sovleMediaVerificationKey AND !$sovleMediaAuthKey){
      return false;
    } else {
      $solvemedia_response = solvemedia_check_answer($sovleMediaVerificationKey,
                $_SERVER["REMOTE_ADDR"],
                $captchaData["adcopy_challenge"],
                $captchaData["adcopy_response"],
                $sovleMediaAuthKey);
      if(!$solvemedia_response->is_valid) {
        return false;
      } else {
        return true;
      }
    }
  }
}

function addressCheck($provider, $address){
  if(substr($address, 0, 3) == "EC-"){
    $returnData['provider'] = 2;
    if($provider['ec'] == true)
       $returnData['valid'] = true;
      else 
       $returnData['valid'] = false;
  } else if(strlen($address) >= 30 && strlen($address) <= 40){
    $returnData['provider'] = 1;
    if($provider['btc'] == true)
       $returnData['valid'] = true;
      else 
       $returnData['valid'] = false;
  } else {
    $returnData['valid'] = false;
  }
  return $returnData;
}
?>