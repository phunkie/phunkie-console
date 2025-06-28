Feature: Evaluating single line assignment
  As a single line runner
  In order to get results without entering the repl
  I should see the output of instructions with a one-liner command

  Scenario: $a = 42
    When I run phunkie
    And I am using single line mode
    And the input value is "$a = 42"
    Then I should see "$a: Int = 42"
