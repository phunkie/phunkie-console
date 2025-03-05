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
use PhpParser\Node\Arg;
use PhpParser\Node\Expr\Array_;
use PhpParser\Node\Expr\ArrayDimFetch;
use PhpParser\Node\Expr\Assign;
use PhpParser\Node\Expr\BinaryOp;
use PhpParser\Node\Expr\ConstFetch;
use PhpParser\Node\Expr\FuncCall;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Expr\New_;
use PhpParser\Node\Expr\PropertyFetch;
use PhpParser\Node\Expr\Variable;
use PhpParser\Node\Scalar\DNumber;
use PhpParser\Node\Scalar\LNumber;
use PhpParser\Node\Scalar\String_;
use PhpParser\Node\Stmt;
use PhpParser\Node\Stmt\Expression as Expr;
use Phunkie\Types\ImmList;
use Phunkie\Types\ImmMap;
use Phunkie\Types\Pair;
use Phunkie\Utils\Trampoline\Done;
use Phunkie\Utils\Trampoline\More;
use PhunkieConsole\Result\ClassDeclarationResult;
use PhunkieConsole\Result\FunctionDeclarationResult;
use PhunkieConsole\Result\PrintableResult;
use PhunkieConsole\Result\VariableAssignmentResult;
use function Phunkie\Functions\immlist\concat;
use function PhunkieConsole\IO\Lens\getVariableValue;
use function PhunkieConsole\IO\Lens\updateVariable;
use function PhunkieConsole\IO\Lens\variableLens;

const value = "PhunkieConsole\\Compiler\\value";


function nodeIsNull(Node $node): bool
{
    return ($node instanceof ConstFetch && strtolower($node->name->name) == "null");
}

function value($state, $node, $code)
{
    switch(true) {
        case $node instanceof FuncCall:
            return funcCall($state, $node, $code);

        case $node instanceof ConstFetch:
            switch(true) {
                case $node->name->name === "true": return true;
                case $node->name->name === "false": return false;
                case $node->name->name === "null": return null;
                case !defined($node->name->name):
                    trigger_error("Use of undefined constant {$node->name->parts[0]}", E_NOTICE);
                    return $node->name->name;
                default: return eval("return {$node->name->name};");
            }
            break;
        case $node instanceof Array_:
            $array = [];
            foreach ($node->items as $arrayItem) {
                if ($arrayItem->key == null) {
                    $array[] = value($state, $arrayItem->value, $code);
                } else {
                    $array[value($state, $arrayItem->key, $code)] = value($state, $arrayItem->value, $code);
                }
            }
            return $array;
        case $node instanceof LNumber:
        case $node instanceof DNumber:
        case $node instanceof String_:
            return $node->value;
        case $node instanceof New_:
            $className = $node->class->name;
            $args = args($state, $node->args, $code);
            return new $className(...$args);
        case $node instanceof Variable:
            return getVariableValue($state, $node->name);
            break;
        case $node instanceof Expr:
            return expr($state, $node, $code);
            break;
        default:
            print_r($node);
            exit;
    }
}

function expr($someLongAndReservedPhunkieConsoleState, Node $someLongAndReservedPhunkieConsoleNode,
              $someLongAndReservedPhunkieConsoleCode)
{
    $someLongAndReservedPhunkieConsoleLocalVariable = null;
    foreach (variableLens()->get($someLongAndReservedPhunkieConsoleState)->iterator() as
             $someLongAndReservedPhunkieConsoleKey => $someLongAndReservedPhunkieConsoleVal) {
        $$someLongAndReservedPhunkieConsoleKey = $someLongAndReservedPhunkieConsoleVal;
    }
    eval("\$someLongAndReservedPhunkieConsoleLocalVariable = " .
        substr($someLongAndReservedPhunkieConsoleCode,
            $someLongAndReservedPhunkieConsoleNode->getAttribute("startFilePos"),
            $someLongAndReservedPhunkieConsoleNode->getAttribute("endFilePos") -
            $someLongAndReservedPhunkieConsoleNode->getAttribute("startFilePos") + 1) . ";"
    );
    return $someLongAndReservedPhunkieConsoleLocalVariable;
}

