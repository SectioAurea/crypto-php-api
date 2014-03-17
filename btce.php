<?php

require('./config.php');

$mt = explode(' ', microtime());
$nonce = $mt[1];

// Public API
function api_public_btce( $url = NULL )
{
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_HEADER, FALSE);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
    $result = curl_exec($ch);
    curl_close($ch);

    return $result;
}

// http://pastebin.com/QyjS3U9M
function btce_query($method, array $req = array()) {
        // API settings
        global $btce_api_key;
        global $btce_api_secret;
        global $nonce;

        $req['method'] = $method;
        $req['nonce'] = $nonce++;

        // generate the POST data string
        $post_data = http_build_query($req, '', '&');
        $sign = hash_hmac('sha512', $post_data, $btce_api_secret);

        // generate the extra headers
        $headers = array(
			'Sign: '.$sign,
			'Key: '.$btce_api_key,
        );

        // our curl handle (initialize if required)
        static $ch = null;
        if (is_null($ch)) {
                $ch = curl_init();
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/4.0 (compatible; BTCE PHP client; PHP/'.phpversion().')');
        }
        curl_setopt($ch, CURLOPT_URL, 'https://btc-e.com/tapi/');
        curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);

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
