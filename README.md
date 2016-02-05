# BehatShellExtension

Behat extension for executing shell commands within features. The shell commands can be run on remote servers using ssh or locally without network.

## Installation

Using [composer](https://getcomposer.org/download/):

```
composer require postcon/behat-shell-extension dev-master
```

## Usage

```gherkin
# test.feature

Feature: Running commands
  In order to run useful integration tests
  As a tester
  I want to execute shell commands and check their output

  Scenario: Run command on the default shell/server and define expected output
    When I run "pwd"
    Then It should pass
    And I see
    """
    /tmp
    """

  Scenario: Run command on the default shell/server and define expected output in inline-style
    When I run "pwd"
    Then It should pass
    And I see "/tmp"

  Scenario: Run command on the shell/server "app"
    When I run "app/console --env=prod do:mi:mi" on "app"
    Then It should pass
```

## Configuration

```yml
# behat.yml
extensions:
  ShellExtension:
    default:
      type: local
      base_dir: /tmp
    app:
      type: remote
      base_dir: /tmp
      ssh_options: -i .ssh/id_rsa shell.example.com
```

