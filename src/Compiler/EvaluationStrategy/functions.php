<?php

/*
 * This file is part of PhunkieConsole, a REPL to support your Phunkie development.
 *
 * (c) Marcello Duarte <marcello.duarte@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PhunkieConsole\Compiler\EvaluationStrategy;

use Phunkie\Types\Unit;
use function PhunkieConsole\IO\Lens\updateVariable;
use function PhunkieConsole\Compiler\binaryOp;
use function PhunkieConsole\Compiler\evaluateNode;
use function PhunkieConsole\Compiler\funcCall;
use function PhunkieConsole\Compiler\generateVarName;
use function PhunkieConsole\Compiler\methodCall;
use function PhunkieConsole\Compiler\name;
use function PhunkieConsole\Compiler\propertyFetch;
use function PhunkieConsole\Compiler\value;
use function PhunkieConsole\Compiler\variable;

function variableStrategy(): callable
{
    return function (&$state, $node, $code) {
        $variable = variable($state, $node);
        $state = updateVariable($variable->_1, $variable->_2)->run($state);
        return [$state, $variable];
    };
}

function binaryOpStrategy(): callable
{
    return function ($state, $node, $code) {
        $binaryOp = binaryOp($state, $code, $node);
        return [$state, Pair(generateVarName($state), $binaryOp)];
    };
}

function echoStrategy(): callable
{
    return function ($state, $node, $code) {
        return [$state, Pair("echo", implode('', array_map(fn($expr) => value($state, $expr, $code), $node->exprs)))];
    };
}

function printStrategy($method): callable
{
    return function ($state, $node, $code) use ($method) {
        return [$state, Pair($method, value($state, $node->expr, $code))];
    };
}

function debugFuncStrategy(): callable
{
    return function ($state, $node, $code) {
        return [$state, Pair($node->name->parts[0], value($state, $node->args[0]->value, $code))];
    };
}

function funcCallStrategy(): callable
{
    return function ($state, $node, $code) {
        $funcCallResult = funcCall($state, $node, $code);
        $varName = '';
        if (!$funcCallResult instanceof Unit) {
            $varName = generateVarName($state);
            $state = updateVariable($varName, $funcCallResult)->run($state);
        }
        return [$state, Pair($varName, $funcCallResult)];
    };
}

function assignStrategy(): callable
{
    return function (&$state, $node, $code) {
        $nome = name($node->var);
        $value = value($state, $node->expr, $code);
        $state = updateVariable($nome, $value)->run($state);
        return [$state, Pair($nome, $value)];
    };
}

function functionStrategy(): callable
{
    return function ($state,$node, $code) {
        if (function_exists($node->name)) {
            trigger_error("Cannot redeclare " . ltrim($node->name,"\\") . "() (previously declared in console)", E_USER_ERROR);
        }
        eval(substr($code, $node->getAttribute("startFilePos"),
            $node->getAttribute("endFilePos") + 1));
        return [$state, $node->name];
    };
}

function methodCallStrategy(): callable
{
    return function ($state, $node, $code) {
        $methodCallResult = methodCall($state, $code, $node);
        $varName = '';
        if (!$methodCallResult instanceof Unit) {
            $varName = generateVarName($state);
            $state = updateVariable($varName, $methodCallResult)->run($state);
        }
        return [$state, Pair($varName, $methodCallResult)];
    };
}

function classStrategy(): callable
{
    return function ($state, $node, $code) {
        if (class_exists($node->name)) {
            trigger_error("Cannot declare class {$node->name}, because the name is already in use in console", E_USER_ERROR);
        }
        eval(substr($code, $node->getAttribute("startFilePos"),
            $node->getAttribute("endFilePos") - 5));
        return [$state, $node->name];
    };
}

function propertyFetchingStrategy(): callable
{
    return function ($state, $node, $code) {
        $funcCallResult = propertyFetch($state, $code, $node);
        $varName = '';
        if (!$funcCallResult instanceof Unit) {
            $varName = generateVarName($state);
            $state = updateVariable($varName, $funcCallResult)->run($state);
        }
        return [$state, Pair($varName, $funcCallResult)];
    };
}

function constantFetchingStrategy(): callable
{
    return function($state, $node, $code) {
        $undefinedConstantHandler = function() use ($node) {
            trigger_error("Use of undefined constant {$node->name->name}", E_USER_NOTICE);
            return $node->name->name;
        };

        $constant = match(true) {
            $node->name->name === "true" => true,
            $node->name->name === "false" => false,
            $node->name->name === "null" => null,
            !defined($node->name->name) => $undefinedConstantHandler(),
            default => constant($node->name->name)
        };
        return [$state, Pair(generateVarName($state), $constant)];
    };
}

function constantAssignStrategy(): callable
{
    return function($state, $node, $code) {
        define($node->consts[0]->name->name, $node->consts[0]->value->value);
        return [$state, Pair($node->consts[0]->name->name, $node->consts[0]->value->value)];
    };
}

function evaluateExpressionStrategy(): callable
{
    return function($state, $node, $code) {
        $stmt = evaluateNode($state, $node, $code);
        return [$state, Pair($stmt->_1, $stmt->_2)];
    };
}

function defaultExpressionStrategy(): callable
{
    return function($state, $node, $code) {
        $value = value($state, $node, $code);
        $varName = '';
        if (!$value instanceof Unit) {
            $varName = generateVarName($state);
            $state = updateVariable($varName, $value)->run($state);
        }
        return [$state, Pair($varName, $value)];
    };
}
