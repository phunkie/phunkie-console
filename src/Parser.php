<?php

/*
 * This file is part of PhunkieConsole, a REPL to support your Phunkie development.
 *
 * (c) Marcello Duarte <marcello.duarte@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PhunkieConsole\Parser;

use Phunkie\Cats\State;
use function PhunkieConsole\IO\Lens\parserLens;
use PhunkieConsole\Parser\Adapter\PhpParser\ParserAdapter;
use PhpParser\Lexer\Emulative;
use PhpParser\ParserFactory;
use PhpParser\Lexer\PhpVersion;

const phpParser = "\\PhunkieConsole\\Parser\\phpParser";

function parse($code): State
{
    return new State(function($state) use ($code) {
        $parser = parserLens()->get($state);

        $parsedCode = $parser()->parse($code);
        return Pair($state, Pair($code, $parsedCode));
    });
}

function phpParser()
{
    $lexer = new Emulative(null, [
        'usedAttributes' => [
            'startLine', 'endLine', 'startFilePos', 'endFilePos', 'comments'
        ]
    ]);
    $parser = (new ParserFactory)->createForHostVersion();
    return new ParserAdapter($parser);
}
