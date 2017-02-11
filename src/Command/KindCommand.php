<?php

namespace PhunkieConsole\Command;

use function Phunkie\Functions\show\showKind;
use Phunkie\Types\Pair;
use PhunkieConsole\Result\PrintableResult;

class KindCommand
{
    private $input;

    public function __construct(string $input)
    {
        $this->input = $input;
    }

    public function run($state): Pair
    {
        $type = trim(substr($this->input, strpos($this->input, " ") + 1));
        $kindToShow = new PrintableResult(Pair(None(), showKind($type)->getOrElse("type $type not in scope")));
        return Pair($state, Nel(Success($kindToShow)));
    }
}