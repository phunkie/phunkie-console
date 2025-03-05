<?php

/*
 * This file is part of PhunkieConsole, a REPL to support your Phunkie development.
 *
 * (c) Marcello Duarte <marcello.duarte@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PhunkieConsole\Parser\Adapter\PhpParser;

use PhpParser\Parser;

class ParserAdapter
{
    private $adaptee;

    public function __construct(Parser $adaptee)
    {
        $this->adaptee = $adaptee;
    }

    public function parse(string $code)
    {
        $nodes = $this->adaptee->parse("<?php $code; ?>");

        if (!is_array($nodes)) {
            throw(new \ParseError("Syntax error, could not parse '$code'"));
        }

        return $nodes;
    }
}