<?php

/**
 *
 * OpenSSL functionality adapted from Jan Lindemann's BitcoinECDSA.php
 * @author Atif Nazir
 */

if (!extension_loaded('gmp')) {
    throw new \Exception('GMP extension seems not to be installed');
}

if (!extension_loaded('curl')) {
    throw new \Exception('cURL extension seems not to be installed');
}

class BlockIo
{
    
    /**
     * Validate the given API key on instantiation
     */
     
    private $api_key;
    private $pin = "";
    private $encryption_key = "";
    private $version;
    private $withdrawal_methods;
    private $sweep_methods;

    public function __construct($api_key, $pin, $api_version = 2)
    { // the constructor
      $this->api_key = $api_key;
      $this->pin = $pin;
      $this->version = $api_version;

      $this->withdrawal_methods = array("withdraw", "withdraw_from_user", "withdraw_from_users", "withdraw_from_label", "withdraw_from_labels", "withdraw_from_address", "withdraw_from_addresses");

      $this->sweep_methods = array("sweep_from_address");
    }

    public function __call($name, array $args)
    { // method_missing for PHP

        $response = "";
	
	if (empty($args)) { $args = array(); }
	else { $args = $args[0]; }

        if ( in_array($name, $this->withdrawal_methods) )
	{ // it is a withdrawal method, let's do the client side signing bit
		$response = $this->_withdraw($name, $args);
	}
	elseif (in_array($name, $this->sweep_methods))
	{ // it is a sweep method
	     	$response = $this->_sweep($name, $args);
	}
	else
	{ // it is not a withdrawal method, let it go to Block.io

		$response = $this->_request($name, $args);
	}

	return $response;

    }

    /**
     * cURL GET request driver
     */
    private function _request($path, $args = array(), $method = 'POST')
    {
        // Generate cURL URL
        $url =  str_replace("API_CALL",$path,"https://block.io/api/v" . $this->version . "/API_CALL/?api_key=") . $this->api_key;
	$addedData = "";

	foreach ($args as $pkey => $pval)
	{

		if (strlen($addedData) > 0) { $addedData .= '&'; }

		$addedData .= $pkey . "=" . $pval;
	}

        // Initiate cURL and set headers/options
        $ch  = curl_init();
        
        // If we run windows, make sure the needed pem file is used
        if(strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
        	$pemfile = dirname(realpath(__FILE__)) . DIRECTORY_SEPARATOR . 'cacert.pem';
        	if(!file_exists($pemfile)) {
        		throw new Exception("Needed .pem file not found. Please download the .pem file at http://curl.haxx.se/ca/cacert.pem and save it as " . $pemfile);
        	}        	
        	curl_setopt($ch, CURLOPT_CAINFO, $pemfile);
        }

	// it's a GET method
	if ($method == 'GET') { $url .= '&' . $addedData; }

	curl_setopt($ch, CURLOPT_SSLVERSION, CURL_SSLVERSION_TLSv1_2); // enforce use of TLSv1.2
        curl_setopt($ch, CURLOPT_URL, $url);

	if ($method == 'POST')
	{ // this was a POST method
		curl_setopt($ch, CURLOPT_POST, 1);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $addedData);
	}

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        // Execute the cURL request
        $result = curl_exec($ch);
        curl_close($ch);

	$json_result = json_decode($result);

	if ($json_result->status != 'success') { throw new Exception('Failed: ' . $json_result->data->error_message); }

