Feature: Evaluating single line lists
  As a single line runner
  In order to get results without entering the repl
  I should see the output of instructions with a one-liner command

  Scenario: List(42)
    When I run phunkie
    And I am using single line mode
    And the input value is "ImmList(42)"
    Then I should see "$var0: List<Int> = List(42)"

  Scenario: List(1,2,3)
    When I run phunkie
    And I am using single line mode
    And the input value is "ImmList(1,2,3)"
    Then I should see "$var0: List<Int> = List(1, 2, 3)"

  Scenario: List(1,2,Some(42))
    When I run phunkie
    And I am using single line mode
    And the input value is "ImmList(1,2,Some(42))"
    Then I should see "$var0: List<Mixed> = List(1, 2, Some(42))"

  Scenario: List(Some(1),Some(2),Some(3))
    When I run phunkie
    And I am using single line mode
    And the input value is "ImmList(Some(1),Some(2),Some(3))"
    Then I should see "$var0: List<Option<Int>> = List(Some(1), Some(2), Some(3))"