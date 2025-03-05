<?php

/*
 * This file is part of PhunkieConsole, a REPL to support your Phunkie development.
 *
 * (c) Marcello Duarte <marcello.duarte@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PhunkieConsole\Result;

class FunctionDeclarationResult extends Result
{
    public function output(callable $formatter): string
    {
        return ($formatter())['magenta']("defined function " . $this->getResult());
    }
}