        // Spit back the response object or fail
        return $result ? $json_result : false;        
    }


    private function _withdraw($name, $args = array())
    { // withdraw method to be called by __call

         unset ($args['pin']); // make sure no inadvertent passing of pin occurs

	 $response = $this->_request($name,$args);

	 if ($response->status == 'success' && array_key_exists('reference_id', $response->data))
	 { // we have signatures to append
	 
	   // get our encryption key ready
	   if (strlen($this->encryption_key) == 0)
	   {
		$this->encryption_key = $this->pinToAesKey($this->pin);
	   }

	   // decrypt the data
	   $passphrase = $this->decrypt($response->data->encrypted_passphrase->passphrase, $this->encryption_key);
	   
	   // extract the key
	   $key = $this->initKey();
	   $key->fromPassphrase($passphrase);

	   // is this the right public key?
	   if ($key->getPublicKey() != $response->data->encrypted_passphrase->signer_public_key) { throw new Exception('Fail: Invalid Secret PIN provided.'); }

	   // grab inputs
	   $inputs = &$response->data->inputs;

	   // data to sign
	   foreach ($inputs as &$curInput)
	   { // for each input

		$data_to_sign = &$curInput->data_to_sign;
		
		foreach ($curInput->signers as &$signer)
		{ // for each signer

		     if ($key->getPublicKey() == $signer->signer_public_key)
		     {
			$signer->signed_data = $key->signHash($data_to_sign);
		     }		

		}
		
	   }

	   $json_string = json_encode($response->data);

	   // let's send the signed data back to Block.io
	   $response = $this->_request('sign_and_finalize_withdrawal', array('signature_data' => $json_string));
	   
	 }

	 return $response;
    }

    private function _sweep($name, $args = array())
    { // sweep method to be called by __call

      	 $key = $this->initKey()->fromWif($args['private_key']);

	 unset($args['private_key']); // remove the key so we don't send it to anyone outside

	 $args['public_key'] = $key->getPublicKey();
	 
	 $response = $this->_request($name,$args);

	 if ($response->status == 'success' && array_key_exists('reference_id', $response->data))
	 { // we have signatures to append

	   // grab inputs
	   $inputs = &$response->data->inputs;

	   // data to sign
	   foreach ($inputs as &$curInput)
	   { // for each input

		$data_to_sign = &$curInput->data_to_sign;
		
		foreach ($curInput->signers as &$signer)
		{ // for each signer

		     if ($key->getPublicKey() == $signer->signer_public_key)
		     {
			$signer->signed_data = $key->signHash($data_to_sign);
		     }		

		}
		
	   }

	   $json_string = json_encode($response->data);

	   // let's send the signed data back to Block.io
	   $response = $this->_request('sign_and_finalize_sweep', array('signature_data' => $json_string));
	   
	 }

	 return $response;
    }

    public function initKey()
    { // grants a new Key object
	return new BlockKey();
    }

    private function pbkdf2($password, $key_length, $salt = "", $rounds = 1024, $a = 'sha256') 
    { // PBKDF2 function adaptation for Block.io

      // Derived key 
      $dk = '';
 
      // Create key 
      for ($block=1; $block<=$key_length; $block++) 
      { 
      	// Initial hash for this block 
    	$ib = $h = hash_hmac($a, $salt . pack('N', $block), $password, true); 
 
	// Perform block iterations 
    	for ($i=1; $i<$rounds; $i++) 
    	{ 
      	  // XOR each iteration
      	  $ib ^= ($h = hash_hmac($a, $h, $password, true)); 
    	} 
 
	// Append iterated block 
    	$dk .= $ib;
      } 
 
      // Return derived key of correct length 
      $key = substr($dk, 0, $key_length);
      return bin2hex($key);
    }


    public function encrypt($data, $key)
    { 
      # encrypt using aes256ecb
      # data is string, key is hex string (pbkdf2 with 2,048 iterations)

      $key = hex2bin($key); // convert the hex into binary

      $padding = 16 - (strlen($data) % 16);
      $data .= str_repeat(chr($padding), $padding);

      $ciphertext = openssl_encrypt($data, 'AES-256-ECB', $key, true);

      $ciphertext_base64 = base64_encode($ciphertext);

      return $ciphertext_base64;
    }

    
    public function pinToAesKey($pin)
    { // converts the given Secret PIN to an Encryption Key

    $enc_key_16 = $this->pbkdf2($pin,16);
    $enc_key_32 = $this->pbkdf2($enc_key_16,32);

    return $enc_key_32;
    }   

    public function decrypt($b64ciphertext, $key)
    {
        # data must be in base64 string, $key is binary of hashed pincode
    
        $key = hex2bin($key); // convert the hex into binary

	$ciphertext_dec = base64_decode($b64ciphertext);
    
	$data_dec = openssl_decrypt($ciphertext_dec, 'AES-256-ECB', $key, OPENSSL_RAW_DATA, NULL);

	return $data_dec; // plain text
    
    }

}

class BlockKey
{

    public $k;
    public $a;
    public $b;
    public $p;
    public $n;
    public $G;
    public $networkPrefix;
    public $c = true; //compressed or not

    public function __construct()
    {
        $this->a = gmp_init('0', 10);
        $this->b = gmp_init('7', 10);
        $this->p = gmp_init('FFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFEFFFFFC2F', 16);
        $this->n = gmp_init('FFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFEBAAEDCE6AF48A03BBFD25E8CD0364141', 16);

        $this->G = array('x' => gmp_init('55066263022277343669578718895168534326250603453777594175500187360389116729240'),
                         'y' => gmp_init('32670510020758816978083085130507043184471273380659243275938904335757337482424'));

        $this->networkPrefix = '00';
    }
    
    public function deterministicGenerateK($message, $key)
    { // key in hex, message as it is
    // RFC6979
    
	$hash = $message;

	$k = "0000000000000000000000000000000000000000000000000000000000000000";
	$v = "0101010101010101010101010101010101010101010101010101010101010101";

	// step D
	$k = hash_hmac('sha256', hex2bin($v) . hex2bin("00") . hex2bin($key) . hex2bin($hash), hex2bin($k));

	// step E
	$v = hash_hmac('sha256', hex2bin($v), hex2bin($k));

	// step F
	$k = hash_hmac('sha256', hex2bin($v) . hex2bin("01") . hex2bin($key) . hex2bin($hash), hex2bin($k));

	// step G
	$v = hash_hmac('sha256', hex2bin($v), hex2bin($k));

	// H2b
	$h2b = hash_hmac('sha256', hex2bin($v), hex2bin($k));

	$tNum = gmp_init($h2b,16);

	// step H3
	while (gmp_sign($tNum) <= 0 || gmp_cmp($tNum, $this->n) >= 0)
	{
		$k = hash_hmac('sha256', hex2bin($v) . hex2bin("00"), hex2bin($k));
		$v = hash_hmac('sha256', hex2bin($v), hex2bin($k));

		$tNum = gmp_init($v, 16);
	}

	return gmp_strval($tNum,16);
    }   


    /***
     * Convert a number to a compact Int
     * taken from https://github.com/scintill/php-bitcoin-signature-routines/blob/master/verifymessage.php
     *
     * @param $i
     * @return string
     * @throws \Exception
     */
    public function numToVarIntString($i) {
        if ($i < 0xfd) {
            return chr($i);
        } else if ($i <= 0xffff) {
            return pack('Cv', 0xfd, $i);
        } else if ($i <= 0xffffffff) {
            return pack('CV', 0xfe, $i);
        } else {
            throw new \Exception('int too large');
        }
    }

    /***
     * Set the network prefix, '00' = main network, '6f' = test network.
     *
     * @param String Hex $prefix
     */
    public function setNetworkPrefix($prefix)
    {
        $this->networkPrefix = $prefix;
    }

