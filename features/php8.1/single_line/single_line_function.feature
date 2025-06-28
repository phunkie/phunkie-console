Feature: Evaluating single line class declaration
  As a single line runner
  In order to get results without entering the repl
  I should see the output of instructions with a one-liner command

  Scenario: class Foo {}
    When I run phunkie
    And I am using single line mode
    And the input value is "function foo() {}"
    Then I should see "defined function foo"