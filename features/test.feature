Feature:
  Scenario:
    When I run "pwd"
    Then I see
    """
    /
    """
    Then I see "/"

  Scenario:
    When I run "pwd" on "default"

  Scenario:
    When I run "pwd" on "foo"