    /**
     * Returns the current network prefix, '00' = main network, '6f' = test network.
     *
     * @return String Hex
     */
    public function getNetworkPrefix()
    {
        return $this->networkPrefix;
    }

    /***
     * Permutation table used for Base58 encoding and decoding.
     *
     * @param $char
     * @param bool $reverse
     * @return null
     */
    public function base58_permutation($char, $reverse = false)
    {
        $table = array('1','2','3','4','5','6','7','8','9','A','B','C','D',
                       'E','F','G','H','J','K','L','M','N','P','Q','R','S','T','U','V','W',
                       'X','Y','Z','a','b','c','d','e','f','g','h','i','j','k','m','n','o',
                       'p','q','r','s','t','u','v','w','x','y','z'
                 );

        if($reverse)
        {
            $reversedTable = array();
            foreach($table as $key => $element)
            {
                $reversedTable[$element] = $key;
            }

            if(isset($reversedTable[$char]))
                return $reversedTable[$char];
            else
                return null;
        }

        if(isset($table[$char]))
            return $table[$char];
        else
            return null;
    }

    /***
     * Bitcoin standard 256 bit hash function : double sha256
     *
     * @param $data
     * @return string
     */
    public function hash256($data)
    {
        return hash('sha256', hex2bin(hash('sha256', $data)));
    }

    /***
     * encode a hexadecimal string in Base58.
     *
     * @param String Hex $data
     * @param bool $littleEndian
     * @return String Base58
     * @throws \Exception
     */
    public function base58_encode($data, $littleEndian = true)
    {
        $res = '';
        $dataIntVal = gmp_init($data, 16);
        while(gmp_cmp($dataIntVal, gmp_init(0, 10)) > 0)
        {
            $qr = gmp_div_qr($dataIntVal, gmp_init(58, 10));
            $dataIntVal = $qr[0];
            $reminder = gmp_strval($qr[1]);
            if(!$this->base58_permutation($reminder))
            {
                throw new \Exception('Something went wrong during base58 encoding');
            }
            $res .= $this->base58_permutation($reminder);
        }

        //get number of leading zeros
        $leading = '';
        $i=0;
        while(substr($data, $i, 1) == '0')
        {
            if($i!= 0 && $i%2)
            {
                $leading .= '1';
            }
            $i++;
        }

        if($littleEndian)
            return strrev($res . $leading);
        else
            return $res.$leading;
    }

    /***
     * Decode a Base58 encoded string and returns it's value as a hexadecimal string
     *
     * @param $encodedData
     * @param bool $littleEndian
     * @return String Hex
     */
    public function base58_decode($encodedData, $littleEndian = true)
    {
        $res = gmp_init(0, 10);
        $length = strlen($encodedData);
        if($littleEndian)
        {
            $encodedData = strrev($encodedData);
        }

        for($i = $length - 1; $i >= 0; $i--)
        {
            $res = gmp_add(
                           gmp_mul(
                                   $res,
                                   gmp_init(58, 10)
                           ),
                           $this->base58_permutation(substr($encodedData, $i, 1), true)
                   );
        }

        $res = gmp_strval($res, 16);
        $i = $length - 1;
        while(substr($encodedData, $i, 1) == '1')
        {
            $res = '00' . $res;
            $i--;
        }

        if(strlen($res)%2 != 0)
        {
            $res = '0' . $res;
        }

        return $res;
    }

    public function toWif($network = "BTC")
    {
	return $this->getWif($network);
    }

    /***
     * returns the private key under the Wallet Import Format
     *
     * @return String Base58
     * @throws \Exception
     */
    public function getWif($network = "BTC")
    {
        if(!isset($this->k))
        {
            throw new \Exception('No Private Key was defined');
        }

	$privKeyVersions = array();
	$privKeyVersion["BTC"] = '80';
	$privKeyVersion["BTCTEST"] = 'ef';
	$privKeyVersion["DOGE"] = '9e';
	$privKeyVersion["DOGETEST"] = 'f1';
	$privKeyVersion["LTC"] = 'b0';
	$privKeyVersion["LTCTEST"] = 'ef';

        $k              = $this->k;
        $secretKey      = $privKeyVersion[$network] . $k;
	if ($this->c) { $secretKey .= '01'; } // set the compression flag if we need it
        $firstSha256    = hash('sha256', hex2bin($secretKey));
        $secondSha256   = hash('sha256', hex2bin($firstSha256));
        $secretKey     .= substr($secondSha256, 0, 8);

        return $this->base58_encode($secretKey);
    }

    /***
     * Computes the result of a point addition and returns the resulting point as an Array.
     *
     * @param Array $pt
     * @return Array Point
     * @throws \Exception
     */
    public function doublePoint(Array $pt)
    {
        $a = $this->a;
        $p = $this->p;

        $gcd = gmp_strval(gmp_gcd(gmp_mod(gmp_mul(gmp_init(2, 10), $pt['y']), $p),$p));
        if($gcd != '1')
        {
            throw new \Exception('This library doesn\'t yet supports point at infinity. See https://github.com/BitcoinPHP/BitcoinECDSA.php/issues/9');
        }

        // SLOPE = (3 * ptX^2 + a )/( 2*ptY )
        // Equals (3 * ptX^2 + a ) * ( 2*ptY )^-1
        $slope = gmp_mod(
                         gmp_mul(
                                 gmp_invert(
                                            gmp_mod(
                                                    gmp_mul(
                                                            gmp_init(2, 10),
                                                            $pt['y']
                                                    ),
                                                    $p
                                            ),
                                            $p
                                 ),
                                 gmp_add(
                                         gmp_mul(
                                                 gmp_init(3, 10),
                                                 gmp_pow($pt['x'], 2)
                                         ),
                                         $a
                                 )
                         ),
                         $p
                );

        // nPtX = slope^2 - 2 * ptX
        // Equals slope^2 - ptX - ptX
        $nPt = array();
        $nPt['x'] = gmp_mod(
                            gmp_sub(
                                    gmp_sub(
                                            gmp_pow($slope, 2),
                                            $pt['x']
                                    ),
                                    $pt['x']
                            ),
                            $p
                    );

        // nPtY = slope * (ptX - nPtx) - ptY
        $nPt['y'] = gmp_mod(
                            gmp_sub(
                                    gmp_mul(
                                            $slope,
                                            gmp_sub(
                                                    $pt['x'],
                                                    $nPt['x']
                                            )
                                    ),
                                    $pt['y']
                            ),
                            $p
                    );

        return $nPt;
    }

