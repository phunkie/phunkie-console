Feature: Evaluating repl function calls
  As a repl runner
  In order to better develop functional programs
  I should a nice repl to interact with

  Scenario: one argument
    Given I am running the repl
    When I type 'strlen("asdf")'
    And I press enter
    Then I should see "$var0: Int = 4"