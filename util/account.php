<?php

// Get account info for a given exchange
function get_acc_info($exchange) {
    global $currency_list;

    debug(2, "$exchange - Getting account information");
    if ($exchange === 'Cryptsy') {
        $all_open_orders = api_query_cryptsy("allmyorders");
        $all_open_orders = $all_open_orders["return"];
        $raw_info = api_query_cryptsy("getinfo");
        $raw_info = $raw_info["return"];
    } elseif ($exchange === 'btce') {
        $raw_info = btce_query('getInfo');
        $raw_info = $raw_info["return"];
    }

    foreach ($currency_list[$exchange] as $curr_sym) {
        if ($exchange === 'Cryptsy') {
            $acc_info["balances_available"][$curr_sym] = $raw_info["balances_available"][$curr_sym];
            if (isset($raw_info["balances_hold"][$curr_sym])) {
              $acc_info["balances_hold"][$curr_sym] = $raw_info["balances_hold"][$curr_sym];
            }
        } elseif ($exchange === 'btce') {
            $acc_info["balances_available"][$curr_sym] = $raw_info["funds"][$curr_sym];
        }
    }

    debug(2, "$exchange - Returning account information");
    return $acc_info;
}


// ACCOUNTS
function print_account_info($exchange, $acc_info) {
    global $currency_list;

    info("$exchange - Account information");

    foreach ($currency_list[$exchange] as $curr_sym) {
        if ($acc_info["balances_available"][$curr_sym] > 0) {
            info("$exchange - $curr_sym available: " . $acc_info["balances_available"][$curr_sym]);
        }
        if (isset($acc_info["balances_hold"][$curr_sym])) {
            if ($acc_info["balances_hold"][$curr_sym] > 0) {
                info("$exchange - $curr_sym held for tx: " . $acc_info["balances_hold"][$curr_sym]);
            }
        }
    }
}

?>
