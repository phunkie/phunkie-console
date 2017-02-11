Feature: Evaluating single line function calls
  As a single line runner
  In order to get results without entering the repl
  I should see the output of instructions with a one-liner command

  Scenario: one argument
    When I run phunkie
    And I am using single line mode
    And the input value is 'strlen("asdf")'
    Then I should see "$var0: Int = 4"