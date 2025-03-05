<?php

/*
 * This file is part of PhunkieConsole, a REPL to support your Phunkie development.
 *
 * (c) Marcello Duarte <marcello.duarte@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PhunkieConsole\Result;

function variableAssignmentResultFactory(): callable
{
    return fn($evaluated) => new VariableAssignmentResult($evaluated);
}

function printableResultFactory(): callable
{
    return fn($evaluated) => new PrintableResult($evaluated);
}

function classDeclarationResultFactory(): callable
{
    return fn($evaluated) => new ClassDeclarationResult($evaluated);
}

function functionDeclarationResultFactory(): callable
{
    return fn($evaluated) => new FunctionDeclarationResult($evaluated);
}