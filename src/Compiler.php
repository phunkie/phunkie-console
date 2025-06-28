<?php

/*
 * This file is part of PhunkieConsole, a REPL to support your Phunkie development.
 *
 * (c) Marcello Duarte <marcello.duarte@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PhunkieConsole\PhpCompiler;

use Phunkie\Cats\State;
use Phunkie\Types\Pair;
use function PhunkieConsole\Compiler\EvaluationStrategy\assignStrategy;
use function PhunkieConsole\Compiler\EvaluationStrategy\binaryOpStrategy;
use function PhunkieConsole\Compiler\EvaluationStrategy\classStrategy;
use function PhunkieConsole\Compiler\EvaluationStrategy\constantFetchingStrategy;
use function PhunkieConsole\Compiler\EvaluationStrategy\constantAssignStrategy;
use function PhunkieConsole\Compiler\EvaluationStrategy\debugFuncStrategy;
use function PhunkieConsole\Compiler\EvaluationStrategy\defaultExpressionStrategy;
use function PhunkieConsole\Compiler\EvaluationStrategy\echoStrategy;
use function PhunkieConsole\Compiler\EvaluationStrategy\evaluateExpressionStrategy;
use function PhunkieConsole\Compiler\EvaluationStrategy\funcCallStrategy;
use function PhunkieConsole\Compiler\EvaluationStrategy\functionStrategy;
use function PhunkieConsole\Compiler\EvaluationStrategy\methodCallStrategy;
use function PhunkieConsole\Compiler\EvaluationStrategy\printStrategy;
use function PhunkieConsole\Compiler\EvaluationStrategy\propertyFetchingStrategy;
use function PhunkieConsole\Compiler\EvaluationStrategy\variableStrategy;
use PhunkieConsole\Result\NoResult;

use PhpParser\Node\Stmt\Expression as Expr;
use PhpParser\Node\Expr\Assign;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Expr\PropertyFetch;
use PhpParser\Node\Expr\Variable;

use PhpParser\Node\Expr\BinaryOp;
use PhpParser\Node\Expr\ConstFetch;
use PhpParser\Node\Expr\Exit_;
use PhpParser\Node\Expr\FuncCall;
use PhpParser\Node\Expr\Print_;
use PhpParser\Node\Stmt;
use PhpParser\Node\Stmt\Class_;
use PhpParser\Node\Stmt\Const_;
use PhpParser\Node\Stmt\Echo_;
use PhpParser\Node\Stmt\Function_;
use PhpParser\Node\Stmt\Nop;
use function PhunkieConsole\Compiler\nodeIsNull;
use function PhunkieConsole\Result\classDeclarationResultFactory;
use function PhunkieConsole\Result\functionDeclarationResultFactory;
use function PhunkieConsole\Result\printableResultFactory;
use function PhunkieConsole\Result\variableAssignmentResultFactory;

const compile = "PhunkieConsole\\PhpCompiler\\compile";
function compile($statements): State
{
    $code = "<?php $statements->_1; ?>";
    $nodes = $statements->_2;
    return new State(function($state) use ($nodes, $code): Pair {
        if ($nodes === []) {
            return Pair($state, Nel(Success(new NoResult(""))));
        }
        $result = Nil();
        foreach ($nodes as $node) {
            [$state, $result] = compileNode($state, $node, $code, $result);
        }
        return Pair($state, $result);
    });
}

function compileNode($state, $node, $code, $result): array
{
    if ($node instanceof Expr && get_class($node) !== BinaryOp::class) {
        $node = $node?->expr;
    }

    $compile = Compiled($state, $node, $code)($result);
    $compileAndAssign = $compile(variableAssignmentResultFactory());
    $compileAndPrint = $compile(printableResultFactory());
    $debugFunctions = ['var_dump', 'print_r', 'var_export'];
    $isDebugFunctionCall = fn ($n) => isset($n->name->parts[0]) && in_array($n->name->parts[0], $debugFunctions);

    return match(true) {
        $node instanceof BinaryOp => $compileAndAssign(binaryOpStrategy()),
        $node instanceof Nop => [$state, $result],
        $node instanceof Echo_ =>$compileAndPrint(echoStrategy()),
        $node instanceof Print_ =>$compileAndPrint(printStrategy("print")),
        $node instanceof Exit_ =>$compileAndPrint(printStrategy("die")),
        $node instanceof FuncCall && $isDebugFunctionCall($node) => $compileAndPrint(debugFuncStrategy()),
        $node instanceof FuncCall =>  $compileAndAssign(funcCallStrategy()),
        $node instanceof Assign => $compileAndAssign(assignStrategy()),
        $node instanceof Variable => $compileAndAssign(variableStrategy()),
        $node instanceof MethodCall => $compileAndAssign(methodCallStrategy()),
        $node instanceof Class_ => $compile(classDeclarationResultFactory())(classStrategy()),
        $node instanceof Function_ => $compile(functionDeclarationResultFactory())(functionStrategy()),
        $node instanceof PropertyFetch => $compileAndAssign(propertyFetchingStrategy()),
        $node instanceof ConstFetch => $compileAndAssign(constantFetchingStrategy()),
        $node instanceof Const_ => $compileAndAssign(constantAssignStrategy()),
        $node instanceof Stmt, $node instanceof Expr && !nodeIsNull($node) => $compileAndAssign(evaluateExpressionStrategy()),
        default =>  $compileAndAssign(defaultExpressionStrategy()),
    };
}
