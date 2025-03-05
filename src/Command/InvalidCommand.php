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

class InvalidCommand
{
    private $input;

    public function __construct(string $input)
    {
        $this->input = $input;
    }

    public function run($state)
    {
        return Pair($state, Nel(Failure(new \Error("Invalid command $this->input. Type :? for help"))));
    }
}