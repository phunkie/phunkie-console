Feature: Evaluating repl class declaration
  As a repl runner
  In order to better develop functional programs
  I should a nice repl to interact with

  Scenario: class Foo {}
    Given I am running the repl
    When I type 'class Foo {}'
    And I press enter
    Then I should see "defined class Foo"

  Scenario: 2 classes
    Given I am running the repl
    When I type "class A {} class B {}"
    And I press enter
    Then I should see
    """
    defined class A
    defined class B
    """

  Scenario: using the class
    Given I am running the repl
    When I type "class Zoo {}"
    And I press enter
    And I type "new Zoo()"
    And I press enter
    Then I should see the output starting "$var0: Zoo = Zoo@" ...