function binaryOp($state, $code, $node)
{
    $operation = $node->getOperatorSigil();
    $left = $node->left;
    $right = $node->right;

    if ($left instanceof BinaryOp) {
        $left = binaryOp($state, $code, $left);
    } else {
        $left = $left->value;
    }

    if ($right instanceof BinaryOp) {
        $right = binaryOp($state, $code, $right);
    } else {
        $right = $right->value;
    }

    return match($operation) {
        '+' => $left + $right,
        '-' => $left - $right,
        '*' => $left * $right,
        '/' => $left / $right,
        '%' => $left % $right,
        '**' => $left ** $right,
        '||' => $left || $right,
        '&&' => $left && $right,
        '??' => $left ?? $right,
        '==' => $left == $right,
        '!=' => $left != $right,
        '===' => $left === $right,
        '!==' => $left !== $right,
        '<' => $left < $right,
        '<=' => $left <= $right,
        '>' => $left > $right,
        '>=' => $left >= $right,
        '<<' => $left << $right,
        '>>' => $left >> $right,
        '&' => $left & $right,
        '|' => $left | $right,
        '^' => $left ^ $right,
        'xor' => $left xor $right,
        'or' => $left or $right,
        'and' => $left and $right,
        '<=>' => $left <=> $right,
        '.' => $left . $right,

        default => throw new \Exception("Unknown operator: " . $operation)
    };
}

function propertyFetch($state, $code, $statement)
{
    $caller = match (true) {
        $statement->var instanceof FuncCall => funcCall($state, $statement->var, $code),
        $statement->var instanceof PropertyFetch => propertyFetch($state, $code, $statement->var),
        $statement->var instanceof MethodCall => methodCall($state, $code, $statement->var),
        default => getVariableValue($state, $statement->var->name),
    };
    return $caller->{$statement->name};
}

function methodCall($state, $code, $statement)
{
    switch(true) {
        case $statement->var instanceof Variable:
            $caller = variable($state, $statement->var);
            return $caller->_2->{$statement->name}(...args($state, $statement->args, $code));
        case $statement->var instanceof MethodCall:
            $caller = methodCall($state, $code, $statement->var);
            return $caller->{$statement->name}(...args($state, $statement->args, $code));
        case $statement->var instanceof PropertyFetch:
            $caller = propertyFetch($state, $code, $statement->var);
            return $caller->{$statement->name}(...args($state, $statement->args, $code));
        default:
            $caller = value($state, $statement->var, $code);
            return $caller->{$statement->name}(...args($state, $statement->args, $code));
    }
}

function name($node, $var = '$'): string
{
    return match (get_class($node)) {
        PropertyFetch::class => $var . name($node->var, "") . $node->name,
        ArrayDimFetch::class => name($node->var, "") . '[' . $node->dim->value . ']',
        default => $node->name,
    };
}

function variable($state, $node): Pair
{
    return Pair(generateVarName($state), getVariableValue($state, $node->name));
}

function funcCall($state, FuncCall $node, $code)
{
    if ($node->name instanceof Variable) {
        return call_user_func_array(getVariableValue($state, $node->name->name), array_map(function($e) use ($state, $code) {
            return $e;
        }, args($state, $node->args, $code)));
    }
    if (isset($node->name->parts)) {
        $functionName = "\\" . ltrim(implode("\\", $node->name->parts), "\\");
        if (!function_exists($functionName)) {
            trigger_error("Call to undefined function " . ltrim($functionName,"\\") . "() in console", E_USER_WARNING);
        }
        return call_user_func_array($functionName, array_map(function ($e) use ($state, $code) {
            return $e;
        }, args($state, $node->args, $code)));
    } else {
        $someLongAndReservedPhunkieVar = null;
        eval('$someLongAndReservedPhunkieVar = ' . substr($code, $node->getAttribute("startFilePos"), $node->getAttribute("endFilePos")));
        return $someLongAndReservedPhunkieVar;
    }
}

