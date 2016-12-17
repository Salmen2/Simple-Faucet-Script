<?php
    
$faucethub_lib_version = "b0.03";
    
/* 
FaucetHub PHP Library

Changelog

b0.02
Implemented a version of the suggestions mentioned here https://bitcointalk.org/index.php?topic=1692263.msg16985624#msg16985624
- Timeout support
- Error handling for timeouts and invalid responses
- Tweaks to the referral payout flag
- Removed silent retry on curl failure

b0.01
First version named
    
Credits to FaucetBOX.com for creating the original version this library is based on
*/

// Dummy class to allow drop-in compatibility with faucet-box library
class FaucetBOX extends FaucetHub {
    public function __construct($api_key, $currency = "BTC", $disable_curl = false, $verify_peer = true) {
        parent::__construct($api_key, $currency, $disable_curl, $verify_peer);
    }
}

class FaucetHub
{
    protected $api_key;
    protected $currency;
    protected $timeout;
    public $last_status = null;
    protected $api_base = "https://faucethub.io/api/v1/";

    public function __construct($api_key, $currency = "BTC", $disable_curl = false, $verify_peer = true, $timeout = null) {
        $this->api_key = $api_key;
        $this->currency = $currency;
        $this->disable_curl = $disable_curl;
        $this->verify_peer = $verify_peer;
        $this->curl_warning = false;
        $this->setTimeout($timeout);
    }

    public function setTimeout($timeout) {
        if($timeout === null) {
            $socket_timeout = ini_get('default_socket_timeout'); 
            $script_timeout = ini_get('max_execution_time');
            $timeout = min($script_timeout / 2, $socket_timeout);
        }
        $this->timeout = $timeout;
     }

    public function __execPHP($method, $params = array()) {
        $params = array_merge($params, array("api_key" => $this->api_key, "currency" => $this->currency));
        $opts = array(
            "http" => array(
                "method" => "POST",
                "header" => "Content-type: application/x-www-form-urlencoded\r\n",
                "content" => http_build_query($params),
                "timeout" => $this->timeout,
            ),
            "ssl" => array(
                "verify_peer" => $this->verify_peer
            )
        );
        $ctx = stream_context_create($opts);
        $fp = fopen($this->api_base . $method, 'rb', null, $ctx);
        
        if (!$fp) {
            return json_encode(array(
                'status' => 503,
                'message' => 'Connection to FaucetHub failed, please try again later',
            ), TRUE);
        }
        
        $response = stream_get_contents($fp);
        if($response && !$this->disable_curl) {
            $this->curl_warning = true;
        }
        fclose($fp);
        return $response;
    }

    public function __exec($method, $params = array()) {
        $this->last_status = null;
        if($this->disable_curl) {
            $response = $this->__execPHP($method, $params);
        } else {
            $response = $this->__execCURL($method, $params);
        }
        $response = json_decode($response, true);
        if($response) {
            $this->last_status = $response['status'];
        } else {
            $this->last_status = null;
            $response = array(
                'status' => 502,
                'message' => 'Invalid response',
            );
        }
        return $response;
    }

    public function __execCURL($method, $params = array()) {
        $params = array_merge($params, array("api_key" => $this->api_key, "currency" => $this->currency));

        $ch = curl_init($this->api_base . $method);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $params);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, $this->verify_peer);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, (int)$this->timeout);

        $response = curl_exec($ch);
        if(!$response) {
            //$response = $this->__execPHP($method, $params); // disabled the exec fallback when using curl
            return json_encode(array(
                'status' => 504,
                'message' => 'Connection error',
            ), TRUE);
        }
        curl_close($ch);

        return $response;
    }

    public function send($to, $amount, $referral = false, $ip_address = "") {
        $referral = ($referral === true) ? 'true' : 'false';
        
        $r = $this->__exec("send", array("to" => $to, "amount" => $amount, "referral" => $referral, "ip_address" => $ip_address));
        if (array_key_exists("status", $r) && $r["status"] == 200) {
            return array(
                'success' => true,
                'message' => 'Payment sent to your address using FaucetHub.io',
                'html' => '<div class="alert alert-success">' . htmlspecialchars($amount) . ' satoshi was sent to <a target="_blank" href="https://faucethub.io/balance/' . rawurlencode($to) . '">your account at FaucetHub.io</a>.</div>',
                'html_coin' => '<div class="alert alert-success">' . htmlspecialchars(rtrim(rtrim(sprintf("%.8f", $amount/100000000), '0'), '.')) . ' '.$this->currency.' was sent to <a target="_blank" href="https://faucethub.io/balance/' . rawurlencode($to) . '">your account at FaucetHub.io</a>.</div>',
                'balance' => $r["balance"],
                'balance_bitcoin' => $r["balance_bitcoin"],
                'response' => json_encode($r)
            );
        }
        
        // Let the user know they need an account to claim
        if (array_key_exists("status", $r) && $r["status"] == 456) {
            return array(
                'success' => false,
                'message' => $r['message'],
                'html' => '<div class="alert alert-danger">Before you can receive payments at FaucetHub.io with this address you must link it to an account. <a href="http://faucethub.io/signup" target="_blank">Create an account at FaucetHub.io</a> and link your address, then come back and claim again.</div>',
                'response' => json_encode($r)
            );
        }

        if (array_key_exists("message", $r)) {
            return array(
                'success' => false,
                'message' => $r["message"],
                'html' => '<div class="alert alert-danger">' . htmlspecialchars($r["message"]) . '</div>',
                'response' => json_encode($r)
            );
        }

        return array(
            'success' => false,
            'message' => 'Unknown error.',
            'html' => '<div class="alert alert-danger">Unknown error.</div>',
            'response' => json_encode($r)
        );
    }

    public function sendReferralEarnings($to, $amount, $ip_address = "") {
        return $this->send($to, $amount, true, $ip_address);
    }

    public function getPayouts($count) {
        $r = $this->__exec("payouts", array("count" => $count) );
        return $r;
    }

    public function getCurrencies() {
        $r = $this->__exec("currencies");
        return $r['currencies'];
    }

    public function getBalance() {
        $r = $this->__exec("balance");
        return $r;
    }
}
