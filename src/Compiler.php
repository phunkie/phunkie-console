<?php

namespace PhunkieConsole\PhpCompiler;

use PhpParser\Node\Expr\ArrayDimFetch;
use Phunkie\Cats\State;
use function Phunkie\Functions\immlist\concat;
use function Phunkie\PatternMatching\Referenced\ListWithTail;
use function Phunkie\PatternMatching\Referenced\ListNoTail;
use Phunkie\Types\ImmMap;
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
use PhpParser\Node\Expr;
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
    $result = Nil();
    foreach ($nodes as $node) {
        switch (true) {
            // Comments
            case $node instanceof Nop:
                continue;

            // Printable results
            case $node instanceof Echo_:
                $result = concat($result, Success(new PrintableResult(Pair("echo", value($state, $node->exprs[0], $code)))));
                break;
            case $node instanceof Print_:
                $result = concat($result, Success(new PrintableResult(Pair("print", value($state, $node->expr, $code)))));
                break;
            case $node instanceof Exit_:
                $result = concat($result, Success(new PrintableResult(Pair("die", value($state, $node->expr, $code)))));
                break;
            case $node instanceof FuncCall && isset($node->name->parts) && in_array($node->name->parts[0],
                    ['var_dump', 'print_r', 'var_export']):
                $result = concat($result, Success(new PrintableResult(Pair($node->name->parts[0],
                    value($state, $node->args[0]->value, $code)))));
                break;

            // Assignment
            case $node instanceof Assign:
                $state = updateVariable(name($node->var), value($state, $node->expr, $code))->run($state);
                $result = concat($result, Success(new VariableAssignmentResult(Pair(name($node->var),
                    value($state, $node->expr, $code)))));
                break;

            // Variables
            case $node instanceof Variable:
                $variable = variable($state, $node);
                $state = updateVariable($variable->_1, $variable->_2)->run($state);
                $result = concat($result, Success(new VariableAssignmentResult($variable)));
                break;

            // Method call
            case $node instanceof MethodCall:
                $methodCallResult = methodCall($state, $code, $node);
                $varName = '';
                if (!$methodCallResult instanceof Unit) {
                    $varName = generateVarName($state);
                    $state = updateVariable($varName, $methodCallResult)->run($state);
                }
                $result = concat($result, Success(new VariableAssignmentResult(Pair($varName, $methodCallResult))));
                break;

            // Class declaration
            case $node instanceof Class_:
                eval(substr($code, $node->getAttribute("startFilePos"),
                    $node->getAttribute("endFilePos") - 5));
                $result = concat($result, Success(new ClassDeclarationResult($node->name)));
                break;

            // Function declaration
            case $node instanceof Function_:
                eval(substr($code, $node->getAttribute("startFilePos"),
                    $node->getAttribute("endFilePos") + 1));
                $result = concat($result, Success(new FunctionDeclarationResult($node->name)));
                break;

            // Property fetch
            case $node instanceof PropertyFetch:
                $funcCallResult = propertyFetch($state, $code, $node);
                $varName = '';
                if (!$funcCallResult instanceof Unit) {
                    $varName = generateVarName($state);
                    $state = updateVariable($varName, $funcCallResult)->run($state);
                }
                $result = concat($result, Success(new VariableAssignmentResult(Pair($varName, $funcCallResult))));
                break;

            // Stmt
            case $node instanceof Stmt:
            case $node instanceof Expr && !nodeIsNull($node):
                $stmt = evaluateNode($state, $node, $code);
                $state = $stmt->_1;
                $result = concat($result, $stmt->_2);
                break;

            default:
                $value = value($state, $node, $code);
                $varName = '';
                if (!$value instanceof Unit) {
                    $varName = generateVarName($state);
                    $state = updateVariable($varName, $value)->run($state);
                }
                $result = concat($result, Success(new VariableAssignmentResult(Pair($varName, $value))));
                break;
        }
    }
    return Pair($state, $result);
}

function nodeIsNull(Node $node): bool
{
    return ($node instanceof ConstFetch && strtolower($node->name->parts[0]) == "null");
}

function value($state, $node, $code)
{
    switch(true) {
        case $node instanceof FuncCall:
            return funcCall($state, $node, $code);

        case $node instanceof ConstFetch:
            switch($node->name->parts[0]) {
                case "true": return true;
                case "false": return false;
                case "null": return null;
                case !defined($node->name->parts[0]): return $node->name->parts[0];
                default: return eval("return {$node->name->parts[0]};");
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
            $className = className($node->class->parts);
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
        case $statement->var instanceof FuncCall:
            $caller = funcCall($state, $statement->var, $code);
            return $caller->{$statement->name}(...args($state, $statement->args, $code));
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
            throw new \RuntimeException("Unidentified caller: " . get_class($statement->var));
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
            return value($state, $e->value, $code);
        }, $node->args));
    }
    if (isset($node->name->parts)) {
        $functionName = "\\" . ltrim(implode("\\", $node->name->parts), "\\");
        return call_user_func_array($functionName, array_map(function ($e) use ($state, $code) {
            return value($state, $e->value, $code);
        }, $node->args));
    } else {
        $someLongAndReservedPhunkieVar = null;
        eval('$someLongAndReservedPhunkieVar = ' . substr($code, $node->getAttribute("startFilePos"), $node->getAttribute("endFilePos")));
        return $someLongAndReservedPhunkieVar;
    }
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
        $varName = generateVarName($someLongAndReservedPhunkieConsoleState);
        $someLongAndReservedPhunkieConsoleState = updateVariable($varName, $someLongAndReservedPhunkieConsoleLocalVariable)->run($someLongAndReservedPhunkieConsoleState);

        $someLongAndReservedPhunkieConsoleResult = concat(
                $someLongAndReservedPhunkieConsoleResult,
                Success(new VariableAssignmentResult(Pair($varName, $someLongAndReservedPhunkieConsoleLocalVariable))));
    }

    return Pair($someLongAndReservedPhunkieConsoleState, $someLongAndReservedPhunkieConsoleResult);
}

function generateVarName($state)
{
    $nextAvailable = function ($numbers) use (&$nextAvailable): Trampoline { $on = match($numbers); switch(true) {
        case $on(Nil): return new Done(0);
        case $on(ListNoTail($head, Nil)): return new Done($head + 1);
        case $on(ListWithTail($head, $tail)) && $head == ($tail->head - 1):
            return new More(function() use ($nextAvailable, $tail) { return $nextAvailable($tail); });
        case $on(_): return new Done($numbers->head + 1); }
    };
    $next = $nextAvailable(ImmList(...variableLens()->get($state)->keys())
        ->filter(function ($varName) {
            return preg_match("/var(\d+)/", $varName);
        })
        ->map(function ($varName) {
            return (int)substr($varName, 3);
        })
    );
    return 'var' . $next->run();
}

function className($parts)
{
    return implode("\\", $parts);
}

function args($state, $args, $code)
{
    return array_map(function ($arg) use ($state, $code) { return value($state, $arg->value, $code); }, $args);
}