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

use function Phunkie\Functions\function1\compose;
use function Phunkie\Functions\functor\fmap;
use Phunkie\Types\Pair;
use function PhunkieConsole\IO\Lens\variableLens;
use PhunkieConsole\Result\PrintableResult;

class TypeCommand
{
    private $input;

    public function __construct(string $input)
    {
        $this->input = $input;
    }

    public function run($someLongAndReservedPhunkieConsoleState): Pair
    {
        $someLongAndReservedPhunkieConsoleType = "";
        $someLongAndReservedPhunkieConsoleExpressionResult = null;
        $someLongAndReservedPhunkieConsoleExpression = trim(substr($this->input, strpos($this->input, " ") + 1));
        foreach (variableLens()->get($someLongAndReservedPhunkieConsoleState)->iterator() as
                 $someLongAndReservedPhunkieConsoleKey => $someLongAndReservedPhunkieConsoleVal) {
            $$someLongAndReservedPhunkieConsoleKey = $someLongAndReservedPhunkieConsoleVal;
        }
        eval("\$someLongAndReservedPhunkieConsoleExpressionResult = $someLongAndReservedPhunkieConsoleExpression;");
        eval("\$someLongAndReservedPhunkieConsoleType = Phunkie\\Functions\\show\\showType(\$someLongAndReservedPhunkieConsoleExpressionResult);");

        if (is_callable($someLongAndReservedPhunkieConsoleExpressionResult)) {
            $someLongAndReservedPhunkieConsoleType = $this->normaliseCallables($someLongAndReservedPhunkieConsoleExpressionResult);
        }
        return Pair($someLongAndReservedPhunkieConsoleState, Nel(Success(new PrintableResult(Pair(None, $someLongAndReservedPhunkieConsoleType)))));
    }

    private function normaliseCallables(callable $f) {
        $reflection = new \ReflectionFunction($f);

        $callableType = ImmList(...$reflection->getParameters())->map(
            function ($parameter) { return $parameter->getType(); }
        )->map("ucFirst")->mkString(" => ");

        return (compose(Option, fmap("ucFirst"), fmap(function($s) use ($callableType) {
            return $callableType . " => " . $s;
        })))($reflection->getReturnType())->getOrElse($callableType);
    }
}