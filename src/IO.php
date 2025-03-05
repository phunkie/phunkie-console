<?php

/*
 * This file is part of PhunkieConsole, a REPL to support your Phunkie development.
 *
 * (c) Marcello Duarte <marcello.duarte@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PhunkieConsole\IO;

use function Phunkie\Functions\io\io;
use Phunkie\Types\ImmList;
use Phunkie\Cats\IO as IOUnit;
use Phunkie\Cats\IO as IOString;

function ReadLine($prompt): IOString
{
    return io(function () use ($prompt) {
        echo $prompt;
        $input = \readline("");
        if (!empty($input)) {
            readline_add_history($input);
        }
        $line = rtrim($input, "\n");
        if ($input === false || $input === "exit") {
            echo ($input ? "" : "\n") . "Stay phunkie! \\o\n";
            exit(0);
        }
        return $line;
    });
}

function PrintLn($message): IOUnit
{
    return io(function() use ($message) { print($message . "\n"); });
}

function PrintNothing(): IOUnit
{
    return io(function() { print(""); });
}

function PrintLines(ImmList $lines): IOUnit
{
    return io(function() use ($lines) { $lines->map(function($line) { print($line . "\n"); }); });
}