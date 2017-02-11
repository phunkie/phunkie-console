<?php

namespace PhunkieConsole\Command;

function isCommand($input)
{
    return strpos($input, ":") === 0;
}

function Command($input) { switch(true) {
    case strpos(trim($input), ":import ") === 0:
        return new ImportCommand($input);
    case trim($input) === ":exit":
        echo "Stay phunkie! \\o". PHP_EOL; exit;
    case trim($input) === ":help":
    case trim($input) === ":?":
    case trim($input) === ":h":
        return new HelpCommand($input);
    case strpos(trim($input), ":type ") === 0:
    case strpos(trim($input), ":t ") === 0:
        return new TypeCommand($input);
    case strpos(trim($input), ":kind ") === 0:
    case strpos(trim($input), ":k ") === 0:
        return new KindCommand($input);
    case strpos(trim($input), ":l ") === 0:
    case strpos(trim($input), ":load ") === 0:
        return new LoadCommand($input);
    default:
        return new InvalidCommand($input);}
}