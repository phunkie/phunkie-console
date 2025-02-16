<?php

namespace PhunkieConsole\PhpCompiler;

use PhpParser\Node\Arg;
use PhpParser\Node\Expr\ArrayDimFetch;
use Phunkie\Cats\State;
use function Phunkie\Functions\immlist\concat;
use function Phunkie\PatternMatching\Referenced\ListWithTail;
use function Phunkie\PatternMatching\Referenced\ListNoTail;
use Phunkie\Types\ImmMap;
use Phunkie\Types\ImmList;
use Phunkie\Types\Nil;
use Phunkie\Types\Cons;
use Phunkie\Types\Pair;
use Phunkie\Types\Unit;
use Phunkie\Utils\Trampoline\Done;
use Phunkie\Utils\Trampoline\More;
use Phunkie\Utils\Trampoline\Trampoline;
use function PhunkieConsole\IO\Lens\getVariableValue;
use function PhunkieConsole\IO\Lens\updateVariable;
use function PhunkieConsole\IO\Lens\variableLens;
use PhunkieConsole\Result\ClassDeclarationResult;
use PhunkieConsole\Result\FunctionDeclarationResult;
use PhunkieConsole\Result\NoResult;
use PhunkieConsole\Result\PrintableResult;

use PhunkieConsole\Result\VariableAssignmentResult;
use PhpParser\Node;
// use PhpParser\Node\Expr;
use PhpParser\Node\Stmt\Expression as Expr;
use PhpParser\Node\Expr\Assign;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Expr\New_;
use PhpParser\Node\Expr\PropertyFetch;
use PhpParser\Node\Expr\Variable;
use PhpParser\Node\Scalar\LNumber;
use PhpParser\Node\Scalar\DNumber;
use PhpParser\Node\Scalar\String_;

use PhpParser\Node\Expr\Array_;
use PhpParser\Node\Expr\ConstFetch;
use PhpParser\Node\Expr\BinaryOp;
use PhpParser\Node\Expr\Exit_;
use PhpParser\Node\Expr\FuncCall;
use PhpParser\Node\Expr\Print_;
use PhpParser\Node\Stmt;
use PhpParser\Node\Stmt\Class_;
use PhpParser\Node\Stmt\Echo_;
use PhpParser\Node\Stmt\Function_;
use PhpParser\Node\Stmt\Nop;

const compile = "PhunkieConsole\\PhpCompiler\\compile";
const value = "PhunkieConsole\\PhpCompiler\\value";

function compile($statements): State
{
    $code = "<?php $statements->_1; ?>";
    $nodes = $statements->_2;
    return new State(function($state) use ($nodes, $code): Pair {
        if ($nodes === []) {
            return Pair($state, Nel(Success(new NoResult(""))));
        }
        return doCompile($state, $nodes, $code);
    });
}

