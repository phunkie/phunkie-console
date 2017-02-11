<?php

use function Phunkie\Functions\currying\curry;
use function Phunkie\Functions\function1\compose;
use function Phunkie\Functions\lens\makeLenses;

function isBlock(string $code, $state)
{
    return blockStarted($code) || !makeLenses('block')->block->get($state)->get()->isEmpty();
}

function blockStarted(string $code)
{
    return (compose("stripStringHex", "stripString", curry('substr_count'), "checkBlockOccurrences"))($code);
}

function checkBlockOccurrences($countOccurrences): bool
{
    return $countOccurrences("{") > $countOccurrences("}") || $countOccurrences("(") > $countOccurrences(")");
}

function getBlockType($code)
{
    $countOccurrences = (curry('substr_count'))($code);
    $code = (compose("stripStringHex", "stripString"))($code);

    if ($countOccurrences("{") > $countOccurrences("}")) {
        if ($countOccurrences("(") > $countOccurrences(")")) {
            return strrpos($code, "{") > strrpos($code, "(") ? "{" : "(";
        }
        return "{";
    } else {
        if ($countOccurrences("(") > $countOccurrences(")")) {
            return "(";
        }
    }
    return ">";
}

function stripString($code)
{
    $acc = ImmMap([
        "started" => false,
        "stripped" => '',
        "lastChar" => ''
    ]);

    return ImmList(...str_split($code))->foldLeft($acc, function($acc, $char) {
        $changeStartedFlag = ($char === "'" || $char === '"') && $acc["lastChar"]->getOrElse('') !== "\\";

        return ImmMap([
            "started" => $started = $changeStartedFlag ? !$acc["started"]->get() : $acc["started"]->get(),
            "stripped" => !$changeStartedFlag && !$started ? $acc["stripped"]->get() . $char : $acc["stripped"]->get(),
            "lastChar" => $char
        ]);
    })["stripped"]->get();
}

function stripStringHex($code): string
{
    if (preg_match('/<<<(\w+)/', $code, $matches)) {
        $start = strpos($code, "<<<");
        $end = strpos(substr($code, strpos($code, $matches[1]) + 1), $matches[1]) + strpos($code, $matches[1]) + 1;
        return stripStringHex(str_replace(substr($code, $start, $end - $start), "", $code));
    }
    return $code;
}