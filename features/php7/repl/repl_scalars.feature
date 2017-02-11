Feature: Evaluating repl scalars
  As a repl runner
  In order to better develop functional programs
  I should a nice repl to interact with

  Scenario: Integers
    Given I am running the repl
    When I type "42"
    And I press enter
    Then I should see "$var0: Int = 42"

  Scenario: Doubles
    Given I am running the repl
    When I type "42.0"
    And I press enter
    Then I should see "$var0: Double = 42.0"

  Scenario: Strings
    Given I am running the repl
    When I type "'42'"
    And I press enter
    Then I should see '$var0: String = "42"'

  Scenario: True
    Given I am running the repl
    When I type "true"
    And I press enter
    Then I should see '$var0: Boolean = true'

  Scenario: False
    Given I am running the repl
    When I type "false"
    And I press enter
    Then I should see '$var0: Boolean = false'

  Scenario: Null
    Given I am running the repl
    When I type "null"
    And I press enter
    Then I should see '$var0: Null = null'

  Scenario: Resource
    Given I am running the repl
    When I type "STDIN"
    And I press enter
    Then I should see '$var0: Resource = Resource id #1'