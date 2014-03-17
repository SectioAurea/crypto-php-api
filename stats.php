<?php

// Fetch market/orderbook API
// $pair only used for display code
function get_market_api($exchange, $market_id, $pair) {
    if ($exchange === 'Cryptsy') {
        $api_result = api_query_cryptsy("marketorders", array("marketid" => $market_id));
    } elseif ($exchange === 'btce') {
        $api_result = json_decode( api_public_btce( 'https://btc-e.com/api/2/' . $market_id . '/depth' ), TRUE );
    }
    if (!$api_result) {
        print "\n";
        error("$pair - Unable to fetch order book for pair $pair on exchange $exchange (market id $market_id)");
    }
    debug(2, "$pair - Got API from $exchange ok");

    return $api_result;
}


// Get a buy price to buy a Pair on a specific exchange
// Uses depth on asks/sells
function get_buy_price($exchange, $pair, $target_buy_amount) {
    global $pair_metadata;
    $sell_price = 0;
    $sell_quantity = 0;

    $market_id = $pair_metadata[$exchange][$pair]["market_id"];
    $min_pip_sell = $pair_metadata[$exchange][$pair]["min_pip_sell"];

    debug(2, "$pair - Collecting price data from $exchange...");

    $api_result = get_market_api($exchange, $market_id, $pair);

    // If the two orderbooks are meeting temporarily, get new prices
    if ($exchange === 'Cryptsy') {
        while ($api_result["return"]["sellorders"][0]["sellprice"]
            <= $api_result["return"]["buyorders"][0]["buyprice"]) {

            $api_result = get_market_api($exchange, $market_id, $pair);
        }
    } elseif ($exchange === 'btce') {
        while ($api_result["asks"][0][0]
            <= $api_result["bids"][0][0]) {
            $api_result = get_market_api($exchange, $market_id, $pair);
        }
    }

    if ($exchange === 'Cryptsy') {
        $sell_orders = $api_result["return"]["sellorders"];
    } elseif ($exchange === 'btce') {
        $sell_orders = $api_result["asks"];
    }

    // Sells - Implementing orderbook depth traversal
    if (isset($sell_orders[0])) {
        $final_curr = substr($pair, 4, 3);
        $start_curr = substr($pair, 0, 3);

        // Start with values from top of book
        if ($exchange === 'Cryptsy') {
            $sell_price = $sell_orders[0]["sellprice"];
            $sell_quantity = $sell_orders[0]["quantity"];
        } elseif ($exchange === 'btce') {
            $sell_price = $sell_orders[0][0];
            $sell_quantity = $sell_orders[0][1];
        }
        $average_sell_price = $sell_price;

        // If low sell amount is greater than target amount, use the low sell
        if ($sell_quantity * $average_sell_price > $target_buy_amount) {
            debug(1, "$pair - Lowest ask/sell order quantity $sell_quantity $start_curr"
                . " exceeds target buy depth/yield amount $target_buy_amount $final_curr"
                . " - using its price $sell_price $final_curr");
        } else {
            debug(1, "$pair - Lowest ask/sell order quantity $sell_quantity $start_curr"
                . " under target buy depth/yield amount $target_buy_amount $final_curr"
                . " at $sell_price $final_curr per $start_curr - descending into the orderbook...");
            $i = 0;
            $sell_price = 0;
            $sell_quantity = 0;
            $current_price = 0;
            $current_quantity = 0;
            $current_amount = 0;
            $total_amount = 0;
            $total_quantity = 0;

            // Until we find a price, keep going into the orderbook and tracking running totals
            while ($sell_price == 0) {
                foreach ($sell_orders as $sellorder) {
                    if ($exchange === 'Cryptsy') {
                        $current_price = $sellorder["sellprice"];
                        $current_quantity = $sellorder["quantity"];
                    } elseif ($exchange === 'btce') {
                        $current_price = $sellorder[0];
                        $current_quantity = $sellorder[1];
                    }
                    $current_amount = sprintf('%0.8f', $current_price * $current_quantity);

                    $total_amount = sprintf('%0.8f', $total_amount + $current_amount);
                    $total_quantity = sprintf('%0.8f', $total_quantity + $current_quantity);
                    $average_sell_price = sprintf('%0.8f', $total_amount / $total_quantity);

                    debug(1, "$pair - ask/sell Order $i - CurrP: $current_price, CurrQ: $current_quantity,"
                        . " TotalQ: $total_quantity, CurrA: $current_amount, TotalA: $total_amount");

                    if ($total_quantity > $min_pip_sell
                        and $total_quantity * $average_sell_price > $target_buy_amount) {

                        $sell_price = $current_price;
                        $sell_quantity = $target_buy_amount;

                        debug(1, "$pair - Able to buy at Average Sell Price: $average_sell_price -"
                            . " Sell Price: $sell_price - Sell Quantity available: $sell_quantity");
                        break;

                    }
                    $i = ++$i;
                }
            }
        }
    }

    debug (1, "$pair - SellPrice $sell_price, SellQty available $sell_quantity");

    $stats = array(
        "market_id" => $market_id,
        "pair" => $pair,
        "exchange" => $exchange,
        "sell_price" => $sell_price,
        "average_sell_price" => $average_sell_price,
        "min_pip_sell" => $min_pip_sell,
        "sell_quantity" => $sell_quantity,
    );
    return $stats;
}


