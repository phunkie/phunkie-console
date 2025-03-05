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

use function Phunkie\Functions\immlist\concat;
use function Phunkie\Functions\tuple\assign;
use function Phunkie\PatternMatching\Referenced\Success as Valid;
use function Phunkie\PatternMatching\Referenced\Failure as Invalid;
use Phunkie\Types\ImmList;
use Phunkie\Types\ImmMap;
use Phunkie\Types\Option;
use Phunkie\Types\Pair;
use Phunkie\Validation\Validation;
use const PhunkieConsole\IO\Colours\colours;
use function PhunkieConsole\IO\Lens\config;
use PhunkieConsole\Result\PrintableResult;

class ImportCommand
{
    private $input;
    private $formatter;

    public function __construct($input)
    {
        $this->input = $input;
    }

    public function run(ImmMap $state): Pair // <AppState, ImmList<Validation<String, PrintableResult>>
    {
        $this->formatter = config($state, "formatter")->getOrElse(colours);
        $module = trim(substr(trim($this->input), strpos(trim($this->input), " ") + 1));
        return Pair(
            $state,
            $this->import($module === false ? "" : $module)
                ->map(function($result) { $on = pmatch($result); switch(true) {
                    case $on(Valid($x)): return Success(new PrintableResult(Pair(None, $x)));
                    case $on(Invalid($x)): return $result;
        }}));
    }

    private function import(string $module): ImmList // <Validation<String, String>>
    {
        $path = explode("/", $module);

        if (count($path) !== 2) {
            return ImmList($this->failure(Some("Invalid module"), $module));
        }

        $namespace = $function = null;
        (assign($namespace, $function)) (Pair("Phunkie\\Functions\\$path[0]", $path[1]));
        $userFunctions = $this->findAllFunctionsInModule($namespace);

        switch(true) {
            case !$this->isValidPath($path): return $this->invalidPath($module);
            case $this->isModuleEmpty($userFunctions): return $this->invalidModule($path);
            case $this->isFunctionWildcard($function): return $this->importAllFunctionsInModule($userFunctions, $namespace);
            default: return $this->importFunctionFold(ImmList($function), $namespace);
        }
    }

    private function isValidPath(array $path): bool
    {
        return count($path) == 2;
    }

    private function invalidPath(string $module): ImmList // Validation<String, String>
    {
        return ImmList($this->failure(Some("Invalid path"), $module));
    }

    private function findAllFunctionsInModule(string $namespace): ImmList // <String>
    {
        return ImmList(...get_defined_functions()["user"])
            ->filter(function ($f) use ($namespace) {
                return strpos(strtolower($f), strtolower("$namespace\\")) === 0;
            });
    }

    private function importFunctionFold(ImmList $userFunctions, $namespace): ImmList // Validation<String, String>
    {
        return ($userFunctions->foldLeft(Nil()))(function($results, $function) use ($namespace) {
            switch (true) {
                case !is_callable("\\$namespace\\$function"):
                    return concat($results, $this->invalidFunction($function));
                case function_exists($function):
                    return concat($results, $this->functionAlreadyInScope($function));
                default:
                    $this->evalFunction($function, $namespace);
                    return concat($results, $this->importedFunction($namespace, $function));
            }
        });
    }

    private function evalFunction(string $function, string $namespace)
    {
        $reflection = new \ReflectionFunction("\\$namespace\\$function");
        $args = [];
        $justArgs = [];
        foreach ($reflection->getParameters() as $argument) {
            $arg = '';
            if ($argument->isVariadic()) {
                $arg = $argument->getType() . ' ...$' . $argument->getName();
            } elseif ($argument->isPassedByReference()) {
                $arg = $argument->getType() . ' &$' . $argument->getName();
            } else {
                $arg = $argument->getType() . ' $' . $argument->getName();
            }
            $argTypeAndVar = explode(' ', str_replace('&', '', $arg));
            $justArgs[] = end($argTypeAndVar);

            if ($argument->isOptional() && $argument->isDefaultValueAvailable()) {
                $defaultValue = $argument->getDefaultValue();
                switch (true) {
                    case $defaultValue === true: $defaultValue = 'true'; break;
                    case $defaultValue === null: $defaultValue = 'null'; break;
                    case $defaultValue == None(): $defaultValue = 'None()'; break;
                    case $defaultValue === false: $defaultValue = 'false'; break;
                }
                $arg .= ' = ' . $defaultValue;
            }

            $args[] = $arg;
        }

        eval ("function $function(" . implode(", ", $args) . ") {
            return \\$namespace\\$function(" . implode(', ', $justArgs) . ");
        }");
        eval ("const $function = '\\$namespace\\$function';");
    }

    private function isModuleEmpty(ImmList $userFunctions): bool
    {
        return $userFunctions->length == 0;
    }

    private function invalidModule(array $path): ImmList
    {
        return ImmList($this->failure(Some("Invalid module"), $path[0]));
    }

    private function invalidFunction($function): Validation
    {
        return $this->failure(Some("Invalid function"), $function);
    }

    private function functionAlreadyInScope($function): Validation
    {
        return $this->failure(None(), "Function $function already in scope");
    }

    private function importedFunction($namespace, $function): Validation
    {
        return $this->success("imported function \\$namespace\\$function()");
    }

    private function isFunctionWildcard(string $function): bool
    {
        return $function === "*";
    }

    private function importAllFunctionsInModule($userFunctions, string $namespace)
    {
        return $this->importFunctionFold(
            $userFunctions->map(function ($f) { return substr(strrchr($f, "\\"), 1); }),
            $namespace
        );
    }

    private function failure(Option $title, string $body)
    {
        return Failure(
            new \Error(
                ($this->formatter)()['boldRed']($title->isEmpty() ? "" : "{$title->get()}: \n") .
                ($this->formatter)()['red']($body) . "\n"
            )
        );
    }

    private function success(string $body)
    {
        return Success(($this->formatter)()['magenta']($body));
    }
}