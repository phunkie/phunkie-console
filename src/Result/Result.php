<?php

namespace PhunkieConsole\Result;

abstract class Result
{
    private $result;

    public function __construct($result)
    {
        $this->result = $result;
    }

    abstract public function output(callable $formatter): string;

    public function getResult()
    {
        return $this->result;
    }
}