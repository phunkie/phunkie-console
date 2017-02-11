<?php

namespace PhunkieConsole\Result;

use function Phunkie\Functions\show\showType;
use function Phunkie\Functions\show\showValue;

class ShowableResult extends Result
{
    public function output(callable $formatter): string
    {
        return $formatter()['magenta']($formatter()['bold'](showType($this->getResult()))) .
            " = " . $formatter()['bold'](showValue($this->getResult()));
    }
}