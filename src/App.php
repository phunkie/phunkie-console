<?php

namespace PhunkieConsole\App;

use const PhunkieConsole\IO\Colours\colours;
use function PhunkieConsole\IO\Lens\config;
use const PhunkieConsole\Repl\andPrint;
use function PhunkieConsole\Repl\evaluate;
use function PhunkieConsole\Repl\repl;
use function \PhunkieConsole\IO\PrintLines;

const Interactive = "Interactive";
const SingleLine = "SingleLine";
const File = "File";
const Help = "Help";

function mode(int $argc, array $argv) { switch (true)
{
    case $argc == 1 || ($argc == 2 && $argv[1] === "--no-colors"):
        return Interactive;
    case $argc == 2 && ($argv[1] === "-h" || $argv[1] === "--help"):
        return Help;
    case array_key_exists("r", getopt("r:")):
        return SingleLine;
    case file_exists($argv[$argc - 1]) :
        return File;
    default:
        return Help; }
}

function run($argc, $argv, $state) { switch (mode($argc, $argv))
{
    case Interactive: welcome(config($state, "formatter")->getOrElse(colours)); repl($state); break;
    case Help: help(); break;
    case File: fromFile($argv[$argc - 1], $state); break;
    case SingleLine: evaluate(getopt("r:")["r"])->flatMap(andPrint)->run($state); break; }
}

function welcome($formatter)
{
    PrintLines(ImmList(
        "Welcome to " . $formatter()['purple']("phunkie") . " console.",
        "",
        "Type in expressions to have them evaluated.",
        ""
    ))->run();
}

function help()
{
    return PrintLines(ImmList(
        "Usage: phunkie [file|options|code]",
        "    phunkie        Starts Phunkie's repl",
        "    phunkie <file>",
        "    phunkie -r <code>",
        "",
        "  -r <code>        Run Phunkie <code> without using script tags",
        ""
    ))->run();
}

function fromFile($file, $state)
{
    require_once $file;
    echo "\n";
}