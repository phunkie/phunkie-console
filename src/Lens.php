<?php

namespace PhunkieConsole\IO\Lens;

use Phunkie\Cats\Lens;
use function Phunkie\Functions\lens\makeLenses;
use function Phunkie\Functions\lens\member;
use Phunkie\Types\ImmList;

function promptLens(): Lens
{
    return new Lens(
        function($state) {
            $prompt = member("prompt")->get($state)->get();
            return call_user_func($prompt->_1, $prompt->_2) . " {$prompt->_3} ";
        },
        function($state, $newPrompt) { return member("prompt")->set($state, $newPrompt); }
    );
}

function parserLens(): Lens
{
    return new Lens(
        function($state){
            $config = member("config")->get($state);
            return member("parser")->get($config->get())->get();
        },
        function($state, $newParser) {
            $config = member("config")->get($state);
            $config = member("parser")->set($config->get(), $newParser);
            return member("config")->set($state, $config->get());
        }
    );
}

function configLens(): Lens
{
    return new Lens(
        function($state){
            return member("config")->get($state);
        },
        function($state, $config){
            return member("config")->set($state, $config);
        }
    );
}

function config($state, $parameter)
{
    return member($parameter)->get(configLens()->get($state)->get());
}

function variableLens(): Lens
{
    return new Lens(
        function($state) {
            $symbolTable = member("symbol table")->get($state)->get();
            return member("variables")->get($symbolTable)->get();
        },
        function($state, $newVars) {
            $symbolTable = member("symbol table")->get($state)->get();
            $symbolTable = member("variables")->set($symbolTable, $newVars);
            return member("symbol table")->set($state, Some($symbolTable));
        }
    );
}

function getVariableValue($state, $variableName)
{
    /**
     * @var \Phunkie\Types\ImmMap $map
     */
    $map = variableLens()->get($state);
    if (!$map->offsetExists($variableName)) {
        trigger_error("Undefined variable: $variableName in console", E_USER_NOTICE);
    }
    return $map->get($variableName)->get();
}

function updateVariable($var, $value)
{
    return Function1(function($state) use ($var, $value) {
        $variables = variableLens()->get($state);

        if (strpos($var, "[") !== false) {
            $key = substr($var, 0, strpos($var, "["));
            if ($variables->contains($key)) {
                $oldValue = $variables[$key]->get();
                eval('$oldValue' . substr($var, strpos($var, "["), strlen($var) - strpos($var, "[")) . ' = ' . $value . ';');
                $value = $oldValue;
                $var = $key;
            }
        }

        $variables = member($var)->set($variables, Some($value));
        return variableLens()->set($state, Some($variables));
    });
}

function updatePrompt($state, $blockType)
{
    $L = makeLenses('prompt', 'config');
    $config = $L->config->get($state)->get();
    $state = $L->prompt->set(Tuple($config->getOrElse("prompt-color", ($config["formatter"]->get())()['purple']), "phunkie", $blockType), $state);
    return $state;
}

function updateBlock($state, $block)
{
    return makeLenses('block')->block->set($block, $state);
}

function getBlock($state): ImmList
{
    return makeLenses('block')->block->get($state)->getOrElse(Nil());
}