    /***
     * Computes the result of a point addition and returns the resulting point as an Array.
     *
     * @param Array $pt1
     * @param Array $pt2
     * @return Array Point
     * @throws \Exception
     */
    public function addPoints(Array $pt1, Array $pt2)
    {
        $p = $this->p;
        if(gmp_cmp($pt1['x'], $pt2['x']) == 0  && gmp_cmp($pt1['y'], $pt2['y']) == 0) //if identical
        {
            return $this->doublePoint($pt1);
        }

        $gcd = gmp_strval(gmp_gcd(gmp_sub($pt1['x'], $pt2['x']), $p));
        if($gcd != '1')
        {
            throw new \Exception('This library doesn\'t yet supports point at infinity. See https://github.com/BitcoinPHP/BitcoinECDSA.php/issues/9');
        }

        // SLOPE = (pt1Y - pt2Y)/( pt1X - pt2X )
        // Equals (pt1Y - pt2Y) * ( pt1X - pt2X )^-1
        $slope      = gmp_mod(
                              gmp_mul(
                                      gmp_sub(
                                              $pt1['y'],
                                              $pt2['y']
                                      ),
                                      gmp_invert(
                                                 gmp_sub(
                                                         $pt1['x'],
                                                         $pt2['x']
                                                 ),
                                                 $p
                                      )
                              ),
                              $p
                      );

        // nPtX = slope^2 - ptX1 - ptX2
        $nPt = array();
        $nPt['x']   = gmp_mod(
                              gmp_sub(
                                      gmp_sub(
                                              gmp_pow($slope, 2),
                                              $pt1['x']
                                      ),
                                      $pt2['x']
                              ),
                              $p
                      );

        // nPtX = slope * (ptX1 - nPtX) - ptY1
        $nPt['y']   = gmp_mod(
                              gmp_sub(
                                      gmp_mul(
                                              $slope,
                                              gmp_sub(
                                                      $pt1['x'],
                                                      $nPt['x']
                                              )
                                      ),
                                      $pt1['y']
                              ),
                              $p
                      );

        return $nPt;
    }

    /***
     * Computes the result of a point multiplication and returns the resulting point as an Array.
     *
     * @param String Hex $k
     * @param Array $pG
     * @param $base
     * @throws \Exception
     * @return Array Point
     */
    public function mulPoint($k, Array $pG, $base = null)
    {
        //in order to calculate k*G
        if($base == 16 || $base == null || is_resource($base))
            $k = gmp_init($k, 16);
        if($base == 10)
            $k = gmp_init($k, 10);
        $kBin = gmp_strval($k, 2);

        $lastPoint = $pG;
        for($i = 1; $i < strlen($kBin); $i++)
        {
            if(substr($kBin, $i, 1) == 1 )
            {
                $dPt = $this->doublePoint($lastPoint);
                $lastPoint = $this->addPoints($dPt, $pG);
            }
            else
            {
                $lastPoint = $this->doublePoint($lastPoint);
            }
        }
        if(!$this->validatePoint(gmp_strval($lastPoint['x'], 16), gmp_strval($lastPoint['y'], 16)))
            throw new \Exception('The resulting point is not on the curve.');
        return $lastPoint;
    }

    /***
     * Calculates the square root of $a mod p and returns the 2 solutions as an array.
     *
     * @param $a
     * @return array|null
     * @throws \Exception
     */
    public function sqrt($a)
    {
        $p = $this->p;

        if(gmp_legendre($a, $p) != 1)
        {
            //no result
            return null;
        }

        if(gmp_strval(gmp_mod($p, gmp_init(4, 10)), 10) == 3)
        {
            $sqrt1 = gmp_powm(
                            $a,
                            gmp_div_q(
                                gmp_add($p, gmp_init(1, 10)),
                                gmp_init(4, 10)
                            ),
                            $p
                    );
            // there are always 2 results for a square root
            // In an infinite number field you have -2^2 = 2^2 = 4
            // In a finite number field you have a^2 = (p-a)^2
            $sqrt2 = gmp_mod(gmp_sub($p, $sqrt1), $p);
            return array($sqrt1, $sqrt2);
        }
        else
        {
            throw new \Exception('P % 4 != 3 , this isn\'t supported yet.');
        }
    }

