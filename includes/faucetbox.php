<?php

class FaucetBOX
{
    protected $api_key;
    protected $currency;
    public $last_status = null;
    protected $api_base = "https://faucetbox.com/api/v1/";

    public function __construct($api_key, $currency = "BTC", $disable_curl = false, $verify_peer = true) {
        $this->api_key = $api_key;
        $this->currency = $currency;
        $this->disable_curl = $disable_curl;
        $this->verify_peer = $verify_peer;
        $this->curl_warning = false;
    }

    public function __execPHP($method, $params = array()) {
        $params = array_merge($params, array("api_key" => $this->api_key, "currency" => $this->currency));
        $opts = array(
            "http" => array(
                "method" => "POST",
                "header" => "Content-type: application/x-www-form-urlencoded\r\n",
                "content" => http_build_query($params)
            ),
            "ssl" => array(
                "verify_peer" => $this->verify_peer
            )
        );
        $ctx = stream_context_create($opts);
        $fp = fopen($this->api_base . $method, 'rb', null, $ctx);
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

        $response = curl_exec($ch);
        if(!$response) {
            $response = $this->__execPHP($method, $params);
        }
        curl_close($ch);

        return $response;
    }

    public function send($to, $amount, $referral = "false") {
        $r = $this->__exec("send", array("to" => $to, "amount" => $amount, "referral" => $referral));
        if (array_key_exists("status", $r) && $r["status"] == 200) {
            return array(
                'success' => true,
                'message' => 'Payment sent to your address using FaucetBOX.com',
                'html' => '<div class="alert alert-success">' . htmlspecialchars($amount) . ' satoshi was sent to <a target="_blank" href="https://faucetbox.com/check/' . rawurlencode($to) . '">your FaucetBOX.com address</a>.</div>',
                'html_coin' => '<div class="alert alert-success">' . htmlspecialchars(rtrim(rtrim(sprintf("%.8f", $amount/100000000), '0'), '.')) . ' '.$this->currency.' was sent to <a target="_blank" href="https://faucetbox.com/check/' . rawurlencode($to) . '">your FaucetBOX.com address</a>.</div>',
                'balance' => $r["balance"],
                'balance_bitcoin' => $r["balance_bitcoin"],
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

    public function sendReferralEarnings($to, $amount) {
        return $this->send($to, $amount, "true");
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
