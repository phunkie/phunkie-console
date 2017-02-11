<?php

namespace PhunkieConsole\Result;

class NoResult extends Result
{
    public function output(callable $formatter): string
    {
        return "";
    }
}