    /***
     * Calculate the Y coordinates for a given X coordinate.
     *
     * @param $x
     * @param null $derEvenOrOddCode
     * @return array|null|String
     */
    public function calculateYWithX($x, $derEvenOrOddCode = null)
    {
        $a  = $this->a;
        $b  = $this->b;
        $p  = $this->p;

        $x  = gmp_init($x, 16);
        $y2 = gmp_mod(
                      gmp_add(
                              gmp_add(
                                      gmp_powm($x, gmp_init(3, 10), $p),
                                      gmp_mul($a, $x)
                              ),
                              $b
                      ),
                      $p
              );

        $y = $this->sqrt($y2);

        if(!$y) //if there is no result
        {
            return null;
        }

        if(!$derEvenOrOddCode)
        {
            return $y;
        }

        else if($derEvenOrOddCode == '02') // even
        {
            $resY = null;
            if(false == gmp_strval(gmp_mod($y[0], gmp_init(2, 10)), 10))
                $resY = gmp_strval($y[0], 16);
            if(false == gmp_strval(gmp_mod($y[1], gmp_init(2, 10)), 10))
                $resY = gmp_strval($y[1], 16);
            if($resY)
            {
                while(strlen($resY) < 64)
                {
                    $resY = '0' . $resY;
                }
            }
            return $resY;
        }
        else if($derEvenOrOddCode == '03') // odd
        {
            $resY = null;
            if(true == gmp_strval(gmp_mod($y[0], gmp_init(2, 10)), 10))
                $resY = gmp_strval($y[0], 16);
            if(true == gmp_strval(gmp_mod($y[1], gmp_init(2, 10)), 10))
                $resY = gmp_strval($y[1], 16);
            if($resY)
            {
                while(strlen($resY) < 64)
                {
                    $resY = '0' . $resY;
                }
            }
            return $resY;
        }

        return null;
    }

    /***
     * returns the public key coordinates as an array.
     *
     * @param $derPubKey
     * @return array
     * @throws \Exception
     */
    public function getPubKeyPointsWithDerPubKey($derPubKey)
    {
        if(substr($derPubKey, 0, 2) == '04' && strlen($derPubKey) == 130)
        {
            //uncompressed der encoded public key
            $x = substr($derPubKey, 2, 64);
            $y = substr($derPubKey, 66, 64);
            return array('x' => $x, 'y' => $y);
        }
        else if((substr($derPubKey, 0, 2) == '02' || substr($derPubKey, 0, 2) == '03') && strlen($derPubKey) == 66)
        {
            //compressed der encoded public key
            $x = substr($derPubKey, 2, 64);
            $y = $this->calculateYWithX($x, substr($derPubKey, 0, 2));
            return array('x' => $x, 'y' => $y);
        }
        else
        {
            throw new \Exception('Invalid derPubKey format : ' . $derPubKey);
        }
    }


    public function getDerPubKeyWithPubKeyPoints($pubKey, $compressed = true)
    {
        if(true == $compressed)
        {
            return '04' . $pubKey['x'] . $pubKey['y'];
        }
        else
        {
            if(gmp_strval(gmp_mod(gmp_init($pubKey['y'], 16), gmp_init(2, 10))) == 0)
                $pubKey  	= '02' . $pubKey['x'];	//if $pubKey['y'] is even
            else
                $pubKey  	= '03' . $pubKey['x'];	//if $pubKey['y'] is odd

            return $pubKey;
        }
    }

    /***
     * Returns true if the point is on the curve and false if it isn't.
     *
     * @param $x
     * @param $y
     * @return bool
     */
    public function validatePoint($x, $y)
    {
        $a  = $this->a;
        $b  = $this->b;
        $p  = $this->p;

        $x  = gmp_init($x, 16);
        $y2 = gmp_mod(
                        gmp_add(
                            gmp_add(
                                gmp_powm($x, gmp_init(3, 10), $p),
                                gmp_mul($a, $x)
                            ),
                            $b
                        ),
                        $p
                    );
        $y = gmp_mod(gmp_pow(gmp_init($y, 16), 2), $p);

        if(gmp_cmp($y2, $y) == 0)
            return true;
        else
            return false;
    }

    /***
     * returns the X and Y point coordinates of the public key.
     *
     * @return Array Point
     * @throws \Exception
     */
    public function getPubKeyPoints()
    {
        $G = $this->G;
        $k = $this->k;

        if(!isset($this->k))
        {
            throw new \Exception('No Private Key was defined');
        }

        $pubKey 	    = $this->mulPoint($k,
                                          array('x' => $G['x'], 'y' => $G['y'])
                                 );

        $pubKey['x']	= gmp_strval($pubKey['x'], 16);
        $pubKey['y']	= gmp_strval($pubKey['y'], 16);

        while(strlen($pubKey['x']) < 64)
        {
            $pubKey['x'] = '0' . $pubKey['x'];
        }

        while(strlen($pubKey['y']) < 64)
        {
            $pubKey['y'] = '0' . $pubKey['y'];
        }

        return $pubKey;
    }

    /***
     * returns the uncompressed DER encoded public key.
     *
     * @return String Hex
     */
    public function getUncompressedPubKey()
    {
        $pubKey			    = $this->getPubKeyPoints();
        $uncompressedPubKey	= '04' . $pubKey['x'] . $pubKey['y'];

        return $uncompressedPubKey;
    }

    public function getPublicKey()
    {
	return $this->getPubKey();
    }

    /***
     * returns the compressed DER encoded public key.
     *
     * @return String Hex
     */
    public function getPubKey()
    {
	$pubKey = "";

	if ($this->c)
	{ // compressed
		$pubKey = $this->getPubKeyPoints();

        	if(gmp_strval(gmp_mod(gmp_init($pubKey['y'], 16), gmp_init(2, 10))) == 0)
            	        $pubKey  	= '02' . $pubKey['x'];	//if $pubKey['y'] is even
        	else
			$pubKey  	= '03' . $pubKey['x'];	//if $pubKey['y'] is odd
	}
	else
	{ // uncompressed
		$pubKey = $this->getUncompressedPubKey();
	}

        return $pubKey;
    }

