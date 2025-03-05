<?php

/*
 * This file is part of PhunkieConsole, a REPL to support your Phunkie development.
 *
 * (c) Marcello Duarte <marcello.duarte@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use PhpParser\Node;
use Phunkie\Types\ImmMap;
use PhunkieConsole\Compiler\CompiledNode;

require_once __DIR__ . "/Block.php";
require_once __DIR__ . "/Result/Result.php";
require_once __DIR__ . "/Result/functions.php";
require_once __DIR__ . "/App.php";
require_once __DIR__ . "/Compiler/functions.php";
require_once __DIR__ . "/Colours.php";
require_once __DIR__ . "/Compiler/EvaluationStrategy/functions.php";
require_once __DIR__ . "/Compiler.php";
require_once __DIR__ . "/IO.php";
require_once __DIR__ . "/Lens.php";
require_once __DIR__ . "/Parser.php";
require_once __DIR__ . "/Command.php";
require_once __DIR__ . "/Repl.php";

function Compiled(ImmMap $state, Node $node, string $code): callable
{
    $n = new CompiledNode($state, $node, $code);
    return fn($result) => fn($resultFactory) => fn($evaluateStrategy)
    => $n->compile($result)($resultFactory)($evaluateStrategy);
}