<?php

/*
 * This file is part of PhunkieConsole, a REPL to support your Phunkie development.
 *
 * (c) Marcello Duarte <marcello.duarte@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PhunkieConsole\Compiler;

use PhpParser\Node;
use Phunkie\Types\ImmList;
use Phunkie\Types\ImmMap;
use Phunkie\Validation\Validation;
use function Phunkie\Functions\immlist\concat;

class CompiledNode /* <R> */
{
    public function __construct(
        public ImmMap $state,
        public Node $node,
        public string $code
    ) {
    }

    /**
     * @param ImmList<Validation> $result
     * @return callable : (ResultFactory) => (EvaluateStrategy) => [State, ImmList<Validation<E, R>>]
     */
    public function compile(ImmList $result): callable
    {
        return fn($resultFactory) => function ($evaluateStrategy) use ($result, $resultFactory) {
            [$s, $r] = $this->evaluate($evaluateStrategy);
            $this->state = $s;
            $a = $resultFactory($r);
            return [$this->state, concat($result, Success($a))];
        };
    }

    public function evaluate(callable $evaluate): mixed
    {
        return $evaluate($this->state, $this->node, $this->code);
    }
}
