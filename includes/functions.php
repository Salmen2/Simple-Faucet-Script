<?php
function alert($type, $content){
	$alert = "<div class='alert alert-".$type."' role='alert'>".$content."</div>";
	return $alert;
}

function toSatoshi($amount){
	$satoshi = $amount * 100000000;
	return $satoshi;
}

function CaptchaCheck($response)
  {
  global $mysqli;
  $reCaptcha_privKey = $mysqli->query("SELECT * FROM faucet_settings WHERE id = '8' LIMIT 1")->fetch_assoc()['value'];
  $Captcha_url = 'https://www.google.com/recaptcha/api/siteverify';
  $Captcha_data = array('secret' => $reCaptcha_privKey, 'response' => $response);

  $Captcha_options = array(
     'http' => array(
              'header'  => "Content-type: application/x-www-form-urlencoded\r\n",
              'method'  => 'POST',
              'content' => http_build_query($Captcha_data),
      ),
  );
  $Captcha_context  = stream_context_create($Captcha_options);
  $Captcha_result = file_get_contents($Captcha_url, false, $Captcha_context);
  return $Captcha_result;
}

function checkDirtyIp($ip){
	
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_TIMEOUT, "10");
		curl_setopt($ch, CURLOPT_URL, "http://v1.nastyhosts.com/".$ip);
		$response=curl_exec($ch);
	
		curl_close($ch);
	  $nastyArray = json_decode($response);
		if($nastyArray->suggestion == "deny"){
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
?>