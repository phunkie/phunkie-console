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

abstract class Result
{
    private $result;

    public function __construct($result)
    {
        $this->result = $result;
    }

    abstract public function output(callable $formatter): string;

    public function getResult()
    {
        return $this->result;
    }
}
