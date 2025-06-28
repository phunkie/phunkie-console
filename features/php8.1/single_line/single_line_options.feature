Feature: Evaluating single line options
  As a single line runner
  In order to get results without entering the repl
  I should see the output of instructions with a one-liner command

  Scenario: Some(42)
    When I run phunkie
    And I am using single line mode
    And the input value is "Some(42)"
    Then I should see "$var0: Option<Int> = Some(42)"

  Scenario: None()
    When I run phunkie
    And I am using single line mode
    And the input value is "None()"
    Then I should see "$var0: None = None"

  Scenario: Some()
    When I run phunkie
    And I am using single line mode
    And the input value is "Some()"
    Then I should see "$var0: Option<Unit> = Some(())"

  Scenario: Some(None())
    When I run phunkie
    And I am using single line mode
    And the input value is "Some(None())"
    Then I should see "$var0: Option<None> = Some(None)"