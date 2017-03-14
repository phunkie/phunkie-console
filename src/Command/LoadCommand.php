<?php

namespace PhunkieConsole\Command;

use function Phunkie\Functions\lens\makeLenses;
use function Phunkie\Functions\semigroup\combine;
use Phunkie\Types\Pair;
use function PhunkieConsole\IO\Lens\updateVariable;
use PhunkieConsole\Result\PrintableResult;

class LoadCommand
{
    private $file;

    public function __construct($file)
    {

        $this->file = $file;
    }

    public function run($state): Pair
    {
        $file = trim(substr($this->file, strpos($this->file, " ") + 1));

        if (!file_exists($file)) {
            return Pair($state, Nel(Failure(new \Error("Could not find file $file to load."))));
        }

        if (!is_readable($file)) {
            return Pair($state, Nel(Failure(new \Error("Could not read file $file. Permission denied."))));
        }

        $state = $this->loadFile($file, $state);

        $L = makeLenses('config', 'formatter');
        $result = (combine($L->config, $L->formatter)->get($state)->get())()['magenta']("loaded file " . $file);

        return Pair($state, Nel(Success(new PrintableResult(Pair(None, $result)))));
    }

    private function loadFile($someLongAndReservedPhunkieConsoleFile, $someLongAndReservedPhunkieConsoleState)
    {
        $someLongAndReservedPhunkieConsoleVariablesBefore = get_defined_vars();
        require_once $someLongAndReservedPhunkieConsoleFile;

        $someLongAndReservedPhunkieConsoleVariablesAfter = get_defined_vars();

        foreach (array_udiff_assoc($someLongAndReservedPhunkieConsoleVariablesAfter, $someLongAndReservedPhunkieConsoleVariablesBefore,
            function($after, $before) {
                if ($after === $before) return 0;
                return -1;
            }) as
                 $var => $value) {
            $someLongAndReservedPhunkieConsoleState = updateVariable($var, $value)->run($someLongAndReservedPhunkieConsoleState);
        }

        echo "\n";
        return $someLongAndReservedPhunkieConsoleState;
    }
}