function doCompile($state, $nodes, $code): Pair
{
    // echo "Nodes: " . print_r($nodes, true) . "\n";
    $result = Nil();
    foreach ($nodes as $node) {
        if ($node instanceof Expr) {
            if (!$node instanceof BinaryOp) {
                $node = $node?->expr;
            }
        }
        $nodeType = match(true) {
            $node instanceof BinaryOp => 'binary_op',
            $node instanceof Nop => 'nop',
            $node instanceof Echo_ => 'echo',
            $node instanceof Print_ => 'print',
            $node instanceof Exit_ => 'exit',
            $node instanceof FuncCall && isset($node->name->parts) && 
                in_array($node->name->parts[0], ['var_dump', 'print_r', 'var_export']) => 'debug_func',
            $node instanceof FuncCall => 'func_call',
            $node instanceof Assign => 'assign',
            $node instanceof Variable => 'variable',
            $node instanceof MethodCall => 'method_call',
            $node instanceof Class_ => 'class',
            $node instanceof Function_ => 'function',
            $node instanceof PropertyFetch => 'property_fetch',
            $node instanceof ConstFetch => 'const_fetch',
            $node instanceof Stmt => 'stmt',
            $node instanceof Expr && !nodeIsNull($node) => 'expr',
            default => 'default'
        };

        $result = match($nodeType) {
            'binary_op' => (function()use(&$state, $code, $node, $result){
                $binaryOp = binaryOp($state, $code, $node->getOperatorSigil(), $node->left, $node->right);
                return concat($result, Success(new VariableAssignmentResult(Pair(generateVarName($state), $binaryOp))));
            })(),
            'nop' => $result,
                
            'echo' => concat($result, Success(new PrintableResult(Pair("echo",
                    implode('', array_map(fn($expr) => value($state, $expr, $code), $node->exprs)))))),
                
            'print' => concat($result, Success(new PrintableResult(Pair("print", value($state, $node->expr, $code))))),
                
            'exit' => concat($result, Success(new PrintableResult(Pair("die", value($state, $node->expr, $code))))),
                
            'debug_func' => concat($result, Success(new PrintableResult(Pair($node->name->parts[0],
                    value($state, $node->args[0]->value, $code))))),
                
            'func_call' => (function() use ($state, $node, $code, $result) {
                $funcCallResult = funcCall($state, $node, $code);
                $varName = '';
                if (!$funcCallResult instanceof Unit) {
                    $varName = generateVarName($state);
                    $state = updateVariable($varName, $funcCallResult)->run($state);
                }
                return concat($result, Success(new VariableAssignmentResult(Pair($varName, $funcCallResult))));
            })(),
                
            'assign' => (function() use (&$state, $node, $code, $result) {
                $state = updateVariable(name($node->var), value($state, $node->expr, $code))->run($state);
                return concat($result, Success(new VariableAssignmentResult(Pair(name($node->var),
                    value($state, $node->expr, $code)))));
            })(),
                
            'variable' => (function() use ($state, $node, $result) {
                $variable = variable($state, $node);
                $state = updateVariable($variable->_1, $variable->_2)->run($state);
                return concat($result, Success(new VariableAssignmentResult($variable)));
            })(),
                
            'method_call' => (function() use ($state, $code, $node, $result) {
                $methodCallResult = methodCall($state, $code, $node);
                $varName = '';
                if (!$methodCallResult instanceof Unit) {
                    $varName = generateVarName($state);
                    $state = updateVariable($varName, $methodCallResult)->run($state);
                }
                return concat($result, Success(new VariableAssignmentResult(Pair($varName, $methodCallResult))));
            })(),
                
            'class' => (function() use ($node, $code, $result) {
                if (class_exists($node->name)) {
                    trigger_error("Cannot declare class {$node->name}, because the name is already in use in console", E_USER_ERROR);
                }
                eval(substr($code, $node->getAttribute("startFilePos"),
                    $node->getAttribute("endFilePos") - 5));
                return concat($result, Success(new ClassDeclarationResult($node->name)));
            })(),
                
            'function' => (function() use ($node, $code, $result) {
                if (function_exists($node->name)) {
                    trigger_error("Cannot redeclare " . ltrim($node->name,"\\") . "() (previously declared in console)", E_USER_ERROR);
                }
                eval(substr($code, $node->getAttribute("startFilePos"),
                    $node->getAttribute("endFilePos") + 1));
                return concat($result, Success(new FunctionDeclarationResult($node->name)));
            })(),
                
            'property_fetch' => (function() use ($state, $code, $node, $result) {
                $funcCallResult = propertyFetch($state, $code, $node);
                $varName = '';
                if (!$funcCallResult instanceof Unit) {
                    $varName = generateVarName($state);
                    $state = updateVariable($varName, $funcCallResult)->run($state);
                }
                return concat($result, Success(new VariableAssignmentResult(Pair($varName, $funcCallResult))));
            })(),
                
            'const_fetch' => (function() use ($state, $node, $result) {
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
                return concat($result, Success(new VariableAssignmentResult(Pair(generateVarName($state), $constant))));
            })(),
                
            'stmt', 'expr' => (function() use (&$state, $node, $code, $result) {
                $stmt = evaluateNode($state, $node, $code);
                $state = $stmt->_1;
                return concat($result, $stmt->_2);
            })(),
                
            'default' => (function() use ($state, $node, $code, $result) {
                $value = value($state, $node, $code);
                $varName = '';
                if (!$value instanceof Unit) {
                    $varName = generateVarName($state);
                    $state = updateVariable($varName, $value)->run($state);
                }
                return concat($result, Success(new VariableAssignmentResult(Pair($varName, $value))));
            })()
        };
    }
    return Pair($state, $result);
}

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

function binaryOp($state, $code, $operation, $left, $right)
{
    if ($left instanceof BinaryOp) {
        $left = binaryOp($state, $code, $left->getOperatorSigil(), $left->left, $left->right);
    } else {
        $left = $left->value;
    }

    if ($right instanceof BinaryOp) {
        $right = binaryOp($state, $code, $right->getOperatorSigil(), $right->left, $right->right);
    } else {
        $right = $right->value;
    }

    $value = match($operation) {
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
    
    return $value;
}

function propertyFetch($state, $code, $statement)
{
    switch (true) {
        case $statement->var instanceof FuncCall:
            $caller = funcCall($state, $statement->var, $code);
            break;
        case $statement->var instanceof PropertyFetch:
            $caller = propertyFetch($state, $code, $statement->var);
            break;
        case $statement->var instanceof MethodCall:
            $caller = methodCall($state, $code, $statement->var);
            break;
        default:
            $caller = getVariableValue($state, $statement->var->name);
            break;
    }
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

function name($node, $var = '$')
{
    switch(get_class($node))
    {
        case PropertyFetch::class :
            return $var . name($node->var, "") . $node->name;
        case ArrayDimFetch::class :
            return name($node->var, "") . '[' . $node->dim->value . ']';
        default:
            return $node->name;
    }
}

function variable($state, $node)
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

function generateVarName($state)
{
    $nextAvailable = function ($numbers) use (&$nextAvailable) {
        return match (true) {
            $numbers->length === 0 => new Done(0),
            $numbers->length === 1 => new Done($numbers->head + 1),
            $numbers->length > 1 && $numbers->head == ($numbers->tail->head - 1) => new More(fn() => $nextAvailable($numbers->tail)),
            $numbers instanceof ImmList => new Done($numbers->head + 1),
            default => throw new \InvalidArgumentException("Invalid input". print_r($numbers, true))
        };
    };

    $vars = variableLens()->get($state);
    $keys = $vars->keys();

    $numbers = ImmList(...$vars->keys())
        ->filter(fn ($varName) => preg_match("/var(\d+)/", $varName))
        ->map(fn ($varName) => (int)substr($varName, 3));
    
    $next = $nextAvailable($numbers)->run();
    
    return 'var' . $next;
}
