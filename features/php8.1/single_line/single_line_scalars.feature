Feature: Evaluating single line scalars
  As a single line runner
  In order to get results without entering the repl
  I should see the output of instructions with a one-liner command

  Scenario: Integers
    When I run phunkie
    And I am using single line mode
    And the input value is "42"
    Then I should see "$var0: Int = 42"

  Scenario: Doubles
    When I run phunkie
    And I am using single line mode
    And the input value is "42.0"
    Then I should see "$var0: Double = 42.0"

  Scenario: Strings
    When I run phunkie
    And I am using single line mode
    And the input value is '"42"'
    Then I should see '$var0: String = "42"'

  Scenario: True
    When I run phunkie
    And I am using single line mode
    And the input value is "true"
    Then I should see '$var0: Boolean = true'

  Scenario: False
    When I run phunkie
    And I am using single line mode
    And the input value is "false"
    Then I should see '$var0: Boolean = false'

  Scenario: Null
    When I run phunkie
    And I am using single line mode
    And the input value is "null"
    Then I should see '$var0: Null = null'

  Scenario: Resource
    When I run phunkie
    And I am using single line mode
    And the input value is "STDIN"
    Then I should see '$var0: Resource = Resource id #1'