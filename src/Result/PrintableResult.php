<?php

namespace PhunkieConsole\Result;

class PrintableResult extends Result
{
    public function output(callable $formatter): string
    {
        switch($this->getResult()->_1) {
            case "echo":
            case "print":
            case "var_dump":
            case "var_export":
            case "print_r":
                ob_start();
                eval("{$this->getResult()->_1}(unserialize('" . serialize($this->getResult()->_2) . "'));");
                $output = ob_get_contents();
                ob_end_clean();
                return $formatter()['cyan']($output);
            case "die":
                die($formatter()['cyan']($this->getResult()->_2) . "\n");
            default:
                return $this->getResult()->_2;
        }
    }
}