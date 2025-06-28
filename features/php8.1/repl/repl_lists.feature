Feature: Evaluating repl lists
  As a repl runner
  In order to better develop functional programs
  I should a nice repl to interact with

  Scenario: List(42)
    Given I am running the repl
    When I type "ImmList(42)"
    And I press enter
    Then I should see "$var0: List<Int> = List(42)"

  Scenario: List(1,2,3)
    Given I am running the repl
    When I type "ImmList(1,2,3)"
    And I press enter
    Then I should see "$var0: List<Int> = List(1, 2, 3)"

  Scenario: List(1,2,Some(42))
    Given I am running the repl
    When I type "ImmList(1,2,Some(42))"
    And I press enter
    Then I should see "$var0: List<Mixed> = List(1, 2, Some(42))"

  Scenario: List(Some(1),Some(2),Some(3))
    Given I am running the repl
    When I type "ImmList(Some(1),Some(2),Some(3))"
    And I press enter
    Then I should see "$var0: List<Option<Int>> = List(Some(1), Some(2), Some(3))"