    /***
     * returns the uncompressed Bitcoin address generated from the private key if $compressed is false and
     * the compressed if $compressed is true.
     *
     * @param bool $compressed
     * @param string $derPubKey
     * @throws \Exception
     * @return String Base58
     */
    public function getUncompressedAddress($compressed = false, $derPubKey = null)
    {
        if(null != $derPubKey)
        {
            $address = $derPubKey;
        }
        else
        {
            if($compressed) {
                $address 	= $this->getPubKey();
            }
            else {
                $address 	= $this->getUncompressedPubKey();
            }
        }

        $sha256		    = hash('sha256', hex2bin($address));
        $ripem160 	    = hash('ripemd160', hex2bin($sha256));
        $address 	    = $this->getNetworkPrefix() . $ripem160;

        //checksum
        $sha256		    = hash('sha256', hex2bin($address));
        $sha256		    = hash('sha256', hex2bin($sha256));
        $address 	    = $address.substr($sha256, 0, 8);
        $address        = $this->base58_encode($address);

        if($this->validateAddress($address))
            return $address;
        else
            throw new \Exception('the generated address seems not to be valid.');
    }

    /***
     * returns the compressed Bitcoin address generated from the private key.
     *
     * @param string $derPubKey
     * @return String Base58
     */
    public function getAddress($derPubKey = null)
    {
        return $this->getUncompressedAddress(true, $derPubKey);
    }

    /***
     * set a private key.
     *
     * @param String Hex $k
     * @throws \Exception
     */
    public function setPrivateKey($k)
    {
        //private key has to be passed as an hexadecimal number
        if(gmp_cmp(gmp_init($k, 16), gmp_sub($this->n, gmp_init(1, 10))) == 1)
        {
            throw new \Exception('Private Key is not in the 1,n-1 range');
        }
        $this->k = $k;
    }

    public function fromPassphrase($pp)
    {  // take a sha256 hash of the passphrase, and then set it as the private key
    
	$hashed = hash('sha256', hex2bin($pp));
	
	$this->setPrivateKey($hashed);

	return $this;
    }

    public function fromWif($pp)
    { // extract the private key from the key in Wallet Import Format

      	 // TODO validation

	 if ($this->validateWifKey($pp) === false) { throw new \Exception("Invalid Private Key provided."); }

	 $fullStr = $this->base58_decode($pp);
	 $withoutVersion = substr($fullStr,2);
	 $withoutChecksumAndVersion = substr($withoutVersion,0,64);

	 $this->setPrivateKey($withoutChecksumAndVersion);

	 if (substr($withoutVersion,64,2) == '01') 
	 { // is compressed
		$this->c = true;
	 }
	 else 
	 { // is not compressed
		$this->c = false;     
	 }

	 return $this;
    }

    /***
     * return the private key.
     *
     * @return String Hex
     */
    public function getPrivateKey()
    {
        return $this->k;
    }


    /***
     * Generate a new random private key.
     * The extra parameter can be some random data typed down by the user or mouse movements to add randomness.
     *
     * @param string $extra
     * @throws \Exception
     */
    public function generateRandomPrivateKey($extra = 'FSQF5356dsdsqdfEFEQ3fq4q6dq4s5d')
    {
        //private key has to be passed as an hexadecimal number
        do { //generate a new random private key until to find one that is valid
            $bytes      = openssl_random_pseudo_bytes(256, $cStrong);
            $hex        = bin2hex($bytes);
            $random     = $hex . microtime(true).rand(100000000000, 1000000000000) . $extra;
            $this->k    = hash('sha256', $random);

            if(!$cStrong)
            {
                throw new \Exception('Your system is not able to generate strong enough random numbers');
            }

        } while(gmp_cmp(gmp_init($this->k, 16), gmp_sub($this->n, gmp_init(1, 10))) == 1);
    }

    /***
     * Tests if the address is valid or not.
     *
     * @param String Base58 $address
     * @return bool
     */
    public function validateAddress($address)
    {
        $address    = hex2bin($this->base58_decode($address));
        if(strlen($address) != 25)
            return false;
        $checksum   = substr($address, 21, 4);
        $rawAddress = substr($address, 0, 21);
        $sha256		= hash('sha256', $rawAddress);
        $sha256		= hash('sha256', hex2bin($sha256));

        if(substr(hex2bin($sha256), 0, 4) == $checksum)
            return true;
        else
            return false;
    }

    /***
     * Tests if the Wif key (Wallet Import Format) is valid or not.
     *
     * @param String Base58 $wif
     * @return bool
     */
    public function validateWifKey($wif)
    {
        $key            = $this->base58_decode($wif, true);
        $length         = strlen($key);
        $firstSha256    = hash('sha256', hex2bin(substr($key, 0, $length - 8)));
        $secondSha256   = hash('sha256', hex2bin($firstSha256));
        if(substr($secondSha256, 0, 8) == substr($key, $length - 8, 8))
            return true;
        else
            return false;
    }

    function String2Hex($string){
    	     $hex='';
    	     for ($i=0; $i < strlen($string); $i++){
             	 $hex .= dechex(ord($string[$i]));
	     }
    	     return $hex;
    }
 

