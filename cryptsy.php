<?php

require('./config.php');

// Cryptsy API connector
function api_query_cryptsy($method, array $req = array()) {
        // API settings
        global $cryptsy_api_key;
        global $cryptsy_api_secret;

        $req["method"] = $method;
        $mt = explode(' ', microtime());
        $req["nonce"] = $mt[1];

        // generate the POST data string
        $post_data = http_build_query($req, '', '&');
        $sign = hash_hmac("sha512", $post_data, $cryptsy_api_secret);

        // generate the extra headers
        $headers = array(
                'Sign: '.$sign,
                'Key: '.$cryptsy_api_key,
        );

        // our curl handle (initialize if required)
        static $ch = null;
        if (is_null($ch)) {
                $ch = curl_init();
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_USERAGENT,
                  'Mozilla/4.0 (compatible; Cryptsy API PHP client; '.php_uname('s').'; PHP/'.phpversion().')');
        }

        curl_setopt($ch, CURLOPT_URL, 'https://www.cryptsy.com/api');
        curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);

        $cookie_file = "cookie1.txt";
        curl_setopt($ch, CURLOPT_COOKIESESSION, true);
        curl_setopt($ch, CURLOPT_COOKIEFILE, $cookie_file);
        curl_setopt($ch, CURLOPT_COOKIEJAR, $cookie_file);

        // run the query
        try {
            $res = curl_exec($ch);
            if ($res === false) throw new Exception('Could not get reply: '.curl_error($ch));
            $dec = json_decode($res, true);
            if (!$dec) throw new Exception('Invalid data received, please make sure connection is working and requested API exists');
        } catch (Exception $e) {
            error("Caught exception: " .  $e->getMessage());
        }

        return $dec;
}

?>
