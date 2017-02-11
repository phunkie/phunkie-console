Feature: Evaluating single line arrays
  As a single line runner
  In order to get results without entering the repl
  I should see the output of instructions with a one-liner command

  Scenario: Empty old syntax
    When I run phunkie
    And I am using single line mode
    And the input value is "array()"
    Then I should see "$var0: Array<Nothing> = []"

  Scenario: Empty new syntax
    When I run phunkie
    And I am using single line mode
    And the input value is "[]"
    Then I should see "$var0: Array<Nothing> = []"

  Scenario: one element
    When I run phunkie
    And I am using single line mode
    And the input value is "[42]"
    Then I should see "$var0: Array<Int> = [42]"

  Scenario: mixed
    When I run phunkie
    And I am using single line mode
    And the input value is '[42,false]'
    Then I should see "$var0: Array<Mixed> = [42, false]"

  Scenario: associative
    When I run phunkie
    And I am using single line mode
    And the input value is '["foo" => 42]'
    Then I should see '$var0: Array<String, Int> = ["foo" => 42]'