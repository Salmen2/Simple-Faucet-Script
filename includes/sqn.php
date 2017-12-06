<?php

function sqn_validate($string,$key,$id,$off=false){
    $time_zone = @date_default_timezone_get();
    @date_default_timezone_set('Asia/Shanghai');
    $url = 'http://ms.shenqiniao.com/?v=1.0';

    $string = str_replace('[U]','+',$string);
    $string = str_replace('[A]','/',$string);
    $string = str_replace('[B]','\\',$string);

    $ckey_length = 4;
    $key = md5($key);
    $keya = md5(substr($key, 0, 16));
    $keyb = md5(substr($key, 16, 16));
    $keyc = substr($string, 0, $ckey_length);

    $cryptkey = $keya.md5($keya.$keyc);
    $key_length = strlen($cryptkey);

    $string =  base64_decode(substr($string, $ckey_length));
    $string_length = strlen($string);

    $result = '';
    $box = range(0, 255);

    $rndkey = array();
    for($i = 0; $i <= 255; $i++)
    {
        $rndkey[$i] = ord($cryptkey[$i % $key_length]);
    }

    for($j = $i = 0; $i < 256; $i++)
    {
        $j = ($j + $box[$i] + $rndkey[$i]) % 256;
        $tmp = $box[$i];
        $box[$i] = $box[$j];
        $box[$j] = $tmp;
    }

    for($a = $j = $i = 0; $i < $string_length; $i++)
    {
        $a = ($a + 1) % 256;
        $j = ($j + $box[$a]) % 256;
        $tmp = $box[$a];
        $box[$a] = $box[$j];
        $box[$j] = $tmp;
        $result .= chr(ord($string[$i]) ^ ($box[($box[$a] + $box[$j]) % 256]));
    }
    if((substr($result, 0, 10) == 0 || substr($result, 0, 10) - time() > 0) && substr($result, 10, 16) == substr(md5(substr($result, 26).$keyb), 0, 16)) {
        $res = unserialize(substr($result, 26));
    }
    else
    {
        @date_default_timezone_set($time_zone);
        return false;
    }

    @date_default_timezone_set($time_zone);

    if($off){
        if(isset($_COOKIE) && !empty($_COOKIE)){
            if(isset($_COOKIE['sqn_id']) && !empty($_COOKIE['sqn_id'])){
                if(isset($_SESSION['sqn_predate']) && is_numeric($_SESSION['sqn_predate'])){
                    $_SESSION['sqn_pretime'] = time() - intval($_SESSION['sqn_predate']);
                    $_SESSION['sqn_predate'] = time();
                }else{
                    $_SESSION['sqn_predate'] = time();
                    $_SESSION['sqn_pretime'] = 100000000;
                }
                return $_SESSION['sqn_pretime'];
            }else{
                return false;
            }
        }
        return 100000000000;
    }else{
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, FALSE);
        curl_setopt($curl, CURLOPT_POST, 1);
        curl_setopt($curl, CURLOPT_TIMEOUT,10);
        curl_setopt($curl, CURLOPT_POSTFIELDS, array('captcha'=>$res,'key'=>$key,'id'=>$id));
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        $result = curl_exec($curl);
        if (curl_errno($curl)) {
            return false;//'Errno'.curl_error($curl);
        }
        curl_close($curl);
        if((strpos($result,'alidate success')>0) || (strpos($result,'alidate Fail')<1)){
            return true;
        }
        return false;
    }
}