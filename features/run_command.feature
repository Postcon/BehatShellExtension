Feature: Running commands
  In order to run useful integration tests
  As a tester
  I want to execute shell commands and check their output

  Scenario: Run command on the default shell/server and define expected output
    When I run "pwd"
    Then it should pass
    And I see something like
    """
    /tmp
    """

  Scenario: Run command on the default shell/server and define expected output in inline-style
    When I run "pwd"
    Then it should pass
    And I see something like "/tmp"

  Scenario: Run command on the shell/server "foo"
    When I run "pwd" on "foo"
    Then it should pass
    And I see "/"

  Scenario: Run failing command
    When I run "ls /I/hope/this/does/not/exist/"
    Then it should fail

  Scenario: Run command and looking for it's output
    When I run "ls /"
    Then it should pass
    And I see something like "etc"
