<?php

/*
 * This file is part of PhunkieConsole, a REPL to support your Phunkie development.
 *
 * (c) Marcello Duarte <marcello.duarte@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PhunkieConsole\IO\Colours;

use const Phunkie\Functions\function1\identity;
const colours = "PhunkieConsole\\IO\\Colours\\colours";
const noColours = "PhunkieConsole\\IO\\Colours\\noColours";

function colours()
{
    $colours = [
        "boldRed" => function($message) use (&$colours) { return $colours['bold']($colours['red']($message)); },
        "red" => function($message) { return "\e[31m$message\e[0m"; },
        "blue" => function($message) { return "\e[34m$message\e[0m"; },
        "bold" => function($message) { return "\e[1m$message\e[0m"; },
        "cyan" => function($message) { return "\e[36m$message\e[0m"; },
        "green" => function($message) { return "\e[32m$message\e[0m"; },
        "magenta" => function($message) { return "\e[35m$message\e[0m"; },
        "purple" => function($message) { return "\e[38;5;57m$message\e[0m"; }
    ];
    return $colours;
}

function noColours()
{
    return [
        "boldRed" => identity,
        "red" => identity,
        "blue" => identity,
        "bold" => identity,
        "cyan" => identity,
        "magenta" => identity,
        "purple" => identity
    ];
}