    /***
     * Sign a hash with the private key that was set and returns signatures as an array (R,S)
     *
     * @param $hash
     * @param null $nonce
     * @throws \Exception
     * @return Array
     */
    public function getSignatureHashPoints($hash, $nonce = null)
    {
        $n = $this->n;
        $k = $this->k;

        if(empty($k))
        {
            throw new \Exception('No Private Key was defined');
        }

        if(null == $nonce)
        {
		// use a deterministic nonce
		$nonce = $this->deterministicGenerateK($hash, $this->k);
//            $random     = openssl_random_pseudo_bytes(256, $cStrong);
//            $random     = $random . microtime(true).rand(100000000000, 1000000000000);
//            $nonce      = gmp_strval(gmp_mod(gmp_init(hash('sha256',$random), 16), $n), 16);
        }

        //first part of the signature (R).

        $rPt = $this->mulPoint($nonce, $this->G);
        $R	= gmp_strval($rPt ['x'], 16);

	// fix DER encoding -- pad it so we don't confuse overflow with being negative
	if (strlen($R)%2) { $R = '0' . $R; }
	else if (hexdec(substr($R, 0, 1)) >= 8) { $R = '00' . $R; }

        //second part of the signature (S).
        //S = nonce^-1 (hash + privKey * R) mod p

        $S = gmp_strval(
                        gmp_mod(
                                gmp_mul(
                                        gmp_invert(
                                                   gmp_init($nonce, 16),
                                                   $n
                                        ),
                                        gmp_add(
                                                gmp_init($hash, 16),
                                                gmp_mul(
                                                        gmp_init($k, 16),
                                                        gmp_init($R, 16)
                                                )
                                        )
                                ),
                                $n
                        ),
                        16
             );

	// implement BIP62

	$gmpS = gmp_init($S,16);	
	$N_OVER_TWO = gmp_div($this->n,2);

	if (gmp_cmp($gmpS,$N_OVER_TWO) > 0)
	{
		$S = gmp_strval(gmp_sub($this->n, $gmpS),16);
	}

	// fix DER encoding -- pad it so we don't confuse overflow with being negative
	if (strlen($S)%2) { $S = '0' . $S; }
	else if (hexdec(substr($S, 0, 1)) >= 8) { $S = '00' . $S; }

        return array('R' => $R, 'S' => $S);
    }

    public function sign($hash, $nonce = null)
    {
	return $this->signHash($hash, $nonce);
    }

    /***
     * Sign a hash with the private key that was set and returns a DER encoded signature
     *
     * @param $hash
     * @param null $nonce
     * @return string
     */
    public function signHash($hash, $nonce = null)
    {
    
        $points = $this->getSignatureHashPoints($hash, $nonce);

        $signature = '02' . dechex(strlen(hex2bin($points['R']))) . $points['R'] . '02' . dechex(strlen(hex2bin($points['S']))) . $points['S'];
        $signature = '30' . dechex(strlen(hex2bin($signature))) . $signature;

        return $signature;
    }

    /***
     * Satoshi client's standard message signature implementation.
     *
     * @param $message
     * @param bool $compressed
     * @param null $nonce
     * @return string
     * @throws \Exception
     */
    public function signMessage($message, $compressed = true, $nonce = null)
    {

        $hash = $this->hash256("\x18Bitcoin Signed Message:\n" . $this->numToVarIntString(strlen($message)). $message);
        $points = $this->getSignatureHashPoints(
                                                $hash,
                                                $nonce
                   );

        $R = $points['R'];
        $S = $points['S'];

        while(strlen($R) < 64)
            $R = '0' . $R;

        while(strlen($S) < 64)
            $S = '0' . $S;

        $res = "\n-----BEGIN BITCOIN SIGNED MESSAGE-----\n";
        $res .= $message;
        $res .= "\n-----BEGIN SIGNATURE-----\n";
        if(true == $compressed)
            $res .= $this->getAddress() . "\n";
        else
            $res .= $this->getUncompressedAddress() . "\n";

        $finalFlag = 0;
        for($i = 0; $i < 4; $i++)
        {
            $flag = 27;
            if(true == $compressed)
                $flag += 4;
            $flag += $i;

            $pubKeyPts = $this->getPubKeyPoints();

            $recoveredPubKey = $this->getPubKeyWithRS($flag, $R, $S, $hash);

            if($this->getDerPubKeyWithPubKeyPoints($pubKeyPts, $compressed) == $recoveredPubKey)
            {
                $finalFlag = $flag;
            }
        }

        if(0 == $finalFlag)
        {
            throw new \Exception('Unable to get a valid signature flag.');
        }


        $res .= base64_encode(hex2bin(dechex($finalFlag) . $R . $S));
        $res .= "\n-----END BITCOIN SIGNED MESSAGE-----";

        return $res;
    }

