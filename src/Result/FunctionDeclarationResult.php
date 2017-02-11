<?php

namespace PhunkieConsole\Result;

class FunctionDeclarationResult extends Result
{
    public function output(callable $formatter): string
    {
        return ($formatter())['magenta']("defined function " . $this->getResult());
    }
}