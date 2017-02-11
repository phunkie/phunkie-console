<?php

namespace PhunkieConsole\Block;

use function Phunkie\Functions\currying\curry;
use function Phunkie\Functions\function1\compose;
use function Phunkie\Functions\immlist\concat;
use function Phunkie\Functions\lens\makeLenses;
use Phunkie\Types\ImmMap;
use Phunkie\Types\Pair;
use function PhunkieConsole\IO\Lens\getBlock;
use function PhunkieConsole\IO\Lens\updateBlock;
use function PhunkieConsole\IO\Lens\updatePrompt;
use function PhunkieConsole\Parser\parse;
use const PhunkieConsole\PhpCompiler\compile;
use PhunkieConsole\Result\NoResult;

class Block
{
    private $input;
    public function __construct($input)
    {
        $this->input = $input;
    }
    public function run($state)
    {
        $state = updateBlock($state, concat(getBlock($state), ImmList($this->input)));
        $block = getBlock($state)->mkString("\n");
        return blockStarted($block) ? updatePromptAndDoNothing($state, $block) : evaluateBlockAndReset($state);
    }
}

function Block($input)
{
    return new Block($input);
}

function isBlock(string $code, $state): bool
{
    return blockStarted($code) || !makeLenses('block')->block->get($state)->get()->isEmpty();
}

const stripString = "\\PhunkieConsole\\Block\\stripString";
function stripString(string $code): string
{
    $acc = ImmMap([
        "started" => false,
        "stripped" => '',
        "lastChar" => ''
    ]);

    return ImmList(...str_split($code))->foldLeft($acc, function(ImmMap $acc, string $char): ImmMap {
        $changeStartedFlag = ($char === "'" || $char === '"') && $acc["lastChar"]->getOrElse('') !== "\\";

        return ImmMap([
            "started" => $started = $changeStartedFlag ? !$acc["started"]->get() : $acc["started"]->get(),
            "stripped" => !$changeStartedFlag && !$started ? $acc["stripped"]->get() . $char : $acc["stripped"]->get(),
            "lastChar" => $char
        ]);
    })["stripped"]->get();
}

const stripStringHex = "\\PhunkieConsole\\Block\\stripStringHex";
function stripStringHex(string $code): string
{
    if (preg_match('/<<<(\w+)/', $code, $matches)) {
        $start = strpos($code, "<<<");
        $end = strpos(substr($code, strpos($code, $matches[1]) + 1), $matches[1]) + strpos($code, $matches[1]) + 1;
        return stripStringHex(str_replace(substr($code, $start, $end - $start), "", $code));
    }
    return $code;
}

const checkBlockOccurrences = "\\PhunkieConsole\\Block\\checkBlockOccurrences";
function checkBlockOccurrences(callable $countOccurrences): bool
{
    return $countOccurrences("{") > $countOccurrences("}") || $countOccurrences("(") > $countOccurrences(")");
}

function blockStarted(string $code): bool
{
    return (compose(stripStringHex, stripString, curry('substr_count'), checkBlockOccurrences))($code);
}

function updatePromptAndDoNothing(ImmMap $state, string $block): Pair
{
    return Pair(updatePrompt($state, getBlockType($block)),
        Nel(Success(new NoResult(""))));
}

function evaluateBlockAndReset(ImmMap $state): Pair
{
    return parse(getBlock($state)->mkString(" "))
        ->flatMap(compile)->run(updatePrompt(updateBlock($state, Nil()), ">"));
}

function getBlockType(string $code): string
{
    $countOccurrences = (curry('substr_count')) ($code);
    $code = (compose(stripStringHex, stripString)) ($code);

    if ($countOccurrences("{") > $countOccurrences("}")) {
        return $countOccurrences("(") > $countOccurrences(")") ?
            strrpos($code, "{") > strrpos($code, "(") ? "{" : "(" : "{";
    }
    return $countOccurrences("(") > $countOccurrences(")") ? "(" : ">";
}