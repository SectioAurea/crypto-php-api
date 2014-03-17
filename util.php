<?php

// Format microsecond datetimes
function udate($format, $utimestamp = null)
{
    if (is_null($utimestamp))
        $utimestamp = microtime(true);

    $timestamp = floor($utimestamp);
    $milliseconds = round(($utimestamp - $timestamp) * 1000000);

    return date(preg_replace('`(?<!\\\\)u`', $milliseconds, $format), $timestamp);
}

function debug($debug_level, $statement) {
    global $debug, $debug2;

    if ($debug_level == 1 and $debug === 'true') {
        echo "DEBUG   " . udate('H:i:s.u') . "   " . $statement . "\n";
    } elseif ($debug_level == 2 and $debug2 === 'true') {
        echo "DEBUG   " . udate('H:i:s.u') . "   " . $statement . "\n";
    }
}

function devel($statement) {
    global $devel;

    if ($devel === 'true') {
        echo "DEVEL   " . udate('H:i:s.u') . "   " . $statement . "\n";
    }
}

function info($statement) {
    global $info;

    if ($info === 'true') {
        echo "INFO    " . udate('H:i:s.u') . "   " . $statement . "\n";
    }
}

function error($statement) {
    global $error;

    if ($error === 'true') {
        echo "ERROR   " . udate('H:i:s.u') . "   " . $statement . "\n";
    }
}

function warn($statement) {
    global $warn;

    if ($warn === 'true') {
        echo "WARN    " . udate('H:i:s.u') . "   " . $statement . "\n";
    }
}

?>