// Get a sell price to sell a Pair for a specific exchange
// Uses depth on bids/buys
function get_sell_price($exchange, $pair, $target_sell_quantity) {
    global $pair_metadata;
    $buy_price = 0;
    $buy_quantity = 0;
    $sell_price = 0;
    $sell_quantity = 0;

    $market_id = $pair_metadata[$exchange][$pair]["market_id"];
    $min_pip_buy = $pair_metadata[$exchange][$pair]["min_pip_buy"];

    debug(2, "$pair - Collecting price data from $exchange...");

    $api_result = get_market_api($exchange, $market_id, $pair);

    // If the two orderbooks are meeting temporarily, get new prices
    if ($exchange === 'Cryptsy') {
        while ($api_result["return"]["sellorders"][0]["sellprice"]
            <= $api_result["return"]["buyorders"][0]["buyprice"]) {

            $api_result = get_market_api($exchange, $market_id, $pair);
        }
    } elseif ($exchange === 'btce') {
        while ($api_result["asks"][0][0]
            <= $api_result["bids"][0][0]) {
            $api_result = get_market_api($exchange, $market_id, $pair);
        }
    }

    if ($exchange === 'Cryptsy') {
        $buy_orders = $api_result["return"]["buyorders"];
    } elseif ($exchange === 'btce') {
        $buy_orders = $api_result["bids"];
    }

    // Buys - Implementing orderbook depth traversal
    if (isset($buy_orders[0])) {
        $final_curr = substr($pair, 4, 3);
        $start_curr = substr($pair, 0, 3);

        // Start with values from top of book
        if ($exchange === 'Cryptsy') {
            $buy_price = $buy_orders[0]["buyprice"];
            $buy_quantity = $buy_orders[0]["quantity"];
        } elseif ($exchange === 'btce') {
            $buy_price = $buy_orders[0][0];
            $buy_quantity = $buy_orders[0][1];
        }
        $average_buy_price = $buy_price;

        if ($buy_quantity > $target_sell_quantity) {
            debug(1, "$pair - Highest bid/buy order quantity $buy_quantity $start_curr"
                . " exceeds target sell depth/spend quantity $target_sell_quantity $start_curr,"
                . " using its price $buy_price $final_curr");
        } else {
            debug(1, "$pair - Highest bid/buy order quantity $buy_quantity $start_curr"
                . " under target sell depth/spend quantity $target_sell_quantity $start_curr"
                . " at $buy_price $final_curr per $start_curr, descending into the orderbook...");

            $i = 0;
            $buy_price = 0;
            $buy_quantity = 0;
            $current_price = 0;
            $current_quantity = 0;
            $current_amount = 0;
            $total_amount = 0;
            $total_quantity = 0;

            // Until we find a price, keep going into the orderbook and tracking running totals
            while ($buy_price == 0) {

                foreach ($buy_orders as $buyorder) {
                    if ($exchange === 'Cryptsy') {
                        $current_price = $buyorder["buyprice"];
                        $current_quantity = $buyorder["quantity"];
                    } elseif ($exchange === 'btce') {
                        $current_price = $buyorder[0];
                        $current_quantity = $buyorder[1];
                    }

                    $current_amount = sprintf('%0.8f', $current_price * $current_quantity);

                    $total_amount = sprintf('%0.8f', $total_amount + $current_amount);
                    $total_quantity = sprintf('%0.8f', $total_quantity + $current_quantity);
                    $average_buy_price = sprintf('%0.8f', $total_amount / $total_quantity);

                    debug(1, "$pair - bid/buy Order $i, CurrP: $current_price, CurrQ: $current_quantity,"
                        . " TotalQ: $total_quantity, CurrA: $current_amount, TotalA: $total_amount");

                    if ($total_quantity > $min_pip_buy
                        and $total_quantity > $target_sell_quantity) {

                        $buy_price = $current_price;
                        $buy_quantity = $target_sell_quantity / $buy_price;
                        debug(1, "$pair - Able to sell into Average Buy Price: $average_buy_price -"
                            . " Buy Price: $buy_price - Buy Quantity available: $buy_quantity");
                        break;
                    }

                    $i = ++$i;
                }
            }
        }
    }

    $stats = array(
        "market_id" => $market_id,
        "pair" => $pair,
        "exchange" => $exchange,
        "buy_price" => $buy_price,
        "average_buy_price" => $average_buy_price,
        "min_pip_buy" => $min_pip_buy,
        "buy_quantity" => $buy_quantity,
    );
    return $stats;
}


?>
