<?php

use Behat\Gherkin\Node\PyStringNode;
use Behat\Behat\Tester\Exception\PendingException;
use Behat\Behat\Context\Context;

use const Phunkie\Functions\function1\identity;
use Phunkie\Validation\Validation;
use const PhunkieConsole\IO\Colours\colours;
use const PhunkieConsole\IO\Colours\noColours;
use function PhunkieConsole\IO\Lens\variableLens;
use const PhunkieConsole\Parser\phpParser;
use function PhunkieConsole\Repl\evaluate;
use function Phunkie\PatternMatching\Referenced\Success as Valid;
use function Phunkie\PatternMatching\Referenced\Failure as Invalid;

/**
 * Defines application features from the specific context.
 */
class ReplContext implements Context
{
    private $input;
    private $result;
    private $state;

    /**
     * Initializes context.
     *
     * Every scenario gets its own context instance.
     * You can also pass arbitrary arguments to the
     * context constructor through behat.yml.
     */
    public function __construct()
    {
        $this->state = ImmMap([
            "argv" => [],
            "symbol table" => ImmMap([
                "variables" => ImmMap(),
                "functions" => ImmMap()
            ]),
            "block" => ImmList(),
            "prompt" => Tuple(identity, "phunkie", ">"),
            "config" => ImmMap([
                "parser" => phpParser,
                "formatter" => noColours
            ])
        ]);
    }

    /**
     * @Given I am running the repl
     */
    public function iAmRunningTheRepl()
    {
    }

    /**
     * @When I type :input
     */
    public function iType($input)
    {
        $this->input = $input;
    }

    /**
     * @When I press enter
     */
    public function iPressEnter()
    {
        $stateAndResult = evaluate($this->input)->run($this->state);
        $this->state = $stateAndResult->_1;
        $this->result = $stateAndResult->_2->map(function($validation) {
            $on = match($validation);
            switch (true) {
                case $on(Valid($a)): return $a->output(noColours);
                case $on(Invalid($a)): return $a->output(noColours);
            }
        })->toArray();
    }

    /**
     * @Then I should see :result
     */
    public function iShouldSee($result)
    {
        if ($result !== $this->unfoldedResult()) {
            throw $this->expectationMismatch($result);
        }
    }

    private function expectationMismatch(string $result): Exception
    {
        return new \Exception("Expected $result, found: " . print_r($this->unfoldedResult(), true));
    }

    private function unfoldedResult()
    {
        return implode("\n", $this->result);
    }

    /**
     * @Then :symbol should be on the symbol table under variables
     */
    public function shouldBeOnTheSymbolTableUnderVariables($symbol)
    {
        /** @var $vars \Phunkie\Types\ImmMap $vars */
        $vars = variableLens()->get($this->state);
        if ($vars->get($symbol) == None()) {
            throw new RuntimeException("Symbol $symbol not in the symbols table under variables");
        }
    }

    /**
     * @Then the value for :symbol under variables should be :value
     */
    public function theValueForUnderVariablesShouldBe($symbol, $value)
    {
        /** @var $vars \Phunkie\Types\ImmMap $vars */
        $vars = variableLens()->get($this->state);
        if ($vars->get($symbol) != Some($value)) {
            throw new RuntimeException("Expected " . Some($value)->show() . "got" . $vars->get($symbol)->show());
        }
    }

    /**
     * @Then I should see
     */
    public function iShouldSee2(PyStringNode $result)
    {
        if (implode("\n", $result->getStrings()) !== $this->unfoldedResult()) {
            throw $this->expectationMismatch(implode("\n", $result->getStrings()));
        }
    }

    /**
     * @Then I should see the output starting :arg1 ...
     */
    public function iShouldSeeTheOutputStarting($result)
    {
        if (!strpos($this->unfoldedResult(), $result) === 0) {
            throw $this->expectationMismatch($result);
        }
    }

    /**
     * @When I press enter :arg1 times
     */
    public function iPressEnterTimes($times)
    {
        while($times-- > 0) {
            $this->iPressEnter();
        }
    }

    /**
     * @Then I still see no errors
     */
    public function iStillSeeNoErrors()
    {

    }
}
