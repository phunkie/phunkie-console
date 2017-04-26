Feature: Trampoline for the REPL

As a repl runner
In order to keep using the REPL beyond the stack limit
I'd like a smart way to avoid the effects of excessive recursion

  @memory
  Scenario: Repeat the repl 256 times and no memory errors are shown
    Given I am running the repl
    And I type '42'
    When I press enter "300" times
    Then I still see no errors