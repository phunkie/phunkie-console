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

    return match(true) {
        $node instanceof BinaryOp => Compiled($state, $node, $code)($result)(variableAssignmentResultFactory())(binaryOpStrategy()),
        $node instanceof Nop => [$state, $result],
        $node instanceof Echo_ => Compiled($state, $node, $code)($result)(printableResultFactory())(echoStrategy()),
        $node instanceof Print_ => Compiled($state, $node, $code)($result)(printableResultFactory())(printStrategy("print")),
        $node instanceof Exit_ => Compiled($state, $node, $code)($result)(printableResultFactory())(printStrategy("die")),
        $node instanceof FuncCall && isset($node->name->parts) &&
            in_array($node->name->parts[0], ['var_dump', 'print_r', 'var_export']) =>
            Compiled($state, $node, $code)($result)(printableResultFactory())(debugFuncStrategy()),
        $node instanceof FuncCall =>  Compiled($state, $node, $code)($result)(variableAssignmentResultFactory())(funcCallStrategy()),
        $node instanceof Assign => Compiled($state, $node, $code)($result)(variableAssignmentResultFactory())(assignStrategy()),
        $node instanceof Variable => Compiled($state, $node, $code)($result)(variableAssignmentResultFactory())(variableStrategy()),
        $node instanceof MethodCall => Compiled($state, $code, $node)($result)(variableAssignmentResultFactory())(methodCallStrategy()),
        $node instanceof Class_ => Compiled($state, $node, $code)($result)(classDeclarationResultFactory())(classStrategy()),
        $node instanceof Function_ => Compiled($state, $node, $code)($result)(functionDeclarationResultFactory())(functionStrategy()),
        $node instanceof PropertyFetch => Compiled($state, $node, $code)($result)(variableAssignmentResultFactory())(propertyFetchingStrategy()),
        $node instanceof ConstFetch => Compiled($state, $node, $code)($result)(variableAssignmentResultFactory())(constantFetchingStrategy()),
        $node instanceof Const_ => Compiled($state, $node, $code)($result)(variableAssignmentResultFactory())(constantAssignStrategy()),
        $node instanceof Stmt, $node instanceof Expr && !nodeIsNull($node) => Compiled($state, $node, $code)($result)(variableAssignmentResultFactory())(evaluateExpressionStrategy()),
        default =>  Compiled($state, $node, $code)($result)(variableAssignmentResultFactory())(defaultExpressionStrategy()),
    };
}
