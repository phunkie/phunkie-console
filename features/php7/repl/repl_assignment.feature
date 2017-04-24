Feature: Evaluating repl assignment
  As a repl runner
  In order to better develop functional programs
  I should a nice repl to interact with

  Scenario: $a = 42
    Given I am running the repl
    When I type '$a = 42'
    And I press enter
    Then I should see "$a: Int = 42"

  Scenario: Inspecting $a
    Given I am running the repl
    When I type '$a = 42'
    And I press enter
    And I type '$a'
    And I press enter
    Then I should see "$var0: Int = 42"

  Scenario: Add variable to symbolTable
    Given I am running the repl
    When I type '$a = 42'
    And I press enter
    Then "a" should be on the symbol table under variables
    And the value for "a" under variables should be "42"

  Scenario: Create a constant
    Given I am running the repl
    When I type 'const A = 42'
    And I press enter
    And I type 'A'
    And I press enter
    Then I should see "$var0: Int = 42"