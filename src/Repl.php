<?php

namespace PhunkieConsole\Repl;

use ErrorException;
use function Phunkie\Functions\monad\mcompose;
use function Phunkie\Functions\state\gets;
use function Phunkie\Functions\tuple\assign;
use function PhunkieConsole\Block\Block;
use function PhunkieConsole\Block\isBlock;
use function PhunkieConsole\Command\Command;
use function PhunkieConsole\Command\isCommand;
use function PhunkieConsole\IO\Lens\updateBlock;
use function PhunkieConsole\IO\Lens\updatePrompt;
use PhunkieConsole\Result\NoResult;
use PhunkieConsole\Result\Result;

use Phunkie\Cats\State;
use Phunkie\Types\ImmList;
use Phunkie\Types\Pair;
use Phunkie\Cats\IO as IOUnit;

use function PhunkieConsole\IO\Lens\config;
use function PhunkieConsole\IO\Lens\promptLens;
use function PhunkieConsole\IO\PrintNothing;
use function PhunkieConsole\IO\ReadLine;
use function PhunkieConsole\IO\PrintLn;
use function PhunkieConsole\Parser\parse;

use function \Phunkie\PatternMatching\Referenced\Success as Valid;
use function \Phunkie\PatternMatching\Referenced\Failure as Invalid;

use const PhunkieConsole\PhpCompiler\compile;
use const PhunkieConsole\IO\Colours\colours;
use function PhunkieConsole\IO\Colours\colours as format;

use Throwable;

const repl = "PhunkieConsole\\Repl\\repl";
const read = "PhunkieConsole\\Repl\\read";
const evaluate = "PhunkieConsole\\Repl\\evaluate";
const andPrint = "PhunkieConsole\\Repl\\andPrint";
const loop = "PhunkieConsole\\Repl\\loop";

function repl($state)
{
    return read()->
        flatMap(evaluate)->
        flatMap(andPrint)->
        flatMap(loop)
            ->run($state);
}

function read($s = None): State
{
    return new State(function($state) use ($s) {
        if ($s == None) $s = $state;
        return Pair($s, ReadLine(promptLens()->get($s))->run());
    });
}

function evaluate($input): State
{
    return new State(function($state) use ($input) {
        try { switch(true) {
            case isCommand($input) : return Command($input)->run($state);
            case isBlock($input, $state) : return Block($input)->run($state);
            default :return parse($input)->flatMap(compile)->run($state); }
        } catch (\Throwable $e) {
            return Pair($state, Nel(Failure($e)));
        }
    });
}

function andPrint($result): State
{
    return new State(function($state) use ($result) {
        return printResult($state, $result);
    });
}

function loop(): State
{
    return gets(repl);
}

function printResult($state, ImmList $resultList): Pair
{
    /** @var Result $result */
    /** @var Throwable $e */
    $result = $e = null;
    $resultList->map(function($resultToPrint) use (&$state, $result, $e) {
        $on = match($resultToPrint);
        switch (true) {
            case $on(Valid($result)):
                if ($result instanceof NoResult) {
                    PrintNothing()->run();
                    break;
                }
                PrintLn($result->output(config($state, "formatter")->getOrElse(colours)))->run();
                break;
            default:
                $on(Invalid($e));
                (assign($state, $io))(printError($e, $state));
                $io->run();
        }
    });
    return Pair($state, $state);
}

function printError(Throwable $e, $state): Pair
{
    $state = updatePrompt(updateBlock($state, Nil()), ">");
    if ($e instanceof ErrorException) {
        switch ($e->getSeverity()) {
            case E_USER_NOTICE:
            case E_NOTICE:
            case E_STRICT:
                return Pair($state, PrintLn(format()['bold']("Notice") . ": " . format()['boldRed']($e->getMessage())));
            case E_USER_WARNING:
            case E_WARNING:
                return Pair($state, PrintLn(format()['bold']("Warning") . ": " . format()['boldRed']($e->getMessage())));
            case E_USER_ERROR:
                return Pair($state, PrintLn(format()['bold']("Fatal error") . ": " . format()['boldRed']($e->getMessage())));
            case E_RECOVERABLE_ERROR:
                return Pair($state, PrintLn(format()['bold']("Catchable") . ": " . format()['boldRed']($e->getMessage())));
            default:
                return Pair($state, PrintLn(format()['bold']("Error") . ": " . format()['boldRed']($e->getMessage())));
        }
    }
    if ($e instanceof \PhpParser\Error) {
        return Pair ($state, PrintLn(format()['bold']("Parse error") . ": " . format()['boldRed']($e->getMessage())));
    }
    return Pair ($state, PrintLn(format()['bold'](get_class($e)) . ": " . format()['boldRed']($e->getMessage())));
}