    /***
     * extract the public key from the signature and using the recovery flag.
     * see http://crypto.stackexchange.com/a/18106/10927
     * based on https://github.com/brainwallet/brainwallet.github.io/blob/master/js/bitcoinsig.js
     * possible public keys are r−1(sR−zG) and r−1(sR′−zG)
     * Recovery flag rules are :
     * binary number between 28 and 35 inclusive
     * if the flag is > 30 then the address is compressed.
     *
     * @param $flag
     * @param $R
     * @param $S
     * @param $hash
     * @return array
     */
    public function getPubKeyWithRS($flag, $R, $S, $hash)
    {

        $isCompressed = false;

        if ($flag < 27 || $flag >= 35)
            return false;

        if($flag >= 31) //if address is compressed
        {
            $isCompressed = true;
            $flag -= 4;
        }

        $recid = $flag - 27;

        //step 1.1
        $x = null;
        $x = gmp_add(
                     gmp_init($R, 16),
                     gmp_mul(
                             $this->n,
                             gmp_div_q( //check if j is equal to 0 or to 1.
                                        gmp_init($recid, 10),
                                        gmp_init(2, 10)
                             )
                     )
             );

        //step 1.3
        $y = null;
        if(1 == $flag % 2) //check if y is even.
        {
            $gmpY = $this->calculateYWithX(gmp_strval($x, 16), '02');
            if(null != $gmpY)
                $y = gmp_init($gmpY, 16);
        }
        else
        {
            $gmpY = $this->calculateYWithX(gmp_strval($x, 16), '03');
            if(null != $gmpY)
                $y = gmp_init($gmpY, 16);
        }

        if(null == $y)
            return null;

        $Rpt = array('x' => $x, 'y' => $y);

        //step 1.6.1
        //calculate r^-1 (S*Rpt - eG)

        $eG = $this->mulPoint($hash, $this->G);

        $eG['y'] = gmp_mod(gmp_neg($eG['y']), $this->p);

        $SR = $this->mulPoint($S, $Rpt);

        $pubKey = $this->mulPoint(
                            gmp_strval(gmp_invert(gmp_init($R, 16), $this->n), 16),
                            $this->addPoints(
                                             $SR,
                                             $eG
                            )
                  );

        $pubKey['x'] = gmp_strval($pubKey['x'], 16);
        $pubKey['y'] = gmp_strval($pubKey['y'], 16);

        while(strlen($pubKey['x']) < 64)
            $pubKey['x'] = '0' . $pubKey['x'];

        while(strlen($pubKey['y']) < 64)
            $pubKey['y'] = '0' . $pubKey['y'];

        $derPubKey = $this->getDerPubKeyWithPubKeyPoints($pubKey, $isCompressed);


        if($this->checkSignaturePoints($derPubKey, $R, $S, $hash))
            return $derPubKey;
        else
            return false;

    }

    /***
     * Check signature with public key R & S values of the signature and the message hash.
     *
     * @param $pubKey
     * @param $R
     * @param $S
     * @param $hash
     * @return bool
     */
    public function checkSignaturePoints($pubKey, $R, $S, $hash)
    {
        $G = $this->G;

        $pubKeyPts = $this->getPubKeyPointsWithDerPubKey($pubKey);

        // S^-1* hash * G + S^-1 * R * Qa

        // S^-1* hash
        $exp1 =  gmp_strval(
                            gmp_mul(
                                    gmp_invert(
                                               gmp_init($S, 16),
                                               $this->n
                                    ),
                                    gmp_init($hash, 16)
                            ),
                            16
                 );

        // S^-1* hash * G
        $exp1Pt = $this->mulPoint($exp1, $G);


        // S^-1 * R
        $exp2 =  gmp_strval(
                            gmp_mul(
                                    gmp_invert(
                                               gmp_init($S, 16),
                                                $this->n
                                    ),
                                    gmp_init($R, 16)
                            ),
                            16
                 );
        // S^-1 * R * Qa

        $pubKeyPts['x'] = gmp_init($pubKeyPts['x'], 16);
        $pubKeyPts['y'] = gmp_init($pubKeyPts['y'], 16);

        $exp2Pt = $this->mulPoint($exp2,$pubKeyPts);

        $resultingPt = $this->addPoints($exp1Pt, $exp2Pt);

        $xRes = gmp_strval($resultingPt['x'], 16);

        while(strlen($xRes) < 64)
            $xRes = '0' . $xRes;

        if($xRes == $R)
            return true;
        else
            return false;
    }

    /***
     * checkSignaturePoints wrapper for DER signatures
     *
     * @param $pubKey
     * @param $signature
     * @param $hash
     * @return bool
     */
    public function checkDerSignature($pubKey, $signature, $hash)
    {
        $signature = hex2bin($signature);
        if('30' != bin2hex(substr($signature, 0, 1)))
            return false;

        $RLength = hexdec(bin2hex(substr($signature, 3, 1)));
        $R = bin2hex(substr($signature, 4, $RLength));

        $SLength = hexdec(bin2hex(substr($signature, $RLength + 5, 1)));
        $S = bin2hex(substr($signature, $RLength + 6, $SLength));

        return $this->checkSignaturePoints($pubKey, $R, $S, $hash);
    }

    /***
     * checks the signature of a bitcoin signed message.
     *
     * @param $rawMessage
     * @return bool
     */
    public function checkSignatureForRawMessage($rawMessage)
    {
        //recover message.
        preg_match_all("#-----BEGIN BITCOIN SIGNED MESSAGE-----\n(.{0,})\n-----BEGIN SIGNATURE-----\n#USi", $rawMessage, $out);
        $message = $out[1][0];

        preg_match_all("#\n-----BEGIN SIGNATURE-----\n(.{0,})\n(.{0,})\n-----END BITCOIN SIGNED MESSAGE-----#USi", $rawMessage, $out);
        $address = $out[1][0];
        $signature = $out[2][0];

        return $this->checkSignatureForMessage($address, $signature, $message);
    }

    /***
     * checks the signature of a bitcoin signed message.
     *
     * @param $address
     * @param $encodedSignature
     * @param $message
     * @return bool
     */
    public function checkSignatureForMessage($address, $encodedSignature, $message)
    {
        $hash = $this->hash256("\x18Bitcoin Signed Message:\n" . $this->numToVarIntString(strlen($message)) . $message);

        //recover flag
        $signature = base64_decode($encodedSignature);

        $flag = hexdec(bin2hex(substr($signature, 0, 1)));

        $R = bin2hex(substr($signature, 1, 64));
        $S = bin2hex(substr($signature, 65, 64));

        $derPubKey = $this->getPubKeyWithRS($flag, $R, $S, $hash);

        $recoveredAddress = $this->getAddress($derPubKey);

        if($address == $recoveredAddress)
            return true;
        else
            return false;
    }
}

function strToHex($string)
{
    $hex='';
    for ($i=0; $i < strlen($string); $i++)
    {
        $hex .= dechex(ord($string[$i]));
    }
    return $hex;
}

?>
