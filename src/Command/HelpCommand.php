<?php

/*
 * This file is part of PhunkieConsole, a REPL to support your Phunkie development.
 *
 * (c) Marcello Duarte <marcello.duarte@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PhunkieConsole\Command;

use Phunkie\Types\Pair;
use function PhunkieConsole\IO\Colours\colours;
use PhunkieConsole\Result\PrintableResult;

class HelpCommand
{
    private $input;

    public function __construct(string $input)
    {
        $this->input = $input;
    }

    public function run($state): Pair
    {
        return Pair($state, Nel(Success(new PrintableResult(Pair(None,
            colours()['green'](" Commands available from the prompt\n").
            "  :import <module>            loads module(s) and their dependents\n".
            "  :help, :h, :?               displays this list of commands\n".
            "  :exit                       exits the application\n".
            "  :type <expr>, :t            display the type of an expression\n".
            "  :kind <type>, :k            display the kind of a type\n" .
            "  :load <file>, :l            loads a php file\n")))));
    }
}