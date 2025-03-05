<?php

/*
 * This file is part of PhunkieConsole, a REPL to support your Phunkie development.
 *
 * (c) Marcello Duarte <marcello.duarte@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PhunkieConsole\Command;

function isCommand($input)
{
    return strpos(trim($input), ":") === 0;
}

function Command($input) { switch(true) {
    case strpos(trim($input), ":import ") === 0:
        return new ImportCommand(trim($input));
    case trim($input) === ":exit":
        echo "Stay phunkie! \\o". PHP_EOL; exit;
    case trim($input) === ":help":
    case trim($input) === ":?":
    case trim($input) === ":h":
        return new HelpCommand($input);
    case strpos(trim($input), ":type ") === 0:
    case strpos(trim($input), ":t ") === 0:
        return new TypeCommand(trim($input));
    case strpos(trim($input), ":kind ") === 0:
    case strpos(trim($input), ":k ") === 0:
        return new KindCommand(trim($input));
    case strpos(trim($input), ":l ") === 0:
    case strpos(trim($input), ":load ") === 0:
        return new LoadCommand(trim($input));
    default:
        return new InvalidCommand(trim($input));}
}