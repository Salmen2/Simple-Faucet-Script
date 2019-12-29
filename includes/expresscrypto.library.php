<?php
    $current_ver="2.0";
    
    /* 
        The ExpressCryptoV2 class exploses public methods
    
        This class based on FaucetInBox lib https://github.com/coinables/Bitcoin-Faucet-Dice-Faucet-Box/blob/master/faucetbox/faucetbox.php
        
    */
    
    class ExpressCrypto{
        
        //params
        protected $api_key;
        protected $user_token;
        protected $ip_user; //please send to us the ip of user that will help us tracking fraud actions.
        protected $timeout; //by default we use configuration from your php.ini | however, you could customize the timeout depend of your needs (max 90s).
        protected $api_base = "https://expresscrypto.io/public-api/v2/";
        
        public $last_status = null;
        
        
        //methods
        public function __construct($api_key, $user_token, $ip_user = "", $disable_curl = false, $verify_peer = true, $timeout = null) {
            $this->api_key = $api_key;
            $this->user_token = $user_token;
            $this->ip_user = $ip_user;
            $this->disable_curl = $disable_curl;
            $this->verify_peer = $verify_peer;
            $this->curl_warning = false;
            $this->setTimeout($timeout);
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
                $response = array("status" => 520, "message" => "Invalid response");
            }
            return $response;
        }
        
        public function __execPHP($method, $params = array()) {
            $params = array_merge($params, array("api_key" => $this->api_key, "user_token" => $this->user_token, "ip_user" => $this->ip_user));
            
            $opts = array(
                "http" => array(
                    "method" => "POST",
                    "header" => "Content-type: application/x-www-form-urlencoded\r\n",
                    "content" => http_build_query($params),
                    "timeout" => $this->timeout
                ),
                "ssl" => array(
                    "verify_peer" => $this->verify_peer
                )
            );
            
            $ctx = stream_context_create($opts);
            $fp = fopen($this->api_base . $method, 'rb', null, $ctx);
            
            if(!$fp){
                return json_encode(array("status" => 521, "message" => "Connection refused, please try again later..."));
            }else{
                $response = stream_get_contents($fp);
                if($response && !$this->disable_curl) {
                    $this->curl_warning = true;
                }
                fclose($fp);
                return $response;
            }
        }
        
        public function __execCURL($method, $params = array()) {
            $params = array_merge($params, array("api_key" => $this->api_key, "user_token" => $this->user_token, "ip_user" => $this->ip_user));
            
            $headers = array( 
                "Content-type: application/json;charset=\"utf-8\"", 
                "Accept: application/json"
            ); 
       
            $ch = curl_init($this->api_base . $method);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $params);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, $this->verify_peer);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            //curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
            curl_setopt($ch, CURLOPT_TIMEOUT, (int)$this->timeout);
    
            $response = curl_exec($ch);
            if(!$response) {
                return json_encode(array("status" => 524, "message" => "Connection error"));
            }
            curl_close($ch);
    
            return $response;
        }
        
        public function setTimeout($timeout) {
            if($timeout === null) {
                $socket_timeout = ini_get('default_socket_timeout'); 
                $script_timeout = ini_get('max_execution_time');
                $timeout = min($script_timeout / 2, $socket_timeout);
            }
            $this->timeout = $timeout;
        }
        
        public function sendPayment($userId, $currency, $amount){
    		return $this->__exec("sendPayment", array("userId" => $userId, "currency" => $currency, "amount" => $amount, "payment_type" => "Normal"));
    	}
    	
    	public function sendReferralCommission($userId, $currency, $amount){
    		return $this->__exec("sendReferralCommission", array("userId" => $userId, "currency" => $currency, "amount" => $amount, "payment_type" => "Referral"));
    	}
    
        public function checkUserHash($userId){
    		return $this->__exec("checkUserHash", array("userId" => $userId));
    	}
    	
    	public function getBalance($currency){
    		return $this->__exec("getBalance", array("currency" => $currency));
    	}
    	
    	public function getAvailableCurrencies(){
    		return $this->__exec("getAvailableCurrencies");
    	}
    	
    	public function getListOfSites(){
    		return $this->__exec("getListOfSites");
    	}
    }
    