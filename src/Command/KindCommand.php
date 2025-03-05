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