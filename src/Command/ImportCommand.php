<?php

namespace PhunkieConsole\Command;

use function Phunkie\Functions\show\showValue;
use function Phunkie\PatternMatching\Referenced\Some as Just;
use Phunkie\Types\Option;
use Phunkie\Types\Pair;
use function PhunkieConsole\IO\Colours\colours;
use PhunkieConsole\Result\PrintableResult;

class ImportCommand
{
    private $input;

    public function __construct($input)
    {
        $this->input = $input;
    }

    public function run($state): Pair
    {
        $module = trim(substr($this->input, strpos($this->input, " ") + 1));
        $on = match($this->import($module === false ? "" : $module)); switch(true) {
            case $on(Just($result)): return Pair($state, Nel(Success(new PrintableResult(Pair(None, $result)))));
        }
    }

    private function import($module): Option
    {
        if (count($parts = explode("/", $module)) != 2) {
            return Some(colours()['boldRed']("Invalid module: ") . colours()['red'](showValue(trim($module))));
        }

        $namespace = "Phunkie\\Functions\\$parts[0]";
        $function = $parts[1];

        $result = "";
        $createFunction = function($namespace, $function) use (&$result) {
            if (function_exists($function)) {
                $result .= colours()['red']("Function $function already in scope\n");
                return;
            }
            eval ("function $function(...\$args) { return call_user_func_array('\\$namespace\\$function', \$args); }");
            eval ("const $function = '\\$namespace\\$function';");
            $result .= colours()['magenta']("imported function \\$namespace\\$function()\n");
        };

        if ($function === "*") {
            ImmList(...get_defined_functions()["user"])
                ->filter(function($f) use ($namespace) { return strpos(strtolower($f),strtolower($namespace)) === 0;})
                ->map(function($f){ return substr(strrchr($f, "\\"), 1); })
                ->map(function($function) use ($namespace, $createFunction) { $createFunction($namespace, $function); });
        } else {
            $createFunction($namespace, $function);
        }
        return Some($result);
    }
}