<?php

namespace PhunkieConsole\Block;

class BlockLine
{
    private $line;

    public function __construct(string $line)
    {
        $this->line = $line;
    }
}