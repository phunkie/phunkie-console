<?php

namespace PhunkieConsole\Result;

use function Phunkie\Functions\show\showType;
use function Phunkie\Functions\show\showValue;

class VariableAssignmentResult extends Result
{
    public function output(callable $formatter): string
    {
        $varName = $this->getResult()->_1;
        $varValue = $this->getResult()->_2;

        return $formatter()['bold']("\$" . $varName) . ": " . $formatter()['magenta']($formatter()['bold'](showType($varValue))) .
        " = " . $formatter()['bold'](showValue($varValue));
    }
}