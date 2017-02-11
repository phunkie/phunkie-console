<?php

class ExecBuilder
{
    private $input = "";
    private $phunkieCommand = "bin/phunkie --no-colors ";

    public function withSingleLineMode()
    {
        $this->phunkieCommand .= "-r '%s' ";
    }

    public function withInputValue($input)
    {
        $this->input = $input;
    }

    public function build()
    {
        return sprintf($this->phunkieCommand, $this->input);
    }
}