function args($state, $args, $code): array
{
    $arguments = [];
    foreach ($args as $argument) {
        $xs = arg($state, $argument, $code);
        if (isVariadic($argument, $code)) {
            $arguments = array_merge($arguments, $xs);
            continue;
        }
        $arguments[] = $xs;
    }

    return $arguments;
}

function arg($state, $argument, $code)
{
    return value($state, $argument->value, $code);
}

function isVariadic(Arg $argument, $code)
{
    return strpos(substr($code, $argument->getAttribute('startFilePos')), '...') === 0;
}

function evaluateNode(ImmMap $someLongAndReservedPhunkieConsoleState, Node $someLongAndReservedPhunkieConsoleNode, string $someLongAndReservedPhunkieConsoleCode): Pair
{
    $someLongAndReservedPhunkieConsoleLocalVariable = null;
    $someLongAndReservedPhunkieConsoleVariablesBefore = [];
    $someLongAndReservedPhunkieConsoleFunctionsBefore = get_defined_functions()["user"];
    $someLongAndReservedPhunkieConsoleClassesBefore = get_declared_classes();
    $someLongAndReservedPhunkieConsoleResult = ImmList();

    foreach (variableLens()->get($someLongAndReservedPhunkieConsoleState)->iterator() as
             $someLongAndReservedPhunkieConsoleKey => $someLongAndReservedPhunkieConsoleVal) {
        $$someLongAndReservedPhunkieConsoleKey = $someLongAndReservedPhunkieConsoleVal;
        $someLongAndReservedPhunkieConsoleVariablesBefore[$someLongAndReservedPhunkieConsoleKey] = $someLongAndReservedPhunkieConsoleVal;
    }
    unset($someLongAndReservedPhunkieConsoleKey, $someLongAndReservedPhunkieConsoleVal);
    $someLongAndReservedPhunkieConsoleClassesBefore = get_declared_classes();

    ob_start();
    if ($someLongAndReservedPhunkieConsoleNode instanceof Expr) {
        eval("\$someLongAndReservedPhunkieConsoleLocalVariable = " .
            substr($someLongAndReservedPhunkieConsoleCode,
                $someLongAndReservedPhunkieConsoleNode->getAttribute("startFilePos"),
                $someLongAndReservedPhunkieConsoleNode->getAttribute("endFilePos") -
                $someLongAndReservedPhunkieConsoleNode->getAttribute("startFilePos") + 1) . ";"
        );
    } elseif ($someLongAndReservedPhunkieConsoleNode instanceof Stmt) {
        eval(substr($someLongAndReservedPhunkieConsoleCode,
                $someLongAndReservedPhunkieConsoleNode->getAttribute("startFilePos"),
                $someLongAndReservedPhunkieConsoleNode->getAttribute("endFilePos") -
                $someLongAndReservedPhunkieConsoleNode->getAttribute("startFilePos") + 1) . ";"
        );
    }
    if ($someLongAndReservedPhunkieConsoleBuffer = ob_get_contents()) {
        $someLongAndReservedPhunkieConsoleResult = concat(
            $someLongAndReservedPhunkieConsoleResult,
            Success(new PrintableResult(Pair('echo', $someLongAndReservedPhunkieConsoleBuffer))));
    }
    unset($someLongAndReservedPhunkieConsoleBuffer);
    ob_end_clean();

    $someLongAndReservedPhunkieConsoleVariablesAfter = get_defined_vars();
    $someLongAndReservedPhunkieConsoleFunctionsAfter = get_defined_functions()["user"];
    $someLongAndReservedPhunkieConsoleClassesAfter = get_declared_classes();

    unset($someLongAndReservedPhunkieConsoleVariablesAfter["someLongAndReservedPhunkieConsoleState"]);
    unset($someLongAndReservedPhunkieConsoleVariablesAfter["someLongAndReservedPhunkieConsoleNode"]);
    unset($someLongAndReservedPhunkieConsoleVariablesAfter["someLongAndReservedPhunkieConsoleCode"]);
    unset($someLongAndReservedPhunkieConsoleVariablesAfter["someLongAndReservedPhunkieConsoleFunctionsBefore"]);
    unset($someLongAndReservedPhunkieConsoleVariablesAfter["someLongAndReservedPhunkieConsoleClassesBefore"]);
    unset($someLongAndReservedPhunkieConsoleVariablesAfter["someLongAndReservedPhunkieConsoleResult"]);
    unset($someLongAndReservedPhunkieConsoleVariablesAfter["someLongAndReservedPhunkieConsoleVariablesBefore"]);
    unset($someLongAndReservedPhunkieConsoleVariablesAfter["someLongAndReservedPhunkieConsoleLocalVariable"]);

    foreach (array_udiff_assoc($someLongAndReservedPhunkieConsoleVariablesAfter, $someLongAndReservedPhunkieConsoleVariablesBefore,
        function($after, $before) {
            if ($after === $before) return 0;
            return -1;
        }) as
             $var => $value) {
        $someLongAndReservedPhunkieConsoleState = updateVariable($var, $value)->run($someLongAndReservedPhunkieConsoleState);
        $someLongAndReservedPhunkieConsoleResult = concat(
            $someLongAndReservedPhunkieConsoleResult,
            Success(new VariableAssignmentResult(Pair($var, $value))));
    }

    foreach (array_udiff_assoc($someLongAndReservedPhunkieConsoleFunctionsAfter, $someLongAndReservedPhunkieConsoleFunctionsBefore, function($a, $b) { return 0; }) as $function) {
        $someLongAndReservedPhunkieConsoleResult = concat(
            $someLongAndReservedPhunkieConsoleResult,
            Success(new FunctionDeclarationResult($function)));
    }

    foreach (array_udiff_assoc($someLongAndReservedPhunkieConsoleClassesAfter, $someLongAndReservedPhunkieConsoleClassesBefore,
        function($a, $b) {
            return 0;
        }) as $class) {
        if (strpos(ltrim($class, "\\"), "Phunkie") !== 0) {
            if(strpos($class, "class@anonymous") === 0) {
                $class = get_parent_class($someLongAndReservedPhunkieConsoleLocalVariable) ? "AnonymousClass < " . get_parent_class($someLongAndReservedPhunkieConsoleLocalVariable) : "AnonymousClass";
            }
            $someLongAndReservedPhunkieConsoleResult = concat(
                $someLongAndReservedPhunkieConsoleResult,
                Success(new ClassDeclarationResult($class)));
        }
    }

    if (!is_null($someLongAndReservedPhunkieConsoleLocalVariable)) {
        if (!($someLongAndReservedPhunkieConsoleNode?->expr instanceof Assign)) {
            $varName = generateVarName($someLongAndReservedPhunkieConsoleState);
            $someLongAndReservedPhunkieConsoleState = updateVariable($varName, $someLongAndReservedPhunkieConsoleLocalVariable)->run($someLongAndReservedPhunkieConsoleState);

            $someLongAndReservedPhunkieConsoleResult = concat(
                $someLongAndReservedPhunkieConsoleResult,
                Success(new VariableAssignmentResult(Pair($varName, $someLongAndReservedPhunkieConsoleLocalVariable))));
        }
    }

    return Pair($someLongAndReservedPhunkieConsoleState, $someLongAndReservedPhunkieConsoleResult);
}

function generateVarName(ImmMap $state): string
{
    $nextAvailable = function ($numbers) use (&$nextAvailable) {
        return match (true) {
            $numbers->length === 0 => new Done(0),
            $numbers->length > 1 && $numbers->head == ($numbers->tail->head - 1) => new More(fn() => $nextAvailable($numbers->tail)),
            $numbers->length === 1 ||
            $numbers instanceof ImmList => new Done($numbers->head + 1),
            default => throw new \InvalidArgumentException("Invalid input". print_r($numbers, true))
        };
    };

    $vars = variableLens()->get($state);

    $numbers = ImmList(...$vars->keys())
        ->filter(fn ($varName) => preg_match("/var(\d+)/", $varName))
        ->map(fn ($varName) => (int)substr($varName, 3));

    $next = $nextAvailable($numbers)->run();

    return 'var' . $next;
}
