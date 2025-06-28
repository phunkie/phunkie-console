Feature: Evaluating repl arrays
  As a repl runner
  In order to better develop functional programs
  I should a nice repl to interact with

  Scenario: Empty old syntax
    Given I am running the repl
    When I type "array()"
    And I press enter
    Then I should see "$var0: Array<Nothing> = []"

  Scenario: Empty new syntax
    Given I am running the repl
    When I type "[]"
    And I press enter
    Then I should see "$var0: Array<Nothing> = []"

  Scenario: one element
    Given I am running the repl
    When I type "[42]"
    And I press enter
    Then I should see "$var0: Array<Int> = [42]"

  Scenario: mixed
    Given I am running the repl
    When I type '[42,false]'
    And I press enter
    Then I should see "$var0: Array<Mixed> = [42, false]"

  Scenario: associative
    Given I am running the repl
    When I type '["foo" => 42]'
    And I press enter
    Then I should see '$var0: Array<String, Int> = ["foo" => 42]'