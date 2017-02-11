Feature: Evaluating single line class declaration
  As a single line runner
  In order to get results without entering the repl
  I should see the output of instructions with a one-liner command

  Scenario: class Foo {}
    When I run phunkie
    And I am using single line mode
    And the input value is "class Foo {}"
    Then I should see "defined class Foo"

  Scenario: class Foo {}
    When I run phunkie
    And I am using single line mode
    And the input value is "class Foo {}"
    Then I should see "defined class Foo"

  Scenario: 2 classes
    When I run phunkie
    And I am using single line mode
    And the input value is "class A {} class B {}"
    Then I should see
    """
    defined class A
    defined class B
    """