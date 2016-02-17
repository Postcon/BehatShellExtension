Feature: Copy files
  In order to prepare integration tests
  As a test automator
  I want to copy files to directories (on remote servers)

  Scenario:
    Given I copy file "test.txt" to "/tmp"
    When I run "cat /tmp/test.txt"
    Then it should pass
    And I see
    """
    content of test.txt
    """

  Scenario:
    Given I copy file "test.txt" to "/tmp" on "default"
    When I run "cat /tmp/test.txt" on "default"
    Then it should pass
    And I see
    """
    content of test.txt
    """
