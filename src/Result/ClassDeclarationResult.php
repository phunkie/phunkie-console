<?php

namespace PhunkieConsole\Result;

class ClassDeclarationResult extends Result
{
    public function output(callable $formatter): string
    {
        return ($formatter())['magenta']("defined class " . $this->getResult());
    }
}