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

use function Phunkie\Functions\show\showType;
use function Phunkie\Functions\show\showValue;
use Phunkie\Types\Unit;

class VariableAssignmentResult extends Result
{
    public function output(callable $formatter): string
    {
        $varName = $this->getResult()->_1;
        $varValue = $this->getResult()->_2;

        if ($varValue instanceof Unit) {
            return "";
        }
        return $formatter()['bold']("\$" . $varName) . ": " . $formatter()['magenta']($formatter()['bold'](showType($varValue))) .
        " = " . $formatter()['bold'](showValue($varValue));
    }
}