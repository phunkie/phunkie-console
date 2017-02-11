<?php

use Behat\Behat\Tester\Exception\PendingException;
use Behat\Behat\Context\Context;
use Behat\Gherkin\Node\PyStringNode;
use Behat\Gherkin\Node\TableNode;

/**
 * Defines application features from the specific context.
 */
class SingleLineContext implements Context
{
    /**
     * @var ExecBuilder
     */
    private $phunkieBuilder;

    /**
     * @When I run phunkie
     */
    public function iRunPhunkie()
    {
        $this->phunkieBuilder = new ExecBuilder;
    }

    /**
     * @When I am using single line mode
     */
    public function iAmUsingSingleLineMode()
    {
        $this->phunkieBuilder->withSingleLineMode();
    }

    /**
     * @When the input value is :input
     */
    public function theInputValueIs($input)
    {
        $this->phunkieBuilder->withInputValue($input);
    }

    /**
     * @Then I should see :expected
     */
    public function iShouldSee($expected)
    {
        exec($this->phunkieBuilder->build(), $actual);
        if ($actual[0] !== $expected) {
            throw new \RuntimeException("Expected $expected, got " . implode("\n", $actual));
        }
    }

    /**
     * @Then I should see
     */
    public function iShouldSee2(PyStringNode $expected)
    {
        exec($this->phunkieBuilder->build(), $actual);
        if ($actual !== $expected->getStrings()) {
            throw new \RuntimeException(
                "Expected " . $expected .
                ", got " . implode("\n", $actual));
        }
    }
}
