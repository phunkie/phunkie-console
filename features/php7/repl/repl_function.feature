Feature: Evaluating repl function declaration
  As a repl runner
  In order to better develop functional programs
  I should a nice repl to interact with

  Scenario: function Foo {}
    Given I am running the repl
    When I type "function foo() {}"
    And I press enter
    Then I should see "defined function foo"

#  Scenario: running custom function
#    Given I am running the repl
#    When I type "function foo() { return 42; }"
#    And I press enter
#    And I type "foo()"
#    Then I should see "$var0: Null = 42"