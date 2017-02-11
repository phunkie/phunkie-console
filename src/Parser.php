<?php

namespace PhunkieConsole\Parser;

use Phunkie\Cats\State;
use function Phunkie\Functions\currying\curry;
use function Phunkie\Functions\function1\compose;
use function PhunkieConsole\IO\Lens\parserLens;
use PhunkieConsole\Parser\Adapter\PhpParser\ParserAdapter;
use PhpParser\Lexer\Emulative;
use PhpParser\ParserFactory;

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
    $lexer = new Emulative(array('usedAttributes' => array(
        'startLine', 'endLine', 'startFilePos', 'endFilePos', 'comments'
    )));
    $parser = (new ParserFactory)->create(ParserFactory::PREFER_PHP7, $lexer);
    return new ParserAdapter($parser);
}