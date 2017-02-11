<?php

namespace PhunkieConsole\Command;

class InvalidCommand
{
    private $input;

    public function __construct(string $input)
    {
        $this->input = $input;
    }

    public function run($state)
    {
        return Pair($state, Nel(Failure(new \Error("Invalid command $this->input. Type :? for help"))));
    }
}