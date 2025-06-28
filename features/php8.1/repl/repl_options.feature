Feature: Evaluating repl options
  As a repl runner
  In order to better develop functional programs
  I should a nice repl to interact with

  Scenario: Some(42)
    Given I am running the repl
    When I type "Some(42)"
    And I press enter
    Then I should see "$var0: Option<Int> = Some(42)"

  Scenario: None()
    Given I am running the repl
    When I type "None()"
    And I press enter
    Then I should see "$var0: None = None"

  Scenario: Some()
    Given I am running the repl
    When I type "Some()"
    And I press enter
    Then I should see "$var0: Option<Unit> = Some(())"

  Scenario: Some(None())
    Given I am running the repl
    When I type "Some(None())"
    And I press enter
    Then I should see "$var0: Option<None